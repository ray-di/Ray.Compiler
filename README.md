<img src="https://ray-di.github.io/images/logo.svg" width=160  alt="logo">

# Ray.Compiler

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ray-di/Ray.Compiler/badges/quality-score.png?b=1.x)](https://scrutinizer-ci.com/g/ray-di/Ray.Compiler/?branch=1.x)
[![codecov](https://codecov.io/gh/ray-di/Ray.Compiler/branch/1.x/graph/badge.svg?token=KCQXtu01zc)](https://codecov.io/gh/ray-di/Ray.Compiler)
[![Type Coverage](https://shepherd.dev/github/ray-di/Ray.Compiler/coverage.svg)](https://shepherd.dev/github/ray-di/Ray.Compiler)
[![Continuous Integration](https://github.com/ray-di/Ray.Compiler/actions/workflows/continuous-integration.yml/badge.svg?branch=1.x)](https://github.com/ray-di/Ray.Compiler/actions/workflows/continuous-integration.yml)

Pre-compile Ray.Di bindings to PHP code for maximum performance. The compiled injector runs faster than the standard injector by avoiding runtime reflection and binding resolution.

## Installation

```bash
composer require ray/compiler
```

## Usage

Ray.Compiler provides two main components:

1. **`Compiler`**: Compiles Ray.Di bindings into PHP code.
2. **`CompiledInjector`**: High-performance injector that executes pre-compiled code.

### Basic Usage

Pre-compile your dependencies:

```php
use Ray\Compiler\Compiler;

$compiler = new Compiler();
// Compile Ray.Di bindings to PHP files
$compiler->compile(
    $module,    // AbstractModule: Your application's module
    $scriptDir  // string: Directory path where compiled PHP files will be generated
);
```

Use the compiled injector:

```php
use Ray\Compiler\CompiledInjector;

$injector = new CompiledInjector($scriptDir);
$instance = $injector->getInstance(YourInterface::class);
```

### Compiler Integration

Create a compile script:

```php
try {
    $scripts = (new Compiler())->compile(
        new AppModule(),
        __DIR__ . '/di'
    );
    printf("Compiled %d files.\n", count($scripts));
} catch (CompileException $e) {
    fprintf(STDERR, "Compilation failed: %s\n", $e->getMessage());
    exit(1);
}
```

Add compile script to your `composer.json`:

```json
{
    "scripts": {
        "post-install-cmd": ["php bin/compile.php"]
    }
}
```

### Warming Up Singletons on Coroutine Runtime Servers

Compilation also generates `singletons.json`, a list of singleton bindings that can be instantiated without caller context. `CompiledInjector::warmup()` instantiates them all eagerly:

```php
$injector = new CompiledInjector($scriptDir);
$injector->warmup(); // call once at worker startup
```

In a long-lived or coroutine runtime (Swoole, OpenSwoole), lazy singleton initialization can race when construction yields, producing duplicate instances. Calling `warmup()` before concurrent request handling begins removes that window.

This is only needed for runtimes that handle requests concurrently within one process. Standard PHP-FPM workers normally process one request at a time, so no warm-up is required there.

An injection-point-dependent singleton would capture whichever consumer constructs it first, making the shared instance order-dependent. Such bindings must use prototype scope or remove the injection-point dependency.

- Compilation throws `SingletonRequiresInjectionPoint` for an injection-point-dependent singleton.
- `warmup()` throws `SingletonsFileNotFound` if `singletons.json` is missing. Recompile with the current Ray.Compiler version.

## Version Control

Compiled DI code is considered an environment-specific build artifact and **should not** be committed to version control. This approach ensures that your repository remains clean and build artifacts do not cause merge conflicts or unexpected behavior across different environments.

Add the compile directory to your `.gitignore`:

```gitignore
/tmp/di/
```

## Documentation

- **[Performance & OPcache](docs/performance.md)** - Why the compiled injector is fast, the OPcache prerequisite, and how to benchmark it correctly
- **[LLM Documentation](https://ray-di.github.io/Ray.Compiler/llms.txt)** - Brief documentation optimized for LLMs
- **[Complete LLM Documentation](https://ray-di.github.io/Ray.Compiler/llms-full.txt)** - Full documentation with architecture details
