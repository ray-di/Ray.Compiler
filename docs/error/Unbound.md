# Unbound Error

This error occurs when Ray.Compiler cannot find a binding for a requested dependency. It usually means either the dependency wasn't bound in your module, or the compiled file is missing.

## Common Scenarios

### 1. Missing Binding in Module

The most common cause is when a dependency is not bound in your module:

```php
class YourModule extends AbstractModule
{
    protected function configure()
    {
        // Missing binding for FooInterface
    }
}

// Later...
$instance = $injector->getInstance(FooInterface::class); // Throws Unbound
```

#### Solution:
Add the binding to your module:

```php
class YourModule extends AbstractModule
{
    protected function configure()
    {
        $this->bind(FooInterface::class)->to(Foo::class);
    }
}
```

### 2. Missing Compiled File

When using CompiledInjector, this error can occur if the compiled file is missing:

```php
$injector = new CompiledInjector('/app/tmp/di');
$instance = $injector->getInstance(FooInterface::class); // Throws Unbound if FooInterface.php is missing
```

#### Solutions:

1. Ensure compilation was successful:
```php
$compiler = new Compiler();
$compiler->compile($module, $scriptDir);
```

2. Check if the compiled file exists:
```php
// The file should exist:
/app/tmp/di/Your_Interface_class.php
```

3. If using Docker, verify the paths:
```dockerfile
# Ensure these paths match
COPY --from=builder /app/tmp/di/ /app/tmp/di/
```

### 3. Auto-Wiring Issues

When using constructor injection:

```php
class Foo
{
    public function __construct(BarInterface $bar)
    {
    }
}
```

#### Solutions:

1. Explicitly bind the dependency:
```php
$this->bind(BarInterface::class)->to(Bar::class);
```

2. Or use auto-wiring if the implementation class name matches:
```php
class BarInterface_Bar implements BarInterface
```

## Quick Checklist

1. ✓ Required bindings are defined in your module
2. ✓ Compilation was successful
3. ✓ Compiled files exist in the correct location
4. ✓ Paths are consistent between environments

## Related Errors

- [ScriptDirNotReadable](script-dir-not-readable.html) - If the compiler cannot read the output directory

## Still Having Issues?

If you've checked all the above and still encountering issues:
- Enable Ray.Di debugging to see binding resolution
- Check for typos in interface/class names
- Verify your module configuration
- Review the Ray.Di binding documentation

For more help, visit our [GitHub issues page](https://github.com/ray-di/Ray.Compiler/issues)
