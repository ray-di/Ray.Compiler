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

1. `Compiler`: Compiles Ray.Di bindings into PHP code
2. `CompiledInjector`: High-performance injector that executes pre-compiled code

### Basic Usage

Pre-compile your dependencies:

```php
use Ray\Compiler\Compiler;

$compiler = new Compiler();
$compiler->compile($module, $scriptDir);
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
use Ray\Compiler\Compiler;
]
$scripts = (new Compiler())->compile(
    new AppModule(),
    __DIR__ . '/di'
);

printf('Compiled %d files.', count($scripts));
```

Add compile script to your `composer.json`:

```json
{
    "scripts": {
        "post-install-cmd": ["php bin/compile.php"]
    }
}
```

## Docker Integration

Use multi-stage builds to maintain path consistency:

```dockerfile
# Build stage
FROM php:8.2-cli as builder
WORKDIR /app
COPY . .
RUN php bin/compile.php

# Production stage
FROM php:8.2-fpm
WORKDIR /app
COPY . .
COPY --from=builder /app/tmp/di/ ./tmp/di/
```

## Version Control

Add compile directory to `.gitignore`:

```
/tmp/di/
```

The compiled code should be treated as environment-specific build artifacts and not committed to version control.
