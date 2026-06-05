# Changelog

All notable changes to this project are documented in this file.

## 0.6.0

### Added

- `Koriym\Dii\InjectableModule`: install it to bulk-bind every `Injectable` controller/command discovered in the given directories at compile time, so the bindings are AOP-compiled (#32). Already-bound classes are preserved, concrete subclasses of an injectable base are bound while abstract/non-instantiable classes are skipped, and a missing scan path throws `Koriym\Dii\Exception\DirectoryNotFound` during configuration.
- Built-in Grapher cache (`CacheInterface`, `FileCache`, `NullCache`, `GrapherCache`) to boost performance without the `doctrine/cache` dependency (#24).

### Changed

- Injectable controllers/commands must now be bound explicitly via untargeted binding; the previous runtime auto-bind fallback was removed so missing bindings fail fast and are AOP-compiled (#24).
- Support PHP 8.5 and ignore `E_DEPRECATED` so Yii deprecations do not fail the test suite (#24).
- Split the Unit and Integration test suites in CI (#24).
