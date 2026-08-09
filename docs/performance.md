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
| **reflection** (`Ray\Di\Injector`) | Build the whole `Container` from the module (annotation reading, binding resolution, AOP weaving), then instantiate via reflection | Container build is **hundreds of ms** for a large app, paid **every process** | Dev only. Untenable for shared-nothing (php-fpm). |
| **serialize** (`serialize()` the injector, `unserialize()` per request) | `unserialize()` reconstructs the `Container` object graph, then instantiate via reflection | Dominated by **`unserialize()`** — paid **every process** (a blob is data; it cannot live in shared OPcache) | Scales linearly with the binding set. |
| **compiled** (`CompiledInjector`) | `require` the few pre-generated scripts the graph touches, run flat `new`/setter code | **Lazy** — only the needed scripts; opcodes served from OPcache | Scripts/classes can be **preloaded** into shared memory across php-fpm workers. |

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
`realpath($this->scriptDir)` — already canonicalised in the constructor — was removed.

## Benchmarking correctly

`demo/benchmark/di_benchmark.php` compares the three strategies and **prints the OPcache hit rate so you can
tell a valid run from a bogus one**. Pitfalls it (and you) must control for:

1. **OPcache must actually cache the compiled scripts.** Back-date generated scripts
   (`touch($file, time() - 3600)`) or run with `-d opcache.file_update_protection=0`. A `sleep()` does
   **not** work on the CLI — OPcache's age check uses the request start time, not wall-clock. Always
   confirm the benchmark reports `(valid)` / a non-zero cached-script count; if it prints `INVALID`,
   the numbers are re-parsing artifacts.
2. **Disable Xdebug** (`-d xdebug.mode=off`) — it inflates everything.
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

## Measured at production scale

The fixture above is small. These numbers are from a large production BEAR.Sunday application
(~600 compiled DI scripts; PHP 8.3, OPcache on, Xdebug off), measuring the per-process cost to
acquire the application root — i.e. one cold php-fpm request [#135]:

| Strategy | Cost | Breakdown |
|---|---|---|
| reflection | **~0.4–0.6 s** | Container build (annotation reading + binding analysis + AOP weaving) dominates |
| serialize | **~29 ms** | ≈ `unserialize()` of the whole Container (~25 ms, mostly class autoload) + build (~4 ms); blob ~0.5 MB |
| compiled | **~5 ms** | lazy `require` of only the scripts the root touches (~30 of ~600); offline compile ~0.5 s |

- **reflection** rebuilds the entire Container every process — untenable for shared-nothing. This is
  the cost the other two exist to avoid.
- **serialize** scales ~linearly with the binding set, and the blob cannot live in shared OPcache —
  it is re-`unserialize()`d per process.
- **compiled** loads only what a request needs (sub-linear), and its scripts can be preloaded into
  shared OPcache across workers.

In a warm worker `unserialize()` drops to ~1–2 ms (classes already loaded), so warm serialize and
compiled both land in the low-millisecond range; the dramatic gap is the cold first request and the
linear-vs-sub-linear scaling. These are indicative single-run figures on one machine; treat warm
numbers as approximate.

The structure behind these numbers: `compiled` moves work from request time to build time, makes the
runtime cost proportional to what a request actually uses rather than to the total binding set, and
produces artifacts OPcache can share across processes — the same principle OPcache itself applies to
PHP code. Under php-fpm this is decisive, because per-process work is a per-request tax. In
long-lived workers (Swoole, RoadRunner) that cost is amortized over the worker lifetime instead.
