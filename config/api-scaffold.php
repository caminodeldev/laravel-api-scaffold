<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | The package writes generated routes to a dedicated file. This keeps
    | routes/api.php small and makes generated changes easier to review.
    |
    */

    'routes' => [
        'enabled' => true,
        'file' => base_path('routes/scaffolded-api.php'),
        'import_from_api_php' => true,
        'prefix' => 'v1',
        'middleware' => ['api'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Paths
    |--------------------------------------------------------------------------
    */

    'paths' => [
        'controllers' => app_path('Http/Controllers/frontend/v1'),
        'requests' => app_path('Http/Requests/Frontend'),
        'resources' => app_path('Http/Resources'),
        'services' => app_path('Services'),
        'models' => app_path('Models'),
        'tests' => base_path('tests/Feature'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Namespaces
    |--------------------------------------------------------------------------
    */

    'namespaces' => [
        'controllers' => 'App\\Http\\Controllers\\frontend\\v1',
        'requests' => 'App\\Http\\Requests\\Frontend',
        'resources' => 'App\\Http\\Resources',
        'services' => 'App\\Services',
        'models' => 'App\\Models',
        'tests' => 'Tests\\Feature',
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Generation
    |--------------------------------------------------------------------------
    |
    | Generated models may include Eloquent relationships discovered from
    | database foreign keys and PHPDoc metadata for IDE/static analysis support.
    |
    */

    'models' => [
        'generate_relationships' => true,
        'generate_phpdoc' => true,
        'phpdoc_decimal_type' => 'string',
    ],

    /*
    |--------------------------------------------------------------------------
    | Generation Defaults
    |--------------------------------------------------------------------------
    |
    | Read-only is the default and safest mode. Write operations must be
    | requested explicitly with command flags.
    |
    */

    'generation' => [
        'default_operations' => ['index', 'show'],
        'allow_write_operations' => false,
        'generate_model' => true,
        'generate_service' => true,
        'generate_resource' => true,
        'generate_form_requests' => true,
        'generate_tests' => true,
        'declare_strict_types' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Envelope
    |--------------------------------------------------------------------------
    |
    | This generic envelope can be changed to match any organizational contract.
    | No proprietary or institution-specific naming is hardcoded in the package.
    |
    */

    'envelope' => [
        'enabled' => true,
        'keys' => [
            'code' => 'code',
            'message' => 'message',
            'data' => 'data',
            'timestamp' => 'timestamp',
        ],
        'messages' => [
            'index_success' => 'Records retrieved successfully.',
            'show_success' => 'Record retrieved successfully.',
            'store_success' => 'Record created successfully.',
            'update_success' => 'Record updated successfully.',
            'destroy_success' => 'Record deleted successfully.',
            'delete_success' => 'Record deleted successfully.',
            'internal_error' => 'Internal server error.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Helper
    |--------------------------------------------------------------------------
    |
    | By default, generated controllers use the package ResponseEnvelope helper.
    | You may configure an application helper with a compatible static method,
    | such as App\Helpers\ResponseHelper::returnResponse().
    |
    */

    'response' => [
        'helper' => null,
        'method' => 'returnResponse',
        'include_timestamp' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Introspection
    |--------------------------------------------------------------------------
    |
    | PostgreSQL supports schemas. When a table is not passed as schema.table,
    | the inspector uses this schema by default.
    |
    */

    'database' => [
        'default_schema' => 'public',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Generated index endpoints validate and clamp pagination values using these
    | defaults. The legacy security keys are still read as fallbacks.
    |
    */

    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Defaults
    |--------------------------------------------------------------------------
    |
    | Generated services use allow-lists for filters, search and sorting. This
    | default sort is only applied when the generated resource allows the column.
    |
    */

    'query' => [
        'default_sort' => '-id',

        /*
        |--------------------------------------------------------------------------
        | Invalid Query Handling
        |--------------------------------------------------------------------------
        |
        | By default, generated index requests remain backward compatible and
        | invalid filter/sort columns are ignored safely by the generated Service.
        | Enable these flags when you prefer fail-fast HTTP 422 validation.
        |
        */

        'reject_invalid_filters' => false,
        'reject_invalid_sorts' => false,

        /*
        |--------------------------------------------------------------------------
        | Query-only Column Exclusions
        |--------------------------------------------------------------------------
        |
        | These exclusions apply only to generated FILTERABLE, SEARCHABLE and
        | SORTABLE allow-lists. They are evaluated in addition to security.*
        | exclusions, which continue to protect fillable arrays and resources.
        |
        */

        'excluded_columns' => [],
        'excluded_patterns' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Defaults
    |--------------------------------------------------------------------------
    |
    | These columns are excluded from generated fillable arrays and API
    | resources by default. Review generated files before production use.
    |
    */

    'security' => [
        /*
        |--------------------------------------------------------------------------
        | Exact Sensitive Columns
        |--------------------------------------------------------------------------
        |
        | Exact names are normalized before comparison, so apiKey, api_key and
        | API-KEY are treated consistently. These columns are never generated
        | into fillable arrays or API resources.
        |
        */

        'excluded_columns' => [
            'password',
            'password_confirmation',
            'password_hash',
            'passwd',
            'pwd',
            'remember_token',
            'token',
            'access_token',
            'refresh_token',
            'id_token',
            'personal_access_token',
            'jwt',
            'bearer_token',
            'secret',
            'client_secret',
            'api_key',
            'api_secret',
            'access_key',
            'secret_key',
            'private_key',
            'encryption_key',
            'signing_key',
            'webhook_secret',
            'session_id',
            'csrf_token',
            'xsrf_token',
            'otp',
            'mfa_secret',
            'two_factor_secret',
            'recovery_code',
            'recovery_codes',
            'salt',
            'hash',
            'created_at',
            'updated_at',
            'deleted_at',
        ],

        'hidden_columns' => [
            'password',
            'password_confirmation',
            'password_hash',
            'passwd',
            'pwd',
            'remember_token',
            'token',
            'access_token',
            'refresh_token',
            'id_token',
            'personal_access_token',
            'jwt',
            'bearer_token',
            'secret',
            'client_secret',
            'api_key',
            'api_secret',
            'access_key',
            'secret_key',
            'private_key',
            'encryption_key',
            'signing_key',
            'webhook_secret',
            'session_id',
            'csrf_token',
            'xsrf_token',
            'otp',
            'mfa_secret',
            'two_factor_secret',
            'recovery_code',
            'recovery_codes',
            'salt',
            'hash',
        ],

        /*
        |--------------------------------------------------------------------------
        | Sensitive Name Patterns
        |--------------------------------------------------------------------------
        |
        | These regular expressions catch common secret-like names without
        | treating every column containing "key" as sensitive. For example,
        | foreign_key_id remains allowed, while api_key and signing_key are
        | excluded.
        |
        */

        'sensitive_name_patterns' => [
            '/(^|_)(password|passwd|pwd)(_|$)/i',
            '/(^|_)(token|jwt|bearer)(_|$)/i',
            '/(^|_)(secret|credential|credentials)(_|$)/i',
            '/(^|_)(client|api|access|secret|private|encryption|signing|webhook|aws|gcp|azure)_(key|secret|token)(_|$)/i',
            '/(^|_)(otp|mfa|two_factor|recovery_code|recovery_codes)(_|$)/i',
            '/(^|_)(session|cookie|csrf|xsrf)(_|$)/i',
            '/(^|_)(hash|salt)(_|$)/i',
        ],

        'max_per_page' => 100,
        'default_per_page' => 15,
    ],
];
