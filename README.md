# Ray.Compiler

<<<<<<< Updated upstream
## DI and AOP framework for PHP

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ray-di/Ray.Di/badges/quality-score.png?b=2.x)](https://scrutinizer-ci.com/g/ray-di/Ray.Di/?branch=2.x)
[![codecov](https://codecov.io/gh/ray-di/Ray.Di/branch/2.x/graph/badge.svg?token=KCQXtu01zc)](https://codecov.io/gh/ray-di/Ray.Di)
[![Type Coverage](https://shepherd.dev/github/ray-di/Ray.Di/coverage.svg)](https://shepherd.dev/github/ray-di/Ray.Di)
[![Continuous Integration](https://github.com/ray-di/Ray.Di/actions/workflows/continuous-integration.yml/badge.svg?branch=2.x)](https://github.com/ray-di/Ray.Di/actions/workflows/continuous-integration.yml)
=======
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ray-di/Ray.Compiler/badges/quality-score.png?b=1.x)](https://scrutinizer-ci.com/g/ray-di/Ray.Compiler/?branch=1.x)
[![codecov](https://codecov.io/gh/ray-di/Ray.Compiler/branch/1.x/graph/badge.svg?token=KCQXtu01zc)](https://codecov.io/gh/ray-di/Ray.Compiler)
[![Type Coverage](https://shepherd.dev/github/ray-di/Ray.Compiler/coverage.svg)](https://shepherd.dev/github/ray-di/Ray.Compiler)
[![Continuous Integration](https://github.com/ray-di/Ray.Compiler/actions/workflows/continuous-integration.yml/badge.svg?branch=1.x)](https://github.com/ray-di/Ray.Compiler/actions/workflows/continuous-integration.yml)
>>>>>>> Stashed changes
[![Total Downloads](https://poser.pugx.org/ray/di/downloads)](https://packagist.org/packages/ray/di)

Ray.Compiler compiles Ray.Di bindings into PHP code, providing a performance boost that makes Dependency Injection couldn't be any faster.

##  Script Injector

`ScriptInjector` has the same interface as Ray.Di Injector; whereas Ray.Di Injector resolves dependencies based on memory bindings, ScriptInjector executes pre-compiled PHP code and is faster The following is an example.

```php
$injector = new ScriptInjector(
    $tmpDir,
    function () {
        return new CarModule;
    }
);
```

## Precompile

You will want to compile all dependencies into code before deploying the production. The `DiCompiler` will compile all bindings into PHP code.

```php
$tmpDir = __DIR__ . '/tmp';
$compiler = new DiCompiler(new Module, $tmpDir);
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

## CachedInejctorFactory

The `CachedInejctorFactory` gives you the best performance in both development (x2) and production (x10) by switching two injector.

See [CachedInjectorFactory](https://github.com/ray-di/Ray.Compiler/issues/75).

