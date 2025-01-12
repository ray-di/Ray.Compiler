# Ray.Compiler

## Dependency Injection Compiler

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ray-di/Ray.Di/badges/quality-score.png?b=2.x)](https://scrutinizer-ci.com/g/ray-di/Ray.Di/?branch=2.x)
[![codecov](https://codecov.io/gh/ray-di/Ray.Di/branch/2.x/graph/badge.svg?token=KCQXtu01zc)](https://codecov.io/gh/ray-di/Ray.Di)
[![Type Coverage](https://shepherd.dev/github/ray-di/Ray.Di/coverage.svg)](https://shepherd.dev/github/ray-di/Ray.Di)
[![Continuous Integration](https://github.com/ray-di/Ray.Di/actions/workflows/continuous-integration.yml/badge.svg?branch=2.x)](https://github.com/ray-di/Ray.Di/actions/workflows/continuous-integration.yml)

Ray.Compiler compiles Ray.Di bindings into PHP code, providing a performance boost that makes Dependency Injection couldn't be any faster.

## Production Usage

For production, use `CompiledInjector` with pre-compiled dependencies:

```php
// 1. Compile dependencies (during deployment or composer install)
(new Compiler)->compile($module, $scriptDir);

// 2. Use compiled injector in application
$injector = new CompiledInjector($scriptDir);
```

The `CompiledInjector` executes pre-compiled PHP code and is significantly faster than the standard Ray.Di injector.

## Development Usage

For development, use `CompileInjector` which handles compilation automatically:

```php
// Compiles and injects on-demand
$injector = new CompileInjector($scriptDir, new DevModule);
```

## Performance Comparison

- Ray.Di injector (memory-based resolution)
```php
$injector = new Injector(new CarModule);
```

- CompiledInjector (pre-compiled, fastest)
```php
$injector = new CompiledInjector($scriptDir);
```

- CompileInjector (development-friendly)
```php
$injector = new CompileInjector($scriptDir, $module);
```

## Manual Compilation

You can compile dependencies manually using the Compiler:

```php
$compiler = new Compiler();
$compiler->compile($module, $scriptDir);
```

This is useful for:
- Deployment scripts
- Composer post-install scripts
- CI/CD pipelines

## Object Graph Visualization

Object graph can be visualized with `dumpGraph()`. Graph HTML files will be output at `graph` folder under `$scriptDir`.

```php
$compiler = new Compiler();
$compiler->compile($module, $scriptDir);
$compiler->dumpGraph();
```

View the generated graph:
```
open $scriptDir/graph/Ray_Compiler_FakeCarInterface-.html
```

## Production Configuration

For production, it's recommended to:

1. Pre-compile all dependencies during deployment
2. Use `CompiledInjector` exclusively
3. Configure proper error handling for missing dependencies

Example production setup:

```php
// composer post-install script
(new Compiler)->compile(new ProductionModule(), __DIR__ . '/tmp/di');

// application bootstrap
$injector = new CompiledInjector(__DIR__ . '/tmp/di');
