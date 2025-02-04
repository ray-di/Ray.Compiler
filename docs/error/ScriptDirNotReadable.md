# ScriptDirNotReadable Error

This error occurs when Ray.Compiler cannot read the script directory specified for compiled dependencies.

## Common Scenarios

### 1. Local Development Environment

If you encounter this error in your local development environment, check:

```php
// Example error case
$injector = new CompiledInjector(__DIR__ . '/tmp/di');
```

#### Possible Issues:
- Directory does not exist
- Directory permissions are incorrect
- Path is misspecified relative to the current file

#### Solutions:

1. Ensure the directory exists:
```bash
mkdir -p tmp/di
```

2. Check directory permissions:
```bash
chmod 755 tmp/di
```

3. Use absolute paths in your code:
```php
// Good
$scriptDir = __DIR__ . '/tmp/di';
// or
$scriptDir = '/absolute/path/to/tmp/di';
```

### 2. Docker Environment

When using Docker, this error often indicates a path mismatch between build and runtime environments.

#### Example Dockerfile with Issues:
```dockerfile
# Problematic setup
FROM php:8.2-cli as builder
WORKDIR /build
RUN php bin/compile.php  # Compiles to /build/tmp/di

FROM php:8.2-fpm
WORKDIR /app
COPY --from=builder /build/tmp/di ./tmp/di  # Path mismatch!
```

#### Solution:
Use consistent paths across all stages:
```dockerfile
# Correct setup
FROM php:8.2-cli as builder
WORKDIR /app
RUN php bin/compile.php  # Compiles to /app/tmp/di

FROM php:8.2-fpm
WORKDIR /app
COPY --from=builder /app/tmp/di /app/tmp/di  # Same path
```

## Quick Checklist

1. ✓ Directory exists
2. ✓ Directory is readable
3. ✓ Using absolute paths
4. ✓ Paths match between environments
5. ✓ Correct permissions are set

## Still Having Issues?

If you've checked all the above and still encountering issues:
- Check your container logs for permission errors
- Verify the directory structure in your running container
- Ensure your deployment process maintains path consistency

For more help, visit our [GitHub issues page](https://github.com/ray-di/Ray.Compiler/issues)
