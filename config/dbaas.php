<?php

return [
    /*
    |--------------------------------------------------------------------------
    | DBaaS Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the Database as a Service (DBaaS)
    | application. It includes API keys, allowed operations, and other settings.
    |
    */

    // API Keys
    'api_keys' => [
        env('DBAAS_API_KEY', 'default-api-key-for-development'),
    ],

    // Allowed database operations
    'allowed_operations' => [
        'select' => true,
        'insert' => true,
        'update' => true,
        'delete' => true,
    ],

    // Maximum number of records per request
    'max_records_per_request' => env('DBAAS_MAX_RECORDS', 1000),

    // Allowed tables (empty array means all tables are allowed)
    'allowed_tables' => [],

    // Restricted tables (these tables cannot be accessed via the API)
    'restricted_tables' => [
        'users',
        'password_resets',
        'migrations',
        'failed_jobs',
        'personal_access_tokens',
    ],
];
