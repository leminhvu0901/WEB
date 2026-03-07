<?php

// Set environment variables for Vercel serverless
$_ENV['APP_ENV'] = 'production';
$_ENV['APP_DEBUG'] = 'false';
$_ENV['APP_KEY'] = 'base64:ERYv/Bb23WTsWsr/Qo/jhqzQD8/XEGV1Svipfpnw8YA=';
$_ENV['APP_URL'] = 'https://web-leminhvus-projects.vercel.app';

$_ENV['LOG_CHANNEL'] = 'stderr';
$_ENV['LOG_LEVEL'] = 'error';
$_ENV['SESSION_DRIVER'] = 'cookie';
$_ENV['CACHE_DRIVER'] = 'array';

$_ENV['DB_CONNECTION'] = 'mysql';
$_ENV['DB_HOST'] = 'bgjoe5pytlj9pvmoeqbc-mysql.services.clever-cloud.com';
$_ENV['DB_PORT'] = '3306';
$_ENV['DB_DATABASE'] = 'bgjoe5pytlj9pvmoeqbc';
$_ENV['DB_USERNAME'] = 'uqwr7y4tpkt6ppsl';
$_ENV['DB_PASSWORD'] = 'jbxmb8CFdBlSTtgYXMic';

$_ENV['VIEW_COMPILED_PATH'] = '/tmp/views';
$_ENV['APP_CONFIG_CACHE'] = '/tmp/config.php';
$_ENV['APP_ROUTES_CACHE'] = '/tmp/routes.php';
$_ENV['APP_EVENTS_CACHE'] = '/tmp/events.php';

// Also set in putenv and $_SERVER for compatibility
foreach ($_ENV as $key => $value) {
    if (is_string($value)) {
        putenv("$key=$value");
        $_SERVER[$key] = $value;
    }
}

// Ensure /tmp/views directory exists
if (!is_dir('/tmp/views')) {
    mkdir('/tmp/views', 0755, true);
}

require __DIR__ . '/../public/index.php';
