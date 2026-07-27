# Changelog

All notable changes to this project will be documented in this file.

This project follows semantic versioning once stable releases are published.

## [0.1.0] - 2026-07-27

### Added

- Initial Laravel package structure for `caminodeldev/laravel-api-scaffold`.
- Laravel package discovery through `ApiScaffoldServiceProvider`.
- Publishable configuration file: `config/api-scaffold.php`.
- Artisan command to inspect MySQL table metadata.
- Artisan command to generate an Eloquent model from a database table.
- Artisan command to generate a safe API scaffold from a database table.
- MySQL table inspector for columns, primary keys, unique columns, timestamps and soft deletes.
- Safe Eloquent model generation with sensitive columns excluded from `$fillable`.
- FormRequest generation for index, store and update flows.
- API Resource generation with sensitive columns excluded from output.
- Service layer generation for query, create, update and delete operations.
- Controller generation with controlled `try/catch` error handling.
- Dedicated route file generation through `routes/scaffolded-api.php`.
- Idempotent optional import block for `routes/api.php`.
- Dry-run mode to preview generated files without writing them.
- Explicit CRUD generation through `--crud`.
- Explicit delete generation through `--with-delete`.
- Configurable response envelope keys.
- Generated Feature test stub for scaffolded APIs.
- Unit tests for naming, sensitive column detection, file writing and MySQL inspection.
- GitHub Actions workflow for Composer validation and PHPUnit.

### Security

- Read-only API generation is the recommended default.
- Write operations require explicit opt-in.
- Delete operations require an additional explicit opt-in flag.
- Common secret-like columns are excluded from generated models and resources.
- Generated controllers return controlled error messages and avoid exposing internal exception details.
- Route prefix and middleware are configurable before exposing generated endpoints.

### Notes

- MySQL is the only supported database inspector in this release.
- Generated code must be reviewed before production usage.
- Authorization and business rules are intentionally left to the consuming application.
