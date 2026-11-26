# Performance & OPcache

How Ray.Compiler is fast, the one prerequisite that makes it fast, and how to measure it
without fooling yourself. Distilled from benchmarking against `Ray\Di\Injector` (reflection)
and a `serialize()`d injector.

## TL;DR

- **`CompiledInjector` is the fastest strategy — but only when its scripts are served from OPcache.**
- Without warm OPcache, every `require` **re-parses** the script and compiled looks **several times slower than it really is** — the table below measures ~178 µs cold vs ~22 µs warm (~8×).
- In production this is a non-issue: scripts are compiled at deploy time and OPcache keeps the opcodes in shared memory. In **benchmarks** it is the single biggest source of wrong numbers (see below).

## The three strategies

| Strategy | What runs per object graph | Runtime cost | Notes |
|---|---|---|---|
| **reflection** (`Ray\Di\Injector`) | Build the whole `Container` from the module (annotation reading, binding resolution, AOP weaving), then instantiate via reflection | Container build is **hundreds of ms** for a large app, paid **every request** | Dev only. Untenable for shared-nothing (php-fpm). |
| **serialize** (`serialize()` the injector, `unserialize()` per request) | `unserialize()` reconstructs the `Container` object graph, then instantiate via reflection | Dominated by **`unserialize()`** — paid **every request** (a blob is data; it cannot live in shared OPcache) | Scales linearly with the binding set. |
| **compiled** (`CompiledInjector`) | `require` the few pre-generated scripts the graph touches, run flat `new`/setter code | **Lazy** — only the needed scripts; opcodes served from OPcache | Scripts/classes can be **preloaded** into shared memory across php-fpm workers. |

php-fpm resets all object state between requests; only long-lived workers (Swoole, RoadRunner) amortize this.

Key consequence: the heavy work (annotation reading, AOP class generation, binding analysis) is
done **once at compile/serialize time** for both `serialize` and `compiled`. What remains at runtime
is instantiation. `compiled` wins because flat, OPcache-cached opcodes beat reflection's dynamic
dispatch — and because it loads only the subset of bindings a given request actually uses.

## Why OPcache is the prerequisite

A `require` of a script that is **already in OPcache** (with `opcache.validate_timestamps=0`, or a
warm realpath cache) does **no filesystem access and no parsing** — it just executes cached opcodes.
That is what makes compiled code fast.

Two settings decide whether that happens:

- **`opcache.validate_timestamps`** — set to `0` in production so OPcache never `stat()`s the file to
  check for changes.
- **`opcache.file_update_protection`** (default **2 seconds**) — OPcache refuses to cache a file that
  is *younger than this*, to avoid caching a half-written file. A process that **compiles and then
  immediately runs** therefore re-parses every `require`. In production the gap between deploy-time
  compilation and the first request is far larger than 2s, so this never bites; in a benchmark it
  always does.

For php-fpm (shared-nothing), also note **preloading**: compiled scripts/classes can be loaded into
OPcache *shared memory* once and reused by every worker. A `serialize`d blob cannot — it is data and
must be `unserialize()`d into per-process memory on every request.

## The `file_exists()` optimization

Because a cached `require` touches no filesystem, an eager `file_exists()` guard before it would be the
**only** `stat()` syscall left on the hot path — roughly **30% of the per-build cost** for a small
graph. So `prototype()` and `singleton()` `require` the script directly with no guard:

```php
return require $file;               // happy path: no stat(), just cached opcodes
```

A missing compiled script is a build invariant violation (corrupt or incomplete build); PHP 8 makes a
failed `require` a catchable `Error` that surfaces naturally. `CompiledInjector::getInstance()` keeps its
`file_exists()` pre-check (it reports unbound interfaces as `Unbound`); its redundant
`realpath($this->scriptDir)` — already validated in the constructor — was removed.

## Benchmarking correctly

`demo/benchmark/di_benchmark.php` compares the three strategies and **prints the OPcache hit rate so you can
tell a valid run from a bogus one**. Pitfalls it (and you) must control for:

1. **OPcache must actually cache the compiled scripts.** Back-date generated scripts
   (`touch($file, time() - 3600)`) or run with `-d opcache.file_update_protection=0`. A `sleep()` does
   **not** work on the CLI — OPcache's age check uses the request start time, not wall-clock. Always
   confirm the benchmark reports `(valid)` / a non-zero cached-script count; if it prints `INVALID`,
   the numbers are re-parsing artifacts.
2. **Disable Xdebug and verify it actually took effect** (`-d xdebug.mode=off`) — it inflates
   everything, but the setting alone is not proof: assert `xdebug_info('mode') === []` in the
   benchmarked process itself. `ini_get('xdebug.mode')` is not sufficient — it can report `''`
   while Xdebug is still active in another mode (e.g. a php-fpm pool config not honored at
   runtime).
