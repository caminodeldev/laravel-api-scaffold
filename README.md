# Laravel API Scaffold

Generate clean, secure and configurable Laravel API scaffolding from database tables.

> This package generates reviewable Laravel code. It does not replace authorization design, business rules, human review, tests, or production readiness checks.

## Requirements

- PHP 8.2 or higher.
- Laravel 10, 11 or 12.
- A configured database connection.
- MySQL support for the current MVP.

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

## First scaffold

Start by inspecting the table:

```bash
php artisan scaffold:inspect users --connection=mysql
```

Generate a safe read-only API:

```bash
php artisan scaffold:api users --connection=mysql --read-only
```

Preview generated files without writing anything:

```bash
php artisan scaffold:api users --connection=mysql --read-only --dry-run
```

The read-only scaffold generates endpoints for listing and showing records only.

```http
GET /api/v1/users
GET /api/v1/users/{user}
```

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

The generated controller delegates query logic to a service, validates input with FormRequest, returns data through a Resource, and uses controlled `try/catch` blocks.

## Main commands

### Inspect a table

```bash
php artisan scaffold:inspect users --connection=mysql
```

Use this before generating files to confirm that the package reads the expected table metadata.

### Generate only the model

```bash
php artisan scaffold:model users --connection=mysql
```

### Generate a read-only API

```bash
php artisan scaffold:api users --connection=mysql --read-only
```

### Preview without writing files

```bash
php artisan scaffold:api users --connection=mysql --read-only --dry-run
```

### Generate explicit write operations

```bash
php artisan scaffold:api users --connection=mysql --crud
```

Delete generation is intentionally separated:

```bash
php artisan scaffold:api users --connection=mysql --crud --with-delete
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

This behavior is configurable in `config/api-scaffold.php`.

By default, the package uses `v1` as route prefix because `routes/api.php` is usually already mounted under `/api` by Laravel. If your project loads the generated route file elsewhere, change `routes.prefix` to `api/v1` or any prefix you need.

## Configuration overview

Default controller namespace:

```php
App\Http\Controllers\frontend\v1
```

Default request namespace:

```php
App\Http\Requests\Frontend
```

Both are configurable in:

```text
config/api-scaffold.php
```

The generated controller uses configurable response keys:

```php
'envelope' => [
    'keys' => [
        'code' => 'code',
        'message' => 'message',
        'data' => 'data',
    ],
],
```

You can customize them for your organization:

```php
'envelope' => [
    'keys' => [
        'code' => 'codigoRetorno',
        'message' => 'glosaRetorno',
        'data' => 'respuesta',
    ],
],
```

## Security defaults and production readiness

Security is the main design constraint of this package. The scaffold is intentionally conservative and is meant to generate reviewable code, not production-ready authorization decisions.

Read the full security notes in [`SECURITY.md`](SECURITY.md).

Safe defaults included in the MVP:

- Read-only APIs are the recommended default.
- Write operations require `--crud`.
- Delete generation requires both `--crud` and `--with-delete`.
- Generated resources exclude common secret-like columns.
- Generated models exclude common secret-like columns from `$fillable`.
- Generated controllers return controlled error messages.
- Generated routes use configurable middleware.
- `--dry-run` lets you inspect planned files before writing.

Before using generated code in production, review at least:

- Authentication middleware.
- Authorization rules or policies.
- Generated FormRequest rules.
- Generated Resource fields.
- Search and filter behavior.
- Write operations and mass-assignment rules.
- Logs and exception handling.
- Database indexes and pagination limits.


## MVP status

Current MVP scope:

- MySQL table inspection.
- Safe Eloquent model generation.
- Read-only API generation.
- FormRequest generation.
- Resource generation excluding sensitive fields.
- Service layer generation.
- Thin controller generation with `try/catch`.
- Dedicated route file generation.
- Dry-run mode.
- Explicit CRUD generation flags.

Planned next steps:

- Stronger security documentation.
- GitHub Actions workflow.
- PostgreSQL support.
- Optional policy generation.
- Real diff mode.

## License

MIT
