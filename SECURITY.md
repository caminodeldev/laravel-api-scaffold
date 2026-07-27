# Security Policy

Laravel API Scaffold is designed to generate conservative, reviewable Laravel API code. It does not make generated endpoints automatically safe for production.

Generated code must be reviewed by a developer before it is exposed to real users, real data, or public networks.

## Supported versions

Until `1.0.0`, the package should be treated as pre-stable.

| Version | Security support |
| --- | --- |
| `0.x` | Best-effort fixes while the API is evolving |
| `1.x` | Planned stable support after first stable release |

## Security model

This package is a code generator. It reduces repetitive work, but it does not replace application-specific security design.

The generated scaffold intentionally avoids runtime magic. After generation, your Laravel application owns the generated files.

The package can help with:

- generating FormRequests,
- generating API Resources,
- generating thin controllers,
- generating a service layer,
- excluding common sensitive columns,
- avoiding write endpoints by default,
- generating routes in a dedicated file,
- previewing writes with `--dry-run`.

The package cannot know:

- which users may access a resource,
- which roles may create, update or delete records,
- whether a table contains personal, financial, medical or regulated data,
- which columns are safe for a specific API consumer,
- which business rules apply to each operation,
- whether a table should be exposed as an API at all.

## Safe defaults

### Read-only by default

The recommended default is:

```bash
php artisan scaffold:api users --connection=mysql --read-only
```

This generates only:

```http
GET /api/v1/users
GET /api/v1/users/{user}
```

Write operations are explicit:

```bash
php artisan scaffold:api users --connection=mysql --crud
```

Delete generation is intentionally separated:

```bash
php artisan scaffold:api users --connection=mysql --crud --with-delete
```

### Sensitive column exclusion

The package excludes common sensitive columns from generated API Resources and model `$fillable` arrays.

Examples include:

```text
password
remember_token
token
access_token
refresh_token
secret
client_secret
api_key
secret_key
private_key
encryption_key
signing_key
webhook_secret
csrf_token
xsrf_token
mfa_secret
two_factor_secret
recovery_codes
salt
hash
```

The package also supports configurable regular expression patterns through:

```php
config('api-scaffold.security.sensitive_name_patterns')
```

Review this list for your application before generating APIs.

### No automatic trust in database structure

A database table is not an API contract.

Column names and database types can help generate a first draft, but they do not prove that a field is safe to expose, writable, searchable or filterable.

Always review generated:

```text
app/Http/Resources/*
app/Http/Requests/*
app/Models/*
app/Services/*
app/Http/Controllers/*
```

### Controlled error messages

Generated controllers use controlled error responses and avoid returning stack traces to the client.

Technical errors should be kept in application logs. Do not log secrets, tokens, credentials or sensitive payloads.

## Production checklist

Before shipping generated code, review this checklist.

### Routes and middleware

- [ ] The generated route file is loaded intentionally.
- [ ] The route prefix is correct for the application.
- [ ] Protected routes use authentication middleware.
- [ ] Sensitive operations use authorization middleware, policies or gates.
- [ ] Public routes are intentionally public.

### Authorization

- [ ] `index` is authorized for the current user or client.
- [ ] `show` prevents access to records owned by other users or tenants.
- [ ] `store` validates who can create records.
- [ ] `update` validates who can modify each record.
- [ ] `delete` validates who can delete each record.
- [ ] Multi-tenant or organization-scoped data is filtered server-side.

### Requests

- [ ] FormRequest rules match business requirements.
- [ ] Required fields are correct.
- [ ] String lengths are correct.
- [ ] Numeric ranges are correct.
- [ ] Enum-like fields use explicit whitelists.
- [ ] Query filters are allowlisted.
- [ ] Pagination has a safe maximum.

### Resources

- [ ] API Resources expose only fields required by consumers.
- [ ] Secret-like columns are not exposed.
- [ ] Personal or regulated data is minimized.
- [ ] Internal IDs are safe to expose.
- [ ] Timestamps are safe to expose.

### Models and mass assignment

- [ ] `$fillable` contains only fields users may write.
- [ ] Sensitive columns are excluded.
- [ ] Casts are correct.
- [ ] Soft deletes are understood.
- [ ] Hidden fields are reviewed.

### Logs

- [ ] Logs do not contain access tokens.
- [ ] Logs do not contain passwords.
- [ ] Logs do not contain API keys.
- [ ] Logs do not contain full sensitive payloads.
- [ ] Error messages shown to users are safe and non-technical.

### CRUD operations

- [ ] `--crud` was used intentionally.
- [ ] `--with-delete` was used intentionally.
- [ ] Delete behavior is acceptable for the domain.
- [ ] Soft delete is preferred when historical traceability is required.
- [ ] Destructive operations are audited when required.

## Recommended workflow

Use `--dry-run` first:

```bash
php artisan scaffold:api users --connection=mysql --read-only --dry-run
```

Generate files in a feature branch:

```bash
php artisan scaffold:api users --connection=mysql --read-only
```

Review generated files before committing:

```bash
git diff
```

Run your project checks:

```bash
composer validate
composer test
php artisan test
```

Adapt generated code to your domain before production.

## Reporting security issues

Do not publish security vulnerabilities as public issues until they have been reviewed.

If the repository has private vulnerability reporting enabled, use GitHub private vulnerability reporting. Otherwise, contact the maintainer through the contact options listed in the repository profile.

When reporting, include:

- affected package version,
- Laravel version,
- PHP version,
- minimal reproduction steps,
- expected behavior,
- actual behavior,
- whether generated code or package runtime code is affected.

Do not include real secrets, production credentials, private tokens, personal data or regulated data in reports.