3. **Watch for a stale global `opcache.preload`** in your `php.ini` — it pollutes shared memory and can
   emit startup errors. Override it with an empty preload file.
4. **Class-autoload warmth** — the first object graph in a process autoloads all its classes (a
   one-time cost). Measure cold (fresh process) and warm (repeated build) separately; don't compare a
   cold number against a warm one.
5. **Singletons aren't "build-many."** A singleton root is built once per process — a tight loop over
   it measures cache hits, not construction. Use a prototype root to measure per-build cost.
6. **Object size matters.** A tiny graph hides the lazy-loading advantage of `compiled` over
   `serialize`; benchmark a realistic root.

## Measured (FakeCar graph, PHP 8.4, OPcache valid)

`ctor + 5 setters + AOP + singleton mirrors`, `N=50,000`, steady-state (warm) per build:

| Strategy | Cold start | Steady (per build) |
|---|---|---|
| reflection | ~11–25 ms (Container build) | ~46 µs |
| serialize | ~0.1 ms (unserialize) | ~47 µs |
| **compiled** | ~0.03 ms (new injector) | **~22 µs** |

`compiled` is ~2× faster per build than reflection/serialize once OPcache is warm. The same numbers
without warm OPcache show `compiled` at ~178 µs — the re-parse trap. Always check the `(valid)` line.

Run it yourself:

```bash
php -d xdebug.mode=off -d opcache.enable_cli=1 -d opcache.validate_timestamps=0 demo/benchmark/di_benchmark.php
```

## Measured at production scale (historical baseline, pre-[#354](https://github.com/ray-di/Ray.Di/pull/354), cold single-run)

