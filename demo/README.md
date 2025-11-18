# Ray.Compiler Demo

This demo shows how Ray.Compiler pre-compiles dependency injection bindings into executable PHP scripts.

## Architecture

Ray.Compiler uses a **two-phase architecture**:

### Compile-time (Development/Build)
- Uses Ray.Di's full dependency resolution
- Analyzes bindings, constructs dependency graph
- Generates optimized PHP scripts

### Runtime (Production)
- Uses minimal `CompiledInjector`
- Executes pre-compiled scripts
- **No reflection, no dependency resolution**

## Files

- `GreeterInterface.php` - Service interface
- `Greeter.php` - Service implementation
- `AppModule.php` - Dependency injection bindings
- `compile.php` - Compilation script (run at build time)
- `run.php` - Execution script (uses compiled scripts)

## Usage

### Step 1: Compile (Build Time)

```bash
php demo/compile.php
```

This generates optimized PHP scripts in `demo/.compiled/`:
- `Ray_Compiler_Demo_GreeterInterface-.php` (`_` replaces namespace `\`, trailing `-` indicates unnamed binding)
- Other dependency scripts

### Step 2: Run (Runtime)

```bash
php demo/run.php
```

Output:
```
Hello, World!
Hello, Ray.Compiler!

✓ Using pre-compiled dependency injection!
✓ No reflection, no dependency resolution at runtime
✓ Check demo/.compiled/ for generated PHP scripts
```

## Inspecting Generated Code

After compilation, check the generated scripts:

```bash
cat demo/.compiled/Ray_Compiler_Demo_GreeterInterface-.php
```

You'll see plain PHP code with direct instantiation (`new Greeter()`) and simple return statements - no reflection, no dependency resolution logic!

## Benefits

1. **Performance**: No runtime reflection or dependency resolution
2. **Zero Overhead**: Compiled scripts are simple `new` and `return` statements
3. **Debuggable**: Generated code is readable PHP
4. **Production Ready**: No heavy DI framework in production

## How It Works

```
┌─────────────────┐         ┌──────────────┐
│  Ray.Di Module  │────────▶│   Compiler   │
│  (Bindings)     │         │              │
└─────────────────┘         └──────┬───────┘
                                   │
                                   ▼
                            ┌──────────────┐
                            │  PHP Scripts │
                            │ (Optimized)  │
                            └──────┬───────┘
                                   │
                                   ▼
                            ┌──────────────────┐
                            │ CompiledInjector │
                            │  (Lightweight)   │
                            └──────────────────┘
```

## Next Steps

- Add more complex dependencies (interfaces, providers, AOP)
- Try scopes (singleton vs prototype)
- Explore assisted injection
- Use with your real application modules
