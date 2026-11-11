<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/csrf.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$user = auth_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Faça login para gerenciar favoritos.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

csrf_verify();

$payload = [];
if (function_exists('csrf_json_input')) {
    $payload = csrf_json_input() ?? [];
}
if ($payload === [] && !empty($_POST['tool_id'])) {
    $payload = ['tool_id' => $_POST['tool_id']];
}

$toolId = filter_var($payload['tool_id'] ?? null, FILTER_VALIDATE_INT);
if (!$toolId) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Ferramenta inválida.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Serviço indisponível.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$uid = (int) $user['id'];

$check = $pdo->prepare('SELECT id FROM tools WHERE id = :id AND is_active = 1 LIMIT 1');
$check->execute(['id' => $toolId]);
if (!$check->fetchColumn()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Ferramenta não encontrada.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Toggle atômico: evita duplicata via UNIQUE + INSERT IGNORE
$find = $pdo->prepare('SELECT id FROM user_favorites WHERE user_id = :uid AND tool_id = :tid LIMIT 1');
$find->execute(['uid' => $uid, 'tid' => $toolId]);
$existing = $find->fetchColumn();

if ($existing) {
    $del = $pdo->prepare('DELETE FROM user_favorites WHERE user_id = :uid AND tool_id = :tid');
    $del->execute(['uid' => $uid, 'tid' => $toolId]);
    $active = false;
} else {
    $ins = $pdo->prepare('INSERT IGNORE INTO user_favorites (user_id, tool_id) VALUES (:uid, :tid)');
    $ins->execute(['uid' => $uid, 'tid' => $toolId]);
    $active = true;
}

echo json_encode([
    'success' => true,
    'message' => $active ? 'Adicionado aos favoritos.' : 'Removido dos favoritos.',
    'active' => $active,
    'data' => ['tool_id' => $toolId, 'active' => $active],
], JSON_UNESCAPED_UNICODE);
