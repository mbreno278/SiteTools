<?php
require_once __DIR__ . '/bootstrap.php';
require_permission('dashboard.view');

try {
    $pdo = db();
    $data = [
        'tools' => (int)$pdo->query('SELECT COUNT(*) FROM tools')->fetchColumn(),
        'categories' => (int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
        'admins' => (int)$pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn(),
        'operations' => (int)$pdo->query('SELECT COUNT(*) FROM tool_usage')->fetchColumn(),
    ];
    json_response(['success' => true, 'data' => $data]);
} catch (Throwable $exception) {
    error_log('[SiteTools API dashboard] ' . $exception->getMessage());
    json_response(['success' => false, 'message' => 'Não foi possível carregar os indicadores.'], 500);
}
