# Changelog

All notable changes to this project will be documented in this file.

This project follows semantic versioning once stable releases are published.

## [0.3.2] - 2026-08-30

### Added

- Added optional strict query validation for generated index FormRequests.
- Added `api-scaffold.query.reject_invalid_filters` to return HTTP 422 for unknown direct or nested filter columns when enabled.
- Added `api-scaffold.query.reject_invalid_sorts` to return HTTP 422 for unsupported sort columns when enabled.
- Added query-only `api-scaffold.query.excluded_columns` and `api-scaffold.query.excluded_patterns` settings for generated `FILTERABLE`, `SEARCHABLE` and `SORTABLE` allow-lists.

### Changed

- Generated index FormRequests now include explicit `FILTERABLE` and `SORTABLE` allow-lists used only when strict query validation is enabled.
- Query allow-list generation can now exclude additional non-secret business columns from filtering, searching and sorting without removing them from generated fillable arrays or API resources.
- Defaults remain backward compatible: invalid filters and sorts continue to be ignored safely unless strict mode is enabled.

### Documentation

- Documented strict filter/sort validation mode.
- Documented query-only column and pattern exclusions.
- Added examples for fail-fast query validation and business-field query exclusions.

## [0.3.1] - 2026-08-30

### Fixed

- Generate Laravel `uuid` validation rules for MySQL/MariaDB UUID-like columns named `uuid` with `char(36)`, `varchar(36)` or string metadata.
- Use a text-safe PostgreSQL `pg_index.indkey` check for single-column unique index detection, avoiding assumptions about `int2vector` array functions.

### Tests

- Added coverage for MySQL UUID-like `char(36)` / `varchar(36)` request validation.
- Added coverage to keep regular `char(36)` columns as strings.
- Added coverage for PostgreSQL native enum metadata propagation to generated FormRequest `in:` rules.
- Added coverage to distinguish PostgreSQL single-column unique indexes from composite unique indexes.

### Documentation

- Documented the need to run `php artisan optimize:clear` and `php artisan octane:reload` after generating routes/code in Laravel Octane or Swoole applications.
- Added an Octane detection hint after `scaffold:api` generation. The package only prints the reminder and does not reload workers automatically.

## [0.3.0] - 2026-07-28

### Added

- PostgreSQL table inspection through a new `PostgresTableInspector`.
- `TableInspectorFactory` to select the correct inspector from the Laravel connection driver.
- Support for `pgsql` connections in `scaffold:inspect`, `scaffold:model` and `scaffold:api`.
- PostgreSQL schema-qualified table names such as `public.solicitudes` and `tramites.solicitudes`.
- PostgreSQL metadata mapping for common Laravel API table columns: `uuid`, `boolean`, `jsonb`, `numeric`, `timestamp`, `bigint`, `integer`, `varchar`, `text` and user-defined enum types.
- PostgreSQL enum value extraction for generated `in:` FormRequest rules.
- PostgreSQL single-column unique index detection for generated `unique:table,column` rules.
- PostgreSQL identity and `bigserial`/`serial` auto-increment detection.
- Unit tests for PostgreSQL table inspection, driver factory resolution and generator behavior with PostgreSQL metadata.

### Changed

- Console commands now resolve table inspectors through the driver-aware factory instead of depending directly on the MySQL inspector.
- Schema-qualified table names use the base table name for generated model, route and test names while keeping the schema-qualified table in the generated Eloquent model.
- Generated validation now treats PostgreSQL `uuid` columns as Laravel `uuid` validation rules.
- Generated query allow-lists support PostgreSQL `uuid` columns for exact filtering and sorting.
- MySQL inspector accepts `mariadb` as a MySQL-compatible driver.

### Documentation

- Documented MySQL and PostgreSQL support.
- Documented PostgreSQL schema-qualified table usage.
- Documented PostgreSQL validation expectations and current scope.

## [0.2.0] - 2026-07-28

### Added

