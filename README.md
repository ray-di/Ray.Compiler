# Ray.Compiler

## Dependency Injection Compiler

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ray-di/Ray.Compiler/badges/quality-score.png?b=1.x)](https://scrutinizer-ci.com/g/ray-di/Ray.Compiler/?branch=1.x)
[![codecov](https://codecov.io/gh/ray-di/Ray.Compiler/branch/1.x/graph/badge.svg?token=KCQXtu01zc)](https://codecov.io/gh/ray-di/Ray.Compiler)
[![Type Coverage](https://shepherd.dev/github/ray-di/Ray.Compiler/coverage.svg)](https://shepherd.dev/github/ray-di/Ray.Compiler)
[![Continuous Integration](https://github.com/ray-di/Ray.Compiler/actions/workflows/continuous-integration.yml/badge.svg?branch=1.x)](https://github.com/ray-di/Ray.Compiler/actions/workflows/continuous-integration.yml)

Ray.Compiler compiles Ray.Di bindings into PHP code, providing a performance boost that makes dependency injection as fast as possible.

## Installation

```bash
composer require ray/compiler
```

## Overview

Ray.Compiler enhances Ray.Di by providing pre-compiled dependency injection, dramatically improving performance in production environments. It consists of two main components:

1. `Compiler`: Compiles Ray.Di bindings into optimized PHP code
2. `CompiledInjector`: High-performance injector that executes pre-compiled bindings

## Usage

### Production

For production environments, use the two-step process:

1. Compile your bindings (during deployment):

```php
use Ray\Compiler\Compiler;

$compiler = new Compiler();
$compiler->compile($module, $scriptDir);
```

2. Use CompiledInjector in your application:
```php
use Ray\Compiler\CompiledInjector;

$injector = new CompiledInjector($scriptDir);
$instance = $injector->getInstance(YourInterface::class);
```

Note: Ensure that `$scriptDir` is writable during compilation and add it to your `.gitignore` as these are generated files.

The `CompiledInjector` executes pre-compiled PHP code for maximum performance.

### Development

During development, you can use the same `CompiledInjector` as in production for consistency and better performance. Simply compile your bindings when needed:

```php
use Ray\Compiler\Compiler;

$compiler = new Compiler();
$compiler->compile(new DevModule(), __DIR__ . '/tmp/di');

// Use CompiledInjector just like in production
$injector = new CompiledInjector(__DIR__ . '/tmp/di');
```

## Performance Comparison

Ray.Di offers two types of dependency injection, optimized for different use cases:

1. CompiledInjector (Optimized for production)
```php
// Pre-compiled dependencies for maximum performance
$injector = new CompiledInjector($scriptDir);
```

2. Ray\Di\Injector (Standard resolution)
```php
// Memory-based dependency resolution
$injector = new Injector(new YourModule);
```

For production environments, `CompiledInjector` is recommended as it offers significant performance benefits through pre-compiled dependency resolution.

## Docker Integration

When using Docker, compile the dependencies inside the container during the build process:

```dockerfile
# Build stage
FROM composer:2 as vendor
COPY composer.json composer.lock /app/
COPY bin/compile.php /app/bin/
RUN composer install --no-dev --no-scripts

# Compile dependencies with the container's environment and paths
RUN php bin/compile.php

# Application stage
FROM php:8.2-alpine
COPY --from=vendor /app/vendor /app/vendor
COPY --from=vendor /app/tmp/di /app/tmp/di  # Compiled code with correct paths
```

This ensures:
- Dependencies are compiled with the container's paths and environment
- Each container rebuild generates fresh compiled code
- Production containers include only the necessary compiled code

## Important: Compilation and Version Control

⚠️ Never commit compiled code to your repository:
- Compiled code is environment-specific and should be generated during `composer install`
- Committing compiled code can leak sensitive information from your environment
- Different environments (dev, staging, prod) should generate their own compiled code

Instead:
1. Add your script directory to `.gitignore`:
```
/tmp/di/
```

2. Let each environment compile its own code during `composer install`:
```json
{
    "scripts": {
        "post-install-cmd": ["php bin/compile.php"]
    }
}
```

This ensures:
- Clean separation between environments
- Proper handling of sensitive configuration
- Correct environment-specific paths and settings

## Composer Integration

Create a compile script (`bin/compile.php`):

```php
<?php

use Ray\Compiler\Compiler;

require dirname(__DIR__) . '/vendor/autoload.php';

(new Compiler)->compile(new ProductionModule(), dirname(__DIR__) . '/tmp/di');
```

Add it to your `composer.json`:

```json
{
    "scripts": {
        "post-install-cmd": ["php bin/compile.php"]
    }
}
```

This ensures your dependencies are compiled during:
- `composer install`
- `composer update`

## Production Best Practices

1. Pre-compile all bindings during `composer install`
    - Ensures bindings are compiled with the correct environment settings
    - Handles environment-specific paths and configurations automatically
    - Keeps sensitive information secure by generating factory code only in the target environment
    - Never compile and commit code before deployment - let the target environment handle it
2. Use `CompiledInjector` exclusively in production
3. Set up proper error handling for missing dependencies
4. Keep the script directory (`$scriptDir`) outside of version control
    - Always add the compile directory to .gitignore
    - Treat compiled code as environment-specific build artifacts
