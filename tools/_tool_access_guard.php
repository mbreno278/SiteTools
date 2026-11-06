<?php

declare(strict_types=1);

/**
 * SiteTools — guard de acesso no endpoint (fail-closed)
 *
 * Uso no topo de tools/xxx.php (após CSRF):
 *
 *   require __DIR__ . '/_tool_access_guard.php';
 *   tool_access_guard_for('word-to-pdf');
 *
 * Comportamento:
 * - DB offline ou erro ao resolver regra → NEGA acesso a ferramentas protegidas
 * - free + guest_allowed → permite (mesmo com degradação limitada)
 * - login / paid / temporary / development / unavailable → exige confirmação positiva
 */

require_once __DIR__ . '/../config/tool-access.php';

/**
 * Nega com JSON e encerra a requisição.
 */
function tool_access_guard_deny(string $message, string $reason, int $status = 403): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
    echo json_encode([
        'error'  => $message,
        'reason' => $reason,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Obtém PDO de forma segura (sem fail-open).
 */
function tool_access_guard_pdo(): ?PDO
{
    if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
        return $GLOBALS['pdo'];
    }

    try {
        require_once __DIR__ . '/../config/database.php';
    } catch (Throwable) {
        return null;
    }

    if (isset($pdo) && $pdo instanceof PDO) {
        $GLOBALS['pdo'] = $pdo;
        return $pdo;
    }

    return null;
}

/**
 * Bloqueia o endpoint se o usuário não tiver autorização para a ferramenta.
 */
function tool_access_guard_for(string $slug): void
{
    $slug = strtolower(trim($slug));
    if ($slug === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
        tool_access_guard_deny('Ferramenta inválida.', 'invalid_slug', 400);
    }

    $pdo = tool_access_guard_pdo();
    if ($pdo === null) {
        // Fail-closed: sem banco não é possível confirmar autorização
        tool_access_guard_deny(
            'Serviço temporariamente indisponível. Tente novamente em instantes.',
            'service_unavailable',
            503
        );
    }

    try {
        // tool_access_require já nega com JSON se não permitido
        tool_access_require($pdo, $slug);
    } catch (Throwable $e) {
        error_log('[tool_access_guard] ' . $e->getMessage());
        tool_access_guard_deny(
            'Não foi possível validar o acesso a esta ferramenta.',
            'authorization_error',
            503
        );
    }
}
