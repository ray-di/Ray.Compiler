<?php

/**
 * DI strategy benchmark — COMPILE TEST ONLY.
 *
 * Compares three strategies for building objects from a Ray.Di module:
 *   1. reflection : Ray\Di\Injector            (runtime reflection, warm Container)
 *   2. serialize  : serialize/unserialize Injector (warm Container restored from a blob)
 *   3. compiled   : Ray\Compiler\CompiledInjector (pre-compiled PHP scripts)
 *
 * It measures the two regimes that matter:
 *   - cold start    : build the Container once (reflection) / unserialize (serialize) / require (compiled)
 *   - steady state  : build a PROTOTYPE object repeatedly (the "build-many" regime, e.g. Grapher / entity hydration)
 *
 * NOT part of the library. Not autoloaded. Do not use in production.
 * See benchmark/README.md.
 *
 * Run with realistic settings (Xdebug off, OPcache on):
 *   php -d xdebug.mode=off -d opcache.enable_cli=1 -d opcache.validate_timestamps=0 benchmark/di_benchmark.php
 */

declare(strict_types=1);

use Ray\Compiler\CompiledInjector;
use Ray\Compiler\Compiler;
use Ray\Compiler\FakeCarInterface;
use Ray\Compiler\FakeCarModule;
use Ray\Di\Injector;

require __DIR__ . '/../vendor/autoload.php';

const ITERATIONS = 50000;

$interface = FakeCarInterface::class;
$tmp = sys_get_temp_dir() . '/ray_compiler_bench_' . getmypid();
$aopDir = $tmp . '/aop';
$diDir = $tmp . '/di';
@mkdir($aopDir, 0777, true);
@mkdir($diDir, 0777, true);

/** @return array{0: float, 1: mixed} elapsed milliseconds and the callback result */
function measure(callable $fn): array
{
    $start = hrtime(true);
    $result = $fn();

    return [(hrtime(true) - $start) / 1e6, $result];
}

/** @return float microseconds per operation */
function steady(callable $fn, int $iterations): float
{
    for ($i = 0; $i < 2000; $i++) {
        $fn(); // warm up
    }

    $start = hrtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $fn();
    }

    return (hrtime(true) - $start) / 1e3 / $iterations;
}

// --- 1. reflection: build the Container once, then build prototypes repeatedly ---
[$reflectColdMs, $injector] = measure(static fn (): Injector => new Injector(new FakeCarModule(), $aopDir));
$reflectSteadyUs = steady(static fn () => $injector->getInstance($interface), ITERATIONS);

// --- 2. serialize: cache the warm injector, restore it from a blob ---
[$serializeMs, $blob] = measure(static fn (): string => serialize($injector));
$blobKb = strlen($blob) / 1024;
[$unserializeMs, $restored] = measure(static fn () => unserialize($blob));
assert($restored instanceof Injector);
$serializeSteadyUs = steady(static fn () => $restored->getInstance($interface), ITERATIONS);

// --- 3. compiled: compile offline, then build prototypes from scripts ---
// CRITICAL: the generated scripts must be served from OPcache to be representative
// of production. OPcache refuses to cache files younger than
// opcache.file_update_protection (default 2s), so a benchmark that compiles and
// measures immediately re-parses every script on every require and makes "compiled"
// look ~5x slower than it really is. Age the scripts past that window first.
[$compileMs] = measure(static fn () => (new Compiler())->compile(new FakeCarModule(), $diDir));
$scriptCount = count((array) glob($diDir . '/*.php'));
$opcacheOn = function_exists('opcache_get_status') && (bool) ini_get('opcache.enable_cli');
// OPcache refuses files younger than opcache.file_update_protection (default 2s).
// Backdate the freshly written scripts so OPcache accepts them — this mirrors
// production, where scripts are compiled at deploy time, long before being served.
$backdated = time() - 3600;
foreach ((array) glob($diDir . '/*.php') as $file) {
    touch((string) $file, $backdated);
}
[$compiledColdMs, $compiled] = measure(static fn (): CompiledInjector => new CompiledInjector($diDir));
$compiledSteadyUs = steady(static fn () => $compiled->getInstance($interface), ITERATIONS);

// OPcache self-check — if the compiled scripts are not cached, the result is invalid.
$opcacheNote = 'OPcache: off (compiled re-parses every call — not representative)';
if ($opcacheOn) {
    $status = opcache_get_status(true);
    $cached = count(array_filter(array_keys((array) ($status['scripts'] ?? [])), static fn ($f): bool => str_contains((string) $f, $diDir)));
    $rate = (float) ($status['opcache_statistics']['opcache_hit_rate'] ?? 0.0);
    $opcacheNote = $cached > 0
        ? sprintf('OPcache: hit %.1f%%, %d compiled scripts cached (valid)', $rate, $cached)
        : 'OPcache: 0 compiled scripts cached — INVALID, scripts are re-parsing. Re-run with -d opcache.file_update_protection=0';
}

$peakMb = memory_get_peak_usage(true) / 1048576;

printf("Ray.Compiler DI benchmark — FakeCar prototype graph (ctor + 5 setters + AOP + singleton mirrors)\n");
printf("iterations=%d  php=%s  opcache=%d  xdebug=%d\n", ITERATIONS, PHP_VERSION, (int) ini_get('opcache.enable_cli'), (int) extension_loaded('xdebug'));
printf("%s\n\n", $opcacheNote);
printf("%-12s | %-22s | %-18s | %s\n", 'strategy', 'cold start', 'steady (build-many)', 'deploy artifact');
printf("%s\n", str_repeat('-', 86));
printf("%-12s | %-22s | %16.1f us | %s\n", 'reflection', sprintf('%.1f ms (build)', $reflectColdMs), $reflectSteadyUs, '-');
printf("%-12s | %-22s | %16.1f us | %s\n", 'serialize', sprintf('%.2f ms (unserialize)', $unserializeMs), $serializeSteadyUs, sprintf('%.0f KB blob (ser %.1f ms)', $blobKb, $serializeMs));
printf("%-12s | %-22s | %16.1f us | %s\n", 'compiled', sprintf('%.2f ms (new injector)', $compiledColdMs), $compiledSteadyUs, sprintf('%d scripts (compile %.0f ms)', $scriptCount, $compileMs));
printf("\npeak memory: %.1f MB\n", $peakMb);

// cleanup
array_map('unlink', (array) glob($tmp . '/{,*/}*.*', GLOB_BRACE));
@array_map('rmdir', [$aopDir, $diDir, $tmp]);
