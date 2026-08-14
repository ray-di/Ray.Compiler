# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.16.0] - 2026-08-14

### Added
- Accept `phar://` script directories — `realpath()` cannot resolve stream URIs, so validation falls back to `is_dir()`/`is_readable()`; AOT scripts can now boot from inside a phar archive [#144]

## [1.15.0] - 2026-08-08

### Added
- Add `CompiledInjector::warmup()` — eagerly instantiates every singleton listed in the compiler-generated `singletons.json`, closing the lazy-initialization race window in coroutine runtimes (Swoole, OpenSwoole); call once at worker start. Not needed under PHP-FPM [#137] [#140]
- Add `SingletonRequiresInjectionPoint` exception — `compile()` rejects singletons whose construction needs a caller-supplied injection point (first-consumer-wins bindings); use prototype scope instead [#140]
- Add `InjectionPointNotAvailable` exception — resolving an injection-point binding without a consumer context fails with a dedicated exception instead of a bare `TypeError` [#140]
- Add `SingletonsFileNotFound` exception — `warmup()` fails loudly when the singleton metadata is missing (recompile) [#140]

### Changed
- Reject an unsafe dependency index instead of encoding it; escape the index and export serialized values in generated code [#139]
- Generated scripts no longer carry `// prototype` / `// singleton` scope comments; the `$singletons` write line already expresses the scope [#140]

### Fixed
- Align the singleton PostConstruct lifecycle with Ray.Di: the injector caches itself, and a failed setter/`setContext()` rolls back the provisional cache entry instead of leaving a half-initialized singleton behind [#138]

## [1.14.0] - 2026-07-12

### Changed
- Skip `file_exists()` guard on the hot path in `prototype()`/`singleton()` — an OPcache-cached `require` performs no filesystem access, so the guard was the only `stat()` syscall left [#134]
- `prototype()`/`singleton()` now `require` the script directly without try/catch; a missing compiled script is a build invariant violation that surfaces as PHP 8's native catchable `Error` [#134]
- Deprecate `ScriptFileNotFound` exception (kept for BC) [#134]
- Remove redundant `realpath()` call in `CompiledInjector::getInstance()` [#134]
- Resolve `#[ScriptDir]` to `__DIR__` instead of baking the compile-time absolute path [#136]

### Added
- Add performance documentation (`docs/performance.md`) covering OPcache prerequisites and benchmarking methodology [#134]
- Add self-validating DI benchmark under `demo/benchmark/` [#134]

### Fixed
- Add `--memory-limit=256M` to phpstan in composer scripts

## [1.13.1] - 2025-12-04

### Fixed
- Fix typo in variable and directory name in DependencySaver (`qualifer` → `qualifier`) [#131]

## [1.13.0] - 2025-11-18

### Changed
- Require PHP 8.2+ (drop PHP 7.x support) [#129]
- Remove deprecated Doctrine annotations

### Added
- Add demo for Ray.Compiler usage [#130]

### Removed
- Remove unused exception classes

## [1.12.3] - 2025-11-13

### Fixed
- Additional array type handling improvements [#128]
- Add missing EOL at end of test files

## [1.12.2] - 2025-11-13

### Fixed
- Fix array handling in CompileVisitor to serialize arrays instead of using var_export() [#125]
- Fix fatal error when arrays contain objects (e.g., `[new Object(), 'method']`)

## [1.12.1] - 2025-10-27

### Fixed
- Remove incorrect @deprecated annotation from DiCompileModule

## [1.12.0] - 2025-10-26

### Changed
- Use `override()` in Compiler and remove BuiltinModule from CompilerModule
- Use `override()` instead of `install()` for CompilerModule [#116]
- Move Code4Dependency to src-deprecated and exclude from Psalm

### Added
- Introduce Code4Dependency class and incorporate into DependencyCode
- Add Symfony PHP 8.3 polyfill and Override attributes
- Add PHP 8.5 support to CI
- Add LLM documentation for Ray.Compiler [#118]

### Fixed
- Remove duplicated class [#116]
- Fix PHP 7.2 compatibility in CompilerModuleOverrideTest
- Use domain types from Types.php and fix PHPStan type errors

## [1.11.0] - 2024-12-XX

Previous releases (prior to 1.11.0) are not documented in this changelog.

[1.16.0]: https://github.com/ray-di/Ray.Compiler/compare/1.15.0...1.16.0
[1.15.0]: https://github.com/ray-di/Ray.Compiler/compare/1.14.0...1.15.0
[1.14.0]: https://github.com/ray-di/Ray.Compiler/compare/1.13.1...1.14.0
[1.13.1]: https://github.com/ray-di/Ray.Compiler/compare/1.13.0...1.13.1
[1.13.0]: https://github.com/ray-di/Ray.Compiler/compare/1.12.3...1.13.0
[1.12.3]: https://github.com/ray-di/Ray.Compiler/compare/1.12.2...1.12.3
[1.12.2]: https://github.com/ray-di/Ray.Compiler/compare/1.12.1...1.12.2
[1.12.1]: https://github.com/ray-di/Ray.Compiler/compare/1.12.0...1.12.1
[1.12.0]: https://github.com/ray-di/Ray.Compiler/compare/1.11.0...1.12.0
[1.11.0]: https://github.com/ray-di/Ray.Compiler/releases/tag/1.11.0
