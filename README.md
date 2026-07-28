# Laravel API Scaffold

[![Tests](https://github.com/caminodeldev/laravel-api-scaffold/actions/workflows/tests.yml/badge.svg?branch=master)](https://github.com/caminodeldev/laravel-api-scaffold/actions/workflows/tests.yml)

Generate clean, secure and configurable Laravel API scaffolding from existing database tables.

**Languages:** [English](README.md) | [Español](README.es.md)

> This package generates reviewable Laravel code. It does not replace authorization design, business rules, human review, tests, or production readiness checks.

## What this package does

`caminodeldev/laravel-api-scaffold` inspects an existing database table and generates Laravel files that follow a layered API structure:

- Eloquent model.
- API controller.
- FormRequest classes.
- API Resource.
- Service class.
- Dedicated generated route file.
- Basic Feature test scaffold.

The generated code is intentionally simple and explicit. It is meant to be reviewed, customized and committed like normal application code.

## What this package does not do

This package does not:

- Act as a runtime CRUD engine.
- Infer business authorization rules.
- Create database migrations.
- Replace policies, gates, middleware or domain validation.
- Guarantee that generated code is production-ready without review.
- Support every database engine in `v0.1.2`.

## Release status

Current prepared release:

```text
v0.1.2
```

This release focuses on a safe MySQL-first API scaffold, with read-only generation as the recommended default and explicit opt-in flags for write and delete operations.

See [`CHANGELOG.md`](CHANGELOG.md) for release notes.

## Requirements

- PHP 8.2 or higher.
- Laravel 10, 11, 12 or 13.
- A configured database connection.
- MySQL support for the current MVP.

Laravel 13 compatibility is declared in Composer constraints and CI includes PHP 8.4. Always run the package test suite and validate generated code inside your target Laravel application before using it in production.

## Installation

Install the package with Composer:

```bash
composer require caminodeldev/laravel-api-scaffold
```

Laravel package discovery registers the service provider automatically.

Publish the configuration file:

```bash
php artisan vendor:publish --tag=api-scaffold-config
```

This creates:

```text
config/api-scaffold.php
```

## Local development installation

When testing the package before publishing it to Packagist, add a local path repository in the Laravel application that will consume it.

Example:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../laravel-api-scaffold",
      "options": {
        "symlink": true
      }
    }
  ]
}
```

Then require it locally:

```bash
composer require caminodeldev/laravel-api-scaffold:@dev
```

Refresh autoload files:

```bash
composer dump-autoload
```

## Recommended first flow

Start by inspecting the table:

```bash
php artisan scaffold:inspect users --connection=mysql
```

Preview the generated API before writing files:

```bash
php artisan scaffold:api users --connection=mysql --read-only --dry-run
```

Generate a safe read-only API:

```bash
php artisan scaffold:api users --connection=mysql --read-only
```

`--read-only` is explicit for readability. Read-only is also the default behavior when `--crud` is not used.

The read-only scaffold generates routes for listing and showing records only:

```http
GET /api/v1/users
GET /api/v1/users/{user}
```

The `/api` prefix assumes the generated route file is imported from Laravel's `routes/api.php`. If you load the generated route file somewhere else, adjust `routes.prefix` in `config/api-scaffold.php`.

## Generated files

For a `users` table, the default read-only scaffold creates:

```text
app/Models/User.php
app/Http/Controllers/frontend/v1/UserController.php
app/Http/Requests/Frontend/User/IndexUserRequest.php
app/Http/Resources/UserResource.php
app/Services/UserService.php
routes/scaffolded-api.php
tests/Feature/UserApiTest.php
```

When `--crud` is used, the package also generates write FormRequests:

```text
app/Http/Requests/Frontend/User/StoreUserRequest.php
app/Http/Requests/Frontend/User/UpdateUserRequest.php
```

The generated controller delegates query logic to a service, validates input with FormRequest classes, returns data through a Resource and uses controlled `try/catch` blocks.

## Available Artisan commands

The package currently registers these commands:

| Command | Purpose |
| --- | --- |
| `scaffold:inspect` | Inspect a database table and print detected metadata. |
| `scaffold:model` | Generate only the Eloquent model for a table. |
| `scaffold:api` | Generate the API scaffold for a table. |

### `scaffold:inspect`

```bash
php artisan scaffold:inspect users --connection=mysql
```

Signature:

```text
scaffold:inspect
  {table}
  {--connection=}
