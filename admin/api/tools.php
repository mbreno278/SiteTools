<?php
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'message' => 'Este endpoint é somente leitura nesta versão.'], 405);
}

try {
    $stmt = db()->query('SELECT id, name, slug, category, is_active FROM tools ORDER BY id DESC');
    json_response(['success' => true, 'data' => $stmt->fetchAll()]);
} catch (Throwable $exception) {
    error_log('[SiteTools API tools] ' . $exception->getMessage());
    json_response(['success' => false, 'message' => 'Erro ao consultar ferramentas.'], 500);
}
