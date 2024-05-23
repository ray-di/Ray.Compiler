# Ray.Compiler

## Dependency Injection Compiler

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ray-di/Ray.Di/badges/quality-score.png?b=2.x)](https://scrutinizer-ci.com/g/ray-di/Ray.Di/?branch=2.x)
[![codecov](https://codecov.io/gh/ray-di/Ray.Di/branch/2.x/graph/badge.svg?token=KCQXtu01zc)](https://codecov.io/gh/ray-di/Ray.Di)
[![Type Coverage](https://shepherd.dev/github/ray-di/Ray.Di/coverage.svg)](https://shepherd.dev/github/ray-di/Ray.Di)
[![Continuous Integration](https://github.com/ray-di/Ray.Di/actions/workflows/continuous-integration.yml/badge.svg?branch=2.x)](https://github.com/ray-di/Ray.Di/actions/workflows/continuous-integration.yml)

Ray.Compiler compiles Ray.Di bindings into PHP code, providing a performance boost that makes Dependency Injection couldn't be any faster.

```php
$injector = new CompileInjector($tmpDir, fn => new CarModule);
$car = $injector->getInstance(CarInterface::class);
```

## Precompile

You will want to compile all dependencies into code before deploying the production.

```php
$compiler = new Compiler($tmpDir, new CarModule);
$compiler->compile();
```
