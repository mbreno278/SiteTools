<?php

declare(strict_types=1);

/**
 * Bloqueia endpoint se o módulo correspondente estiver off.
 * Uso: require + module_guard_require('video_downloads');
 */

require_once dirname(__DIR__) . '/config/modules-helper.php';

function module_guard_require(string $module, string $message = 'Este módulo está desativado.'): void
{
    if (module_enabled($module)) {
        return;
    }

    if (!headers_sent()) {
        http_response_code(503);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
    }

    echo json_encode([
        'error' => $message,
        'reason' => 'module_disabled',
        'module' => $module,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
