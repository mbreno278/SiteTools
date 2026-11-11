<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/user-area.php';

$currentUser = user_area_require_login();

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(503);
    exit('Serviço indisponível.');
}

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('Arquivo inválido.');
}

$stmt = $pdo->prepare(
    "SELECT id, original_name, storage_path, mime_type
     FROM user_files
     WHERE id = :id AND user_id = :uid AND status = 'available'
       AND (expires_at IS NULL OR expires_at > NOW())
     LIMIT 1"
);
$stmt->execute(['id' => $id, 'uid' => (int) $currentUser['id']]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    http_response_code(404);
    exit('Arquivo não encontrado.');
}

$root = realpath(__DIR__ . '/../storage/user-files');
$path = realpath((string) $file['storage_path']);

if (
    !$root
    || !$path
    || !str_starts_with($path, $root . DIRECTORY_SEPARATOR)
    || !is_file($path)
) {
    http_response_code(404);
    exit('Arquivo indisponível.');
}

$name = basename((string) $file['original_name']);
$mime = (string) ($file['mime_type'] ?: 'application/octet-stream');

header('X-Content-Type-Options: nosniff');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $name) . '"');
header('Content-Length: ' . (string) filesize($path));
header('Cache-Control: private, no-store');
readfile($path);
exit;
