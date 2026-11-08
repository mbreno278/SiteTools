<?php
declare(strict_types=1);

/**
 * Protege páginas administrativas contra acesso direto fora do roteador.
 * Quando incluída por admin/index.php, o roteador já validou a permissão.
 */
$adminScript = str_replace('\\\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
if (preg_match('~(?:^|/)admin/pages/([^/]+\\.php)$~i', $adminScript, $matches)) {
    require_once dirname(__DIR__) . '/includes/permissions.php';

    $requestedFile = strtolower($matches[1]);
    $requiredPermission = null;

    foreach (admin_page_permission_map() as $slug => $permission) {
        if (strtolower($slug . '.php') === $requestedFile) {
            $requiredPermission = $permission;
            break;
        }
    }

    if ($requiredPermission === null) {
        http_response_code(404);
        exit('Página não encontrada.');
    }

    require_permission($requiredPermission);
}
