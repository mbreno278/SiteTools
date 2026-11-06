<?php
require __DIR__ . '/_bootstrap.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(404);
    exit('Arquivo não encontrado.');
}

$stmt = $pdo->prepare(
    "SELECT original_name, storage_path, mime_type
     FROM user_files
     WHERE id = :id
       AND user_id = :uid
       AND status = 'available'
       AND (expires_at IS NULL OR expires_at > NOW())
     LIMIT 1"
);
$stmt->execute(['id' => $id, 'uid' => (int) $currentUser['id']]);
$file = $stmt->fetch();

if (!$file) {
    http_response_code(404);
    exit('Arquivo não encontrado.');
}

$root = realpath(__DIR__ . '/../storage/user-files');
$path = realpath($file['storage_path']);

if (!$root || !$path || !str_starts_with($path, $root . DIRECTORY_SEPARATOR) || !is_file($path)) {
    http_response_code(404);
    exit('Arquivo indisponível.');
}

$name = basename((string) $file['original_name']);

header('X-Content-Type-Options: nosniff');
header('Content-Type: ' . ($file['mime_type'] ?: 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . rawurlencode($name) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
