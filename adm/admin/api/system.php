<?php
require_once __DIR__ . '/bootstrap.php';

json_response([
    'success' => true,
    'data' => [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Indisponível',
        'timezone' => date_default_timezone_get(),
        'storage_writable' => is_writable(dirname(__DIR__, 2) . '/storage'),
    ],
]);
