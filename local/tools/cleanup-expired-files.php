<?php
/**
 * Limpa arquivos expirados (user_files + disco).
 * Uso (CLI ou cron): php tools/cleanup-expired-files.php
 * Não deve ser acessível publicamente sem proteção.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    // Bloqueio básico se chamado via web
    $token = $_GET['token'] ?? '';
    $expected = getenv('CLEANUP_TOKEN') ?: '';
    if ($expected === '' || !hash_equals($expected, (string) $token)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

require_once __DIR__ . '/../config/database.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    fwrite(STDERR, "DB unavailable\n");
    exit(1);
}

$root = realpath(__DIR__ . '/../storage/user-files');
$removed = 0;
$marked = 0;

$stmt = $pdo->query(
    "SELECT id, storage_path FROM user_files
     WHERE status = 'available'
       AND expires_at IS NOT NULL
       AND expires_at <= NOW()
     LIMIT 500"
);
$rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

foreach ($rows as $row) {
    $path = realpath((string) $row['storage_path']);
    if ($root && $path && str_starts_with($path, $root . DIRECTORY_SEPARATOR) && is_file($path)) {
        if (@unlink($path)) {
            $removed++;
        }
    }
    $pdo->prepare("UPDATE user_files SET status = 'expired' WHERE id = :id")
        ->execute(['id' => (int) $row['id']]);
    $marked++;
}

$msg = sprintf("[%s] marked=%d files_removed=%d\n", date('c'), $marked, $removed);
if (PHP_SAPI === 'cli') {
    echo $msg;
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
}
