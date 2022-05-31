# Ray.Compiler

## Dependency Injection Compiler

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ray-di/Ray.Di/badges/quality-score.png?b=2.x)](https://scrutinizer-ci.com/g/ray-di/Ray.Di/?branch=2.x)
[![codecov](https://codecov.io/gh/ray-di/Ray.Di/branch/2.x/graph/badge.svg?token=KCQXtu01zc)](https://codecov.io/gh/ray-di/Ray.Di)
[![Type Coverage](https://shepherd.dev/github/ray-di/Ray.Di/coverage.svg)](https://shepherd.dev/github/ray-di/Ray.Di)
[![Continuous Integration](https://github.com/ray-di/Ray.Di/actions/workflows/continuous-integration.yml/badge.svg?branch=2.x)](https://github.com/ray-di/Ray.Di/actions/workflows/continuous-integration.yml)

Ray.Compiler compiles Ray.Di bindings into PHP code, providing a performance boost that makes Dependency Injection couldn't be any faster.

##  Script Injector

`ScriptInjector` has the same interface as Ray.Di Injector; whereas Ray.Di Injector resolves dependencies based on memory bindings, ScriptInjector executes pre-compiled PHP code and is faster.

Ray.Di injector
```php
$injector = new Injector(new CarModule); // Ray.Di injector
```

Ray.Compiler injector
```php
$injector = new ScriptInjector($tmpDir, fn => new CarModule);
```

## Precompile

You will want to compile all dependencies into code before deploying the production. The `DiCompiler` will compile all bindings into PHP code.

```php
$compiler = new DiCompiler(new CarModule, $tmpDir);
$compiler->compile();
```

## Object graph visualization

Object graph can be visualized with `dumpGraph()`.
Graph HTML files will be output at `graph` folder under `$tmpDir`.

```php
$compiler = new DiCompiler(new Module, $tmpDir);
$compiler->compile();
$compiler->dumpGraph();
```

```
open tmp/graph/Ray_Compiler_FakeCarInterface-.html
```

## CompileInjector

The `CompileInjector` gives you the best performance in both development (x2) and production (x10) by switching two injector.

Get the injector by specifying the binding and cache, depending on the execution context of the application.

```php
$injector = new CompileInjector($tmpDir, $injectorContext);
```

`$injectorContext` example: 

 * [dev](docs/exmaple/DevInjectorContext.php)
 * [prod](docs/exmaple/ProdInjectorContext.php)

The `__invoke()` method prepares the modules needed in that context.
The `getCache()` method specifies the cache of the injector itself.

Install `DiCompileModule` in the context for production. The injector is more optimized and dependency errors are reported at compile-time instead of run-time.