- Generated Service allow-lists for safe exact filters, search columns and sortable columns.
- Generated index support for direct filters such as `?estado=ingresada`.
- Generated index support for nested filters such as `?filter[estado]=ingresada`.
- Generated index support for `search` across safe text-like columns.
- Generated index support for comma-separated safe sorting such as `?sort=estado,-id`.
- Dedicated `pagination` configuration with `default_per_page` and `max_per_page`.
- Dedicated `query.default_sort` configuration.
- `QueryColumnResolver` to derive safe query columns from inspected metadata and security configuration.
- Unit tests for generated Service query behavior, index request filters and query column resolution.

### Changed

- Generated Services now apply filters, search, sorting and pagination through explicit allow-lists.
- Generated index requests now validate `per_page`, `perPage`, `search`, `sort`, `filter` and safe generated filter fields.
- Generated write FormRequests now include enum `in:` validation when MySQL enum values are available.
- Generated store FormRequests now include `unique:table,column` rules for unique writable columns.
- Generated validation treats MySQL `tinyint(1)` columns as booleans when column metadata is available.
- Generated models cast MySQL `tinyint(1)` columns as booleans.

### Documentation

- Documented generated query behavior for filters, search, sorting and pagination.
- Documented pagination configuration and generated query allow-lists.


## [0.1.2] - 2026-07-28

### Added

- Internal `ResponseEnvelope::make()` helper to centralize generated API response envelopes.
- Optional `response.helper` and `response.method` configuration to delegate envelope formatting to a compatible application helper, such as `App\Helpers\ResponseHelper::returnResponse()`.
- Optional `response.include_timestamp` support for the package default envelope.
- Configurable `envelope.keys.timestamp` key for timestamp output.

### Changed

- Generated controllers now call `ResponseEnvelope::make()` instead of building response arrays inline.
- Generated controllers depend on the package helper at runtime unless projects replace the generated response calls with their own application helper.

### Documentation

- Documented the internal response envelope helper.
- Documented explicit integration with application-level helpers.
- Documented the runtime dependency introduced by generated controllers using `ResponseEnvelope`.

## [0.1.1] - 2026-07-28

### Fixed

- Generate API resource URIs from the table name by default, avoiding English-only pluralization such as `solicituds`.
- Add explicit route parameter mapping for generated resources, so table-based URIs such as `solicitudes` still bind to controller parameters like `$solicitud`.
- Generate Feature tests using Laravel named routes such as `route('solicitudes.index')` instead of hardcoded `/v1/...` paths.
- Generate index smoke tests with a mocked Service paginator so they do not depend on the consumer application's testing database state.
- Use configurable response envelope keys and messages consistently across generated `store`, `update` and `destroy` controller actions.
- Replace managed blocks in `routes/scaffolded-api.php` per generated resource instead of appending duplicate blocks on regeneration.
- Generated feature tests now mock the generated service to avoid depending on the consumer application's test database state.

### Added

- `--route-resource` option for `scaffold:api` to customize the generated API resource URI.
- MySQL enum value extraction in table inspection output.
- Additional tests covering generated route URI behavior, route parameters, Feature test generation, controller envelope consistency and managed route block replacement.

### Documentation

- Documented table-based route URI generation and `--route-resource`.
- Documented generated Feature test behavior with Laravel named routes and mocked Service pagination.
- Clarified `/api/v1` behavior when generated routes are imported from Laravel's `routes/api.php`.


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
- English and Spanish README documentation.
- Declared Composer compatibility with Laravel 13.

### Security

- Read-only API generation is the recommended default.
- Write operations require explicit opt-in.
- Delete operations require an additional explicit opt-in flag.
- Common secret-like columns are excluded from generated models and resources.
- Generated controllers return controlled error messages and avoid exposing internal exception details.
- Route prefix and middleware are configurable before exposing generated endpoints.

### Notes

- MySQL is the only supported database inspector in this release.
- Laravel 13 support is declared through Composer constraints; validate generated code inside the target application before production usage.
- Generated code must be reviewed before production usage.
- Authorization and business rules are intentionally left to the consuming application.