```

Use this before generating files to confirm that the package reads the expected table metadata.

### `scaffold:model`

```bash
php artisan scaffold:model users --connection=mysql
```

Signature:

```text
scaffold:model
  {table}
  {--connection=}
  {--model=}
  {--dry-run}
  {--force}
```

Examples:

```bash
php artisan scaffold:model users --connection=mysql --dry-run
php artisan scaffold:model users --connection=mysql --model=AccountUser
php artisan scaffold:model users --connection=mysql --force
```

### `scaffold:api`

```bash
php artisan scaffold:api users --connection=mysql --read-only
```

Signature:

```text
scaffold:api
  {table}
  {--connection=}
  {--model=}
  {--route-resource=}
  {--read-only}
  {--crud}
  {--with-delete}
  {--dry-run}
  {--force}
```

Preview without writing files:

```bash
php artisan scaffold:api users --connection=mysql --read-only --dry-run
```

Generate explicit write operations:

```bash
php artisan scaffold:api users --connection=mysql --crud
```

Generate delete support explicitly:

```bash
php artisan scaffold:api users --connection=mysql --crud --with-delete
```

`--with-delete` requires `--crud`.

Use a custom route resource URI when the table name should not be exposed directly:

```bash
php artisan scaffold:api cha_solicitudes --connection=mysql --model=Solicitud --route-resource=solicitudes
```

By default, the route resource URI is derived from the table name, not from English pluralization of the model name. For example, `solicitudes` with `--model=Solicitud` generates `solicitudes`, not `solicituds`.

Overwrite existing generated files explicitly:

```bash
php artisan scaffold:api users --connection=mysql --read-only --force
```

## Route strategy

Generated routes are written to:

```text
routes/scaffolded-api.php
```

The package can optionally add an import block to `routes/api.php`:

```php
// <laravel-api-scaffold routes>
if (file_exists(__DIR__ . '/scaffolded-api.php')) {
    require __DIR__ . '/scaffolded-api.php';
}
// </laravel-api-scaffold routes>
```

This import is idempotent and configurable in `config/api-scaffold.php`.

Generated route blocks inside `routes/scaffolded-api.php` are managed per resource. Regenerating the same resource replaces the existing managed block instead of appending a duplicate block. Manual routes outside generated markers are preserved.

By default, the package uses `v1` as route prefix because `routes/api.php` is usually already mounted under `/api` by Laravel. If your project loads the generated route file elsewhere, change `routes.prefix` to `api/v1` or any prefix you need.

Generated Feature tests use Laravel named routes, such as `route('users.index')`, instead of hardcoded `/v1/...` paths. This keeps tests aligned with the actual Laravel route prefix, commonly `/api/v1/...` when loaded from `routes/api.php`.

Generated index smoke tests also mock the generated Service pagination method. This keeps the test focused on route/controller wiring and avoids failures when the consumer application's testing database has not been migrated yet.

## Configuration overview

Default controller namespace:

```php
App\Http\Controllers\frontend\v1
```

Default request namespace:

```php
App\Http\Requests\Frontend
```

Default output paths and namespaces are configurable in:

```text
config/api-scaffold.php
```

Generated controllers use the package response envelope helper:

```php
use CaminoDelDev\LaravelApiScaffold\Support\ResponseEnvelope;

