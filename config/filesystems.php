<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been set up for each driver as an example of the required values.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',

              APP_NAME=Laravel
              APP_ENV=local
              APP_KEY=base64:ERYv/Bb23WTsWsr/Qo/jhqzQD8/XEGV1Svipfpnw8YA=
              APP_DEBUG=true
              APP_URL=http://localhost:8000

              LOG_CHANNEL=stack
              LOG_DEPRECATIONS_CHANNEL=null
              LOG_LEVEL=debug

              BROADCAST_DRIVER=log
              CACHE_DRIVER=file
            - FILESYSTEM_DISK=local
            + FILESYSTEM_DISK=s3
              QUEUE_CONNECTION=sync
              SESSION_DRIVER=file
              SESSION_LIFETIME=120

              # DATABASE
              DB_CONNECTION=mysql
              DB_HOST=gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com
              DB_PORT=4000
              DB_DATABASE=test
              DB_USERNAME=3bHZndwFRc3VLmY.root
              DB_PASSWORD=UYfmRbfd8SelCmld

              MYSQL_ATTR_SSL_CA=storage/certs/DigiCertGlobalRootG2.crt.pem
              MYSQL_ATTR_SSL_VERIFY_SERVER_CERT=false



              MEMCACHED_HOST=127.0.0.1

              REDIS_HOST=127.0.0.1
              REDIS_PASSWORD=null
              REDIS_PORT=6379

              MAIL_MAILER=smtp
              MAIL_HOST=mailpit
              MAIL_PORT=1025
              MAIL_USERNAME=null
              MAIL_PASSWORD=null
              MAIL_ENCRYPTION=null
              MAIL_FROM_ADDRESS="hello@example.com"
              MAIL_FROM_NAME="${APP_NAME}"

              AWS_ACCESS_KEY_ID=
              AWS_SECRET_ACCESS_KEY=
              AWS_DEFAULT_REGION=us-east-1
              AWS_BUCKET=
              AWS_USE_PATH_STYLE_ENDPOINT=false

              PUSHER_APP_ID=
              PUSHER_APP_KEY=
              PUSHER_APP_SECRET=
              PUSHER_HOST=
              PUSHER_PORT=443
              PUSHER_SCHEME=https
              PUSHER_APP_CLUSTER=mt1

              VITE_APP_NAME="${APP_NAME}"
              VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
              VITE_PUSHER_HOST="${PUSHER_HOST}"
              VITE_PUSHER_PORT="${PUSHER_PORT}"
              VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
              VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
                          'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
