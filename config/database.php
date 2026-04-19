<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST'),
            'port' => env('DB_PORT'),
            'database' => (function () {
                $database = env('DB_DATABASE');
                $legacyDatabase = env('DB_DBNAME');

                if ((!is_string($database) || trim($database) === '') && is_string($legacyDatabase) && trim($legacyDatabase) !== '') {
                    return trim($legacyDatabase);
                }

                return is_string($database) ? trim($database) : $database;
            })(),
            'username' => (function () {
                $username = env('DB_USERNAME');
                $legacyUsername = env('DB_USER');

                if ((!is_string($username) || trim($username) === '') && is_string($legacyUsername) && trim($legacyUsername) !== '') {
                    return trim($legacyUsername);
                }

                $username = is_string($username) ? trim($username) : $username;
                $legacyUsername = is_string($legacyUsername) ? trim($legacyUsername) : $legacyUsername;

                if (is_string($legacyUsername) && $legacyUsername !== '' && is_string($username) && !str_contains($username, '.') && str_contains($legacyUsername, '.')) {
                    return $legacyUsername;
                }

                return $username;
            })(),
            'password' => env('DB_PASSWORD'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,

            'options' => extension_loaded('pdo_mysql') ? (function (): array {
                $sslCa = env('MYSQL_ATTR_SSL_CA');
                $dbHost = strtolower((string) env('DB_HOST', ''));
                $sslMode = strtolower((string) env('DB_SSL_MODE', ''));

                if (is_string($sslCa) && $sslCa !== '' && !preg_match('/^[A-Za-z]:[\\\\\/]/', $sslCa) && !str_starts_with($sslCa, '/')) {
                    $sslCa = base_path($sslCa);
                }

                $needsSecureTransport = str_contains($dbHost, 'tidbcloud.com')
                    || in_array($sslMode, ['required', 'verify_ca', 'verify_identity'], true);

                if ((!is_string($sslCa) || $sslCa === '') && $needsSecureTransport) {
                    $candidates = [
                        base_path('storage/certs/DigiCertGlobalRootG2.crt.pem'),
                        '/etc/ssl/certs/ca-certificates.crt',
                    ];

                    foreach ($candidates as $candidate) {
                        if (is_file($candidate)) {
                            $sslCa = $candidate;
                            break;
                        }
                    }
                }

                return array_filter([
                    PDO::MYSQL_ATTR_SSL_CA => (is_string($sslCa) && $sslCa !== '' && is_file($sslCa)) ? $sslCa : null,
                    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => filter_var(
                        env('MYSQL_ATTR_SSL_VERIFY_SERVER_CERT', false),
                        FILTER_VALIDATE_BOOLEAN
                    ),
                    PDO::ATTR_TIMEOUT => (int) env('DB_TIMEOUT', 5),
                ], static fn ($value) => $value !== null && $value !== '');
            })() : [],
        ],
        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            // 'encrypt' => env('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_') . '_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];
