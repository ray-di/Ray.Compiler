# DI strategy benchmark

> [!WARNING]
> **Compile-test only — not part of the library.** It is not autoloaded and is never
> used in production. It exists solely to measure the compiled injector against the
> reflection and `serialize()`d injectors.

`di_benchmark.php` builds the same object graph three ways — `Ray\Di\Injector`
(reflection), a `serialize()`d injector, and `Ray\Compiler\CompiledInjector` — and reports
cold-start and steady-state (per-build) cost.

## Run

Requires `vendor/` (`composer install`). Use production-like settings (Xdebug off, OPcache on):

```bash
php -d xdebug.mode=off -d opcache.enable_cli=1 -d opcache.validate_timestamps=0 benchmark/di_benchmark.php
```

## Reading the output

The first line is an **OPcache self-check** — the numbers are only trustworthy when it says `(valid)`:

```text
OPcache: hit 100.0%, 9 compiled scripts cached (valid)
```

If it prints `INVALID`, OPcache is not caching the freshly generated scripts, so `compiled` is
re-parsing and looks several times slower than it really is. The script back-dates the generated
files to avoid this automatically; if it still reports `INVALID`, re-run with
`-d opcache.file_update_protection=0`.

## Background

For the three strategies, why OPcache is the prerequisite, the measured results, and the full list
of benchmarking pitfalls, see **[docs/performance.md](../docs/performance.md)**.
