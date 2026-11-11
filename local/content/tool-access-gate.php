<?php
/**
 * Gate de acesso — incluir em page.php DEPOIS de carregar $tool e $handler
 *
 * Fail-closed: qualquer falha ao resolver autorização em ferramenta
 * protegida resulta em acesso negado.
 *
 * Uso:
 *   require __DIR__ . '/content/tool-access-gate.php';
 *   if (!$toolAccessAllowed) { echo tool_access_block_html($toolAccess); }
 *   else { include content da ferramenta }
 */

declare(strict_types=1);

if (!function_exists('tool_access_check')) {
    require_once __DIR__ . '/../config/tool-access.php';
}

$toolAccess = [
    'allowed' => false,
    'mode' => 'unavailable',
    'status' => 'unavailable',
    'reason' => 'unavailable',
    'label' => 'Indisponível',
];
$toolAccessAllowed = false;

if (!isset($tool) || !is_array($tool) || ($handler ?? '') === 'not-found') {
    // Página 404 / handler inexistente: não libera workspace de ferramenta
    return;
}

// 1) Desativada no admin → bloqueio total
if (array_key_exists('is_active', $tool) && (int) $tool['is_active'] !== 1) {
    $toolAccess = [
        'allowed' => false,
        'mode' => 'unavailable',
        'status' => 'unavailable',
        'reason' => 'unavailable',
        'label' => 'Indisponível',
        'tool_slug' => (string) ($tool['slug'] ?? ''),
    ];
    $toolAccessAllowed = false;
    return;
}

// 2) Status operacional development / unavailable
$status = strtolower((string) ($tool['status'] ?? 'active'));
if ($status === 'development' || $status === 'unavailable') {
    $toolAccess = [
        'allowed' => false,
        'mode' => $status,
        'status' => $status,
        'reason' => $status,
        'label' => $status === 'development' ? 'Em desenvolvimento' : 'Indisponível',
        'tool_slug' => (string) ($tool['slug'] ?? ''),
    ];
    $toolAccessAllowed = false;
    return;
}

// 3) Regras free / login / paid — exige PDO
if (!isset($pdo) || !($pdo instanceof PDO)) {
    // Sem banco: fail-closed para qualquer ferramenta registrada
    $toolAccess = [
        'allowed' => false,
        'mode' => 'unavailable',
        'status' => 'unavailable',
        'reason' => 'service_unavailable',
        'label' => 'Indisponível',
        'tool_slug' => (string) ($tool['slug'] ?? ''),
    ];
    $toolAccessAllowed = false;
    return;
}

try {
    $toolAccess = tool_access_check($pdo, $tool);
    $toolAccessAllowed = !empty($toolAccess['allowed']);
} catch (Throwable $e) {
    error_log('[tool-access-gate] ' . $e->getMessage());
    // Fail-closed: erro na consulta de autorização = negado
    $toolAccess = [
        'allowed' => false,
        'mode' => 'unavailable',
        'status' => 'unavailable',
        'reason' => 'authorization_error',
        'label' => 'Indisponível',
        'tool_slug' => (string) ($tool['slug'] ?? ''),
    ];
    $toolAccessAllowed = false;
}
