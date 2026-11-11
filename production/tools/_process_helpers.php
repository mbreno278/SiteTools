<?php

declare(strict_types=1);

/**
 * Helpers procedurais para handlers legados em tools/*.php
 * sem forçar migração completa para Controllers.
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Helpers\Response;
use App\Services\DocumentConverterService;
use App\Services\ImageProcessingService;
use App\Services\Process\ProcessRunner;
use App\Services\UsageLimitService;

function process_runner(): ProcessRunner
{
    static $r = null;
    return $r ??= new ProcessRunner();
}

function process_document_service(): DocumentConverterService
{
    static $s = null;
    return $s ??= new DocumentConverterService(process_runner());
}

function process_image_service(): ImageProcessingService
{
    static $s = null;
    return $s ??= new ImageProcessingService();
}

/**
 * Envia arquivo processado como download e registra uso se autenticado.
 */
function process_send_file(
    string $path,
    string $downloadName,
    string $mime,
    string $toolSlug,
    ?int $toolId = null,
    ?int $processingMs = null
): never {
    if (!is_file($path)) {
        Response::error('Arquivo de saída não encontrado.', 500, null, 'output_missing');
    }

    $bytes = (int) filesize($path);

    // Histórico autenticado
    if (function_exists('user_area_log_tool_usage') && isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
        try {
            user_area_log_tool_usage($GLOBALS['pdo'], $toolId, $toolSlug, 'completed', $processingMs, $bytes);
        } catch (Throwable) {
        }
    } elseif (function_exists('user_area_log_tool_usage') && isset($pdo) && $pdo instanceof PDO) {
        try {
            user_area_log_tool_usage($pdo, $toolId, $toolSlug, 'completed', $processingMs, $bytes);
        } catch (Throwable) {
        }
    }

    // Contador de cota
    try {
        if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO && $toolId) {
            $uid = null;
            if (function_exists('auth_user')) {
                $u = auth_user();
                $uid = $u ? (int) $u['id'] : null;
            }
            UsageLimitService::make($GLOBALS['pdo'])->recordUse($toolId, $uid, false);
        }
    } catch (Throwable) {
    }

    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $downloadName) . '"');
    header('Content-Length: ' . (string) $bytes);
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    readfile($path);
    exit;
}

/**
 * Resolve tool_id pelo slug (cache simples por request).
 */
function process_tool_id(PDO $pdo, string $slug): ?int
{
    static $cache = [];
    if (array_key_exists($slug, $cache)) {
        return $cache[$slug];
    }
    $stmt = $pdo->prepare('SELECT id FROM tools WHERE slug = :s LIMIT 1');
    $stmt->execute(['s' => $slug]);
    $id = $stmt->fetchColumn();
    $cache[$slug] = $id !== false ? (int) $id : null;
    return $cache[$slug];
}
