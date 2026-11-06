<?php
/**
 * Gate de acesso — incluir em page.php DEPOIS de carregar $tool e $handler
 *
 * Uso:
 *   require __DIR__ . '/content/tool-access-gate.php';
 *   // depois, no workspace:
 *   if (!$toolAccessAllowed) { echo tool_access_block_html($toolAccess); }
 *   else { include content da ferramenta }
 */

declare(strict_types=1);

if (!function_exists('tool_access_check')) {
    require_once __DIR__ . '/../config/tool-access.php';
}

$toolAccess = [
    'allowed' => true,
    'mode' => 'free',
    'status' => 'active',
    'reason' => 'public',
    'label' => 'Gratuito',
];
$toolAccessAllowed = true;

if (!isset($tool) || !is_array($tool) || ($handler ?? '') === 'not-found') {
    return;
}

// 1) Desativada no admin → bloqueio total (não usa a ferramenta)
if (isset($tool['is_active']) && (int) $tool['is_active'] !== 1) {
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

// 3) Regras free / login / paid
if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $toolAccess = tool_access_check($pdo, $tool);
        $toolAccessAllowed = !empty($toolAccess['allowed']);
    } catch (Throwable $e) {
        error_log('[tool-access-gate] ' . $e->getMessage());
        $toolAccessAllowed = true;
    }
}
