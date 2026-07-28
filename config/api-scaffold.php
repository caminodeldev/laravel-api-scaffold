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
