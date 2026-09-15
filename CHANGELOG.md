# Changelog

All notable changes to this project are documented in this file.

## Unreleased

### Fixed

- `InjectableModule`'s directory scanner mis-parsed several valid PHP shapes and silently dropped the `Injectable` binding for the affected class: group-use imports (`use A\{B, C};`), mixed function/class group-use (`use A\{function f, B};`), a `use function`/`use const` modifier applying to every comma-separated item in an ungrouped import list, bracketed `namespace X { ... }` blocks, a namespace `use` appearing after an earlier class in the same file, a trait-use as a body's first statement, and `implements namespace\Marker` (namespace-relative name resolution).
- `FileCache`: switched the cache-file hash from `crc32b` (32-bit) to `sha256` to avoid key collisions, and a corrupted/truncated cache file is now rebuilt instead of raising a fatal error.
- `GrapherCache`: a corrupted or malformed cache payload (including one whose `__wakeup()` throws) no longer emits a warning or propagates the exception; it now falls back to rebuilding the object graph.
- `Dii::registerSilentAutoLoader()` and `SilentAutoload::autoload()`: `error_reporting()` is now restored via `finally`, so a throwing autoloader no longer leaves warnings permanently suppressed process-wide.
- `Dii::createComponent()`: a `class` that does not exist now throws a clear `Koriym\Dii\Exception\Unloadable` instead of an `E_WARNING` followed by a `TypeError` from `in_array()`.

## 0.6.0

### Added

- `Koriym\Dii\InjectableModule`: install it to bulk-bind every `Injectable` controller/command discovered in the given directories at compile time, so the bindings are AOP-compiled (#32). Already-bound classes are preserved, concrete subclasses of an injectable base are bound while abstract/non-instantiable classes are skipped, and a missing scan path throws `Koriym\Dii\Exception\DirectoryNotFound` during configuration.
- Built-in Grapher cache (`CacheInterface`, `FileCache`, `NullCache`, `GrapherCache`) to boost performance without the `doctrine/cache` dependency (#24).

### Changed

- Injectable controllers/commands must now be bound explicitly via untargeted binding; the previous runtime auto-bind fallback was removed so missing bindings fail fast and are AOP-compiled (#24).
- Support PHP 8.5 and ignore `E_DEPRECATED` so Yii deprecations do not fail the test suite (#24).
- Split the Unit and Integration test suites in CI (#24).
