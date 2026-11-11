<?php
declare(strict_types=1);

/**
 * Bootstrap de APIs do admin.
 * Não redireciona para HTML — use require_admin() que já responde JSON em /api/.
 */
require_once dirname(__DIR__) . '/includes/permissions.php';

if (!admin_logged_in()) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Sessão expirada. Faça login novamente.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    verify_csrf();
}