ResponseEnvelope::make(200, 'Records retrieved successfully.', $data);
```

The helper centralizes response key resolution through `config/api-scaffold.php`:

```php
'envelope' => [
    'keys' => [
        'code' => 'code',
        'message' => 'message',
        'data' => 'data',
        'timestamp' => 'timestamp',
    ],
],
```

You can customize these keys for your organization:

```php
'envelope' => [
    'keys' => [
        'code' => 'codigoRetorno',
        'message' => 'glosaRetorno',
        'data' => 'respuesta',
        'timestamp' => 'timestamp',
    ],
],
```

By default, the package helper is used. If your Laravel application already has a compatible helper, configure it explicitly:

```php
'response' => [
    'helper' => App\Helpers\ResponseHelper::class,
    'method' => 'returnResponse',
    'include_timestamp' => false,
],
```

The configured helper must expose a public static method compatible with:

```php
returnResponse(int $code, string $message, mixed $data = []): array
```

If `response.helper` is `null` or the configured class/method is unavailable, the package falls back to its internal `ResponseEnvelope` implementation.

To include a timestamp in the default package envelope, enable:

```php
'response' => [
    'include_timestamp' => true,
],
```

Generated controllers now depend on the package at runtime through `ResponseEnvelope`. Keep `caminodeldev/laravel-api-scaffold` installed while using generated controllers, or replace the generated response calls with your application helper before removing the package.

## Security defaults and production readiness

Security is the main design constraint of this package. The scaffold is intentionally conservative and is meant to generate reviewable code, not production-ready authorization decisions.

Read the full security notes in [`SECURITY.md`](SECURITY.md).

Safe defaults included in the MVP:

- Read-only APIs are the recommended default.
- Write operations require `--crud`.
- Delete generation requires both `--crud` and `--with-delete`.
- Generated resources exclude common secret-like columns.
- Generated models exclude common secret-like columns from `$fillable`.
- Generated models hide common secret-like columns.
- Generated controllers return controlled error messages.
- Generated routes use configurable middleware.
- `--dry-run` lets you inspect planned files before writing.
- Existing files are not overwritten unless `--force` is used.

Before using generated code in production, review at least:

- Authentication middleware.
- Authorization rules, policies or gates.
- Generated FormRequest rules.
- Generated Resource fields.
- Search, filter and pagination behavior.
- Write operations and mass-assignment rules.
- Logs and exception handling.
- Database indexes and pagination limits.

## MVP status

Current MVP scope:

- MySQL table inspection.
- Safe Eloquent model generation.
- Read-only API generation.
- Optional CRUD generation via explicit flags.
- FormRequest generation.
- Resource generation excluding sensitive fields.
- Service layer generation.
- Thin controller generation with `try/catch`.
- Dedicated route file generation.
- Idempotent import into `routes/api.php`.
- Per-resource replacement of managed route blocks in `routes/scaffolded-api.php`.
- Dry-run mode.
- Force overwrite mode.
- Basic generated Feature test scaffold using named routes and a mocked index Service call.

Planned next steps after `v0.1.1`:

- PostgreSQL support.
- Optional policy generation.
- Real diff mode.
- Stronger generated Feature tests for CRUD flows.
- Optional extraction of the response envelope into a dedicated package.
- Expanded CI matrix for framework-version-specific validation.

## Development

Install dependencies:

```bash
composer install
```

Validate Composer metadata:

```bash
composer validate
```

Refresh autoload files:

```bash
composer dump-autoload
```

Run tests:

```bash
composer test
```

Current package test coverage validates:

- Sensitive column detection.
- File writing and dry-run behavior.
- MySQL table inspection with fixtures.
- Name resolution.
- Generated route URI, route parameter and Feature test behavior.
- Controller response envelope consistency.

## Versioning

Releases are created with Git tags. The package does not define a hardcoded `version` field in `composer.json`; Packagist and Composer resolve versions from repository tags.

Recommended patch release tag:

```bash
git tag -a v0.1.1 -m "v0.1.1"
git push origin v0.1.1
```

## Distribution archive

The package includes a `.gitattributes` file to keep development-only files out of Composer distribution archives.

## License

MIT
