<?php
require_once __DIR__ . '/bootstrap.php';

try {
    $total = (int)db()->query('SELECT COUNT(*) FROM tool_usage')->fetchColumn();
    json_response(['success' => true, 'data' => ['operations_total' => $total, 'note' => 'A coleta de eventos precisa ser integrada aos handlers públicos.']]);
} catch (Throwable $exception) {
    error_log('[SiteTools API analytics] ' . $exception->getMessage());
    json_response(['success' => false, 'message' => 'Erro ao consultar estatísticas.'], 500);
}
