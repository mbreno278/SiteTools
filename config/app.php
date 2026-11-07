<?php

declare(strict_types=1);

/**
 * Configuração geral da aplicação.
 * Preferir valores via .env (veja .env.example).
 */

require_once __DIR__ . '/env.php';

if (!defined('APP_NAME')) {
    define('APP_NAME', (string) env('APP_NAME', 'SiteTools'));
}

if (!defined('APP_ENV')) {
    define('APP_ENV', app_env());
}

if (!defined('BASE_URL')) {
    // Ex.: '/projetos/SiteTools' no WAMP ou '' na raiz do domínio
    define('BASE_URL', rtrim((string) env('BASE_URL', ''), '/'));
}

if (!defined('ADMIN_SESSION_NAME')) {
    define('ADMIN_SESSION_NAME', (string) env('ADMIN_SESSION_NAME', 'sitetools_admin'));
}

if (!defined('TIMEZONE')) {
    define('TIMEZONE', (string) env('TIMEZONE', 'America/Sao_Paulo'));
}

if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', (bool) env('APP_DEBUG', app_is_local()));
}

if (!defined('APP_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('APP_URL', (string) env('APP_URL', $scheme . '://' . $host . BASE_URL));
}

if (!defined('STORAGE_PATH')) {
    define('STORAGE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage');
}

if (!defined('LOG_PATH')) {
    define('LOG_PATH', STORAGE_PATH . DIRECTORY_SEPARATOR . 'logs');
}

date_default_timezone_set(TIMEZONE);

// Erros: nunca exibir detalhes em produção
if (APP_ENV === 'production' || !APP_DEBUG) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
}

// Log de erros do PHP em storage/logs
if (is_dir(LOG_PATH) || @mkdir(LOG_PATH, 0750, true)) {
    ini_set('error_log', LOG_PATH . DIRECTORY_SEPARATOR . 'php-error.log');
}

// Uploads (valores em bytes) — reforço; o PHP.ini continua mandando no limite absoluto
if (!defined('UPLOAD_MAX_BYTES')) {
    define('UPLOAD_MAX_BYTES', (int) env('UPLOAD_MAX_BYTES', 30 * 1024 * 1024));
}

if (!defined('CLEANUP_TOKEN')) {
    define('CLEANUP_TOKEN', (string) env('CLEANUP_TOKEN', ''));
}

if (!defined('MAIL_FROM')) {
    define('MAIL_FROM', (string) env('MAIL_FROM', 'noreply@sitetools.local'));
}