These numbers are a single cold-process observation from a large production BEAR.Sunday
application (~600 compiled DI scripts; PHP 8.3, OPcache on, Xdebug off), captured before
[ray-di/Ray.Di#354](https://github.com/ray-di/Ray.Di/pull/354)'s `unserialize()` fix, measuring the per-process cost to acquire the
application root — i.e. one cold php-fpm request ([#135](https://github.com/ray-di/Ray.Compiler/issues/135)). This is a single indicative data
point, not a controlled before/after comparison — see the warm, paired measurement below for that.

| Strategy | Cost | Breakdown |
|---|---|---|
| reflection | **~0.4–0.6 s** | Container build (annotation reading + binding analysis + AOP weaving) dominates |
| serialize | **~29 ms** | ≈ `unserialize()` of the whole Container (~25 ms, mostly class autoload) + build (~4 ms); blob ~0.5 MB |
| compiled | **~5 ms** | lazy `require` of only the scripts the root touches (~30 of ~600); offline compile ~0.5 s |

- **reflection** rebuilds the entire Container every request (php-fpm resets object state between
  requests) — untenable for shared-nothing. This is the cost the other two exist to avoid.
- **serialize** scales ~linearly with the binding set, and the blob cannot live in shared OPcache —
  it is re-`unserialize()`d every request.
- **compiled** loads only what a request needs — cost tracks the scripts a request touches, not the
  total binding set — and its scripts can be preloaded into shared OPcache across workers.

The measurements below share the same runtime configuration and object graph (this app's
federated-import feature disabled to isolate its own object-graph cost, PHP 8.5.10, php-fpm
started with `-d xdebug.mode=off` and `XDEBUG_MODE=off`, `xdebug_info('mode') === []` asserted on
every recorded sample, `opcache.validate_timestamps=0`, php-fpm `pm.max_requests=1` fresh process
per request) but follow separate protocols — don't merge their numbers into one table or ratio.

## Measured after [ray-di/Ray.Di#354](https://github.com/ray-di/Ray.Di/pull/354) (warm OPcache SHM, same app)

An earlier version of this section reported numbers measured while Xdebug was actually running in
`develop` mode despite the php-fpm pool's `xdebug.mode=off` setting — Xdebug did not honor it, and
the per-sample check at the time only verified `ini_get('xdebug.mode')` (always `''`) instead of
`xdebug_info('mode')` (which reported `['develop']`). The headline claim itself changed, not just
`reflection`'s number: paired total diff median was reported as -7.86 ms (29.1% reduction);
corrected value is -5.14 ms (33.9% reduction). The numbers below are a corrected re-measurement
asserting `xdebug_info('mode') === []` on every sample; see
[ray-di/Ray.Compiler#135](https://github.com/ray-di/Ray.Compiler/issues/135#issuecomment-5636446726)
for the retraction — the invalid data is no longer published, but remains visible in the
[gist](https://gist.github.com/koriym/db1c3b8bccd8c7c6bc18d4a2a51c622c)'s revision history.

**Three-way comparison** (n=20 rotations, fixed order reflection → serialize → compiled, one
php-fpm restart, one warmup request per endpoint discarded before rotations began — relevant
bytecode was warmed before recorded samples):

| Strategy | Median | IQR |
|---|---|---|
| reflection | 311.27 ms | [310.88, 312.21] |
| serialize | 7.93 ms | [7.65, 8.11] |
| compiled | 1.34 ms | [1.24, 1.36] |

This table's `serialize` row and the paired comparison below time the identical
`serve_serialize()` region (blob read, `unserialize()`, `SchemeCollectionInterface`,
`AppInterface`) — same code, same graph; `reflection`/`compiled` here use separate endpoint
scripts. This batch's `serialize` median (7.93 ms) is lower than the paired run's after-arm
`total_ms` median below (9.95 ms) by about 25%, and it's a directional, explainable gap rather
than symmetric noise: `fpm_restart` fully stops and restarts the php-fpm master, which tears down
and rebuilds OPcache's shared memory. This table restarts the master once for the whole
60-request batch (1 warmup per endpoint, then 20 rotations) — its `serialize` row runs on OPcache
SHM that has also been populated by up to 19 prior `reflection`/`compiled` rotations (plus that
rotation's own `reflection` hit). The paired run
restarts the master before every single recorded sample (3 discarded warmups each, 40 restarts
total), so its `serialize` measurements never get that cross-endpoint SHM warmth. The three-way
`serialize` number is biased low relative to the paired run for this reason, not measurement
error.

Ratios within this table only (same batch): reflection/serialize 39.2×, reflection/compiled
231.7× (per-rotation median, IQR [228.3, 253.6]), serialize/compiled 5.9×.

**[PR #354](https://github.com/ray-di/Ray.Di/pull/354)'s effect on the serialize strategy** — paired before/after (n=20 pairs; a full php-fpm
master restart and 3 discarded warmup requests between each swap of `vendor/ray/di`, a separate
protocol from the three-way run above):

| Phase | Median diff (after − before) | IQR |
|---|---|---|
| `unserialize()` only | -5.63 ms | [-5.91, -4.98] |
| Total root acquisition | -5.14 ms | [-5.50, -4.56] |

All 20/20 pairs improved (33.9% median reduction in total root-acquisition cost; 41.6% on
`unserialize()` alone, 40.3% when the tiny blob-read step is folded in as "read+unserialize"). The
~0.49 ms gap between the `unserialize()`-only row and the total is downstream first-touch
`ReflectionParameter` rebuild cost. It doesn't split evenly across the medians of the two
downstream phases shown below (medians aren't additive) — the mean reconciliation is exact
instead: -5.461 ms (`unserialize()`) + 0.053 ms (`SchemeCollectionInterface`) + 0.337 ms
(remaining `AppInterface` resolution) = -5.071 ms (total mean). Classes declared during
resolution rise from 39 to 68 as bindings untouched by the old eager rebuild get built lazily
instead. What changes is how many classes
`unserialize()` actually needs to declare in the first place (710 → 61), by deferring
`ReflectionParameter` rebuilding in `Argument`/`InjectionPoint` until a dependency is actually
used. Full phase breakdown, commit
SHAs, and raw per-cycle data:
[ray-di/Ray.Compiler#135](https://github.com/ray-di/Ray.Compiler/issues/135#issuecomment-5636446726)
(comment; raw data:
[gist](https://gist.github.com/koriym/db1c3b8bccd8c7c6bc18d4a2a51c622c)).

**Single first-hit observations, supplementary only, not used in either table above**:
reflection's first hit after the php-fpm restart was 482.62 ms — a genuinely cold observation,
above its warm median. serialize's first hit was 9.11 ms and compiled's was 4.71 ms, but neither
is a clean cold measurement — each ran in its own fresh process (`pm.max_requests=1`), but
reflection's hit had already populated shared OPcache, so only compiled's own script bytecode was
still uncompiled.

Separately, from the paired run's own first request after each restart — the first of the three
discarded warmup requests, not the three-way batch above (a different protocol) — paired, n=20:
total diff median -83.76 ms (IQR [-84.27, -82.75]), 20/20 negative — the deferred rebuild
also removes a large share of the true cold path's bytecode-compilation load.

Neither table above is comparable to the historical cold single-run table above it (different
app-federation state and pre-fix code). Both tables share the same caveats as the linked issue
comment: single machine, single app, no formal significance testing, no CPU/throughput
measurement.

The structure behind these numbers: `compiled` moves work from request time to build time, makes the
runtime cost proportional to what a request actually uses rather than to the total binding set, and
produces artifacts OPcache can share across processes — the same principle OPcache itself applies to
PHP code. Under php-fpm this is decisive, because per-process work is a per-request tax. In
long-lived workers (Swoole, RoadRunner) that cost is amortized over the worker lifetime instead.
