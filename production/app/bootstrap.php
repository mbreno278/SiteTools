<?php

declare(strict_types=1);

/**
 * Bootstrap da camada App\ — pode ser incluído por endpoints legados.
 *
 * Uso:
 *   require_once __DIR__ . '/../app/bootstrap.php';
 */

require_once __DIR__ . '/Helpers/Autoloader.php';

use App\Helpers\ErrorHandler;

// Config legado (PDO, auth, csrf, tool-access)
$configDir = dirname(__DIR__) . '/config';
if (is_file($configDir . '/csrf.php')) {
    require_once $configDir . '/csrf.php';
}
if (is_file($configDir . '/database.php')) {
    try {
        require_once $configDir . '/database.php';
        if (isset($pdo) && $pdo instanceof PDO) {
            $GLOBALS['pdo'] = $pdo;
        }
    } catch (Throwable) {
        // PDO opcional em alguns scripts
    }
}
if (is_file($configDir . '/auth.php')) {
    require_once $configDir . '/auth.php';
}
if (is_file($configDir . '/tool-access.php')) {
    require_once $configDir . '/tool-access.php';
}
if (is_file($configDir . '/modules-helper.php')) {
    require_once $configDir . '/modules-helper.php';
}
if (is_file($configDir . '/upload-security.php')) {
    require_once $configDir . '/upload-security.php';
}

ErrorHandler::register();

/**
 * PDO compartilhado.
 */
function app_pdo(): PDO
{
    if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
        return $GLOBALS['pdo'];
    }
    throw new RuntimeException('Conexão com o banco indisponível.');
}
