<?php
/**
 * Rotina de limpeza: temp, fila antiga, arquivos de usuário expirados, logs grandes.
 *
 * CLI:  php tools/cleanup.php
 * Web:  /tools/cleanup.php?token=SEU_CLEANUP_TOKEN  (somente se CLEANUP_TOKEN estiver definido)
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/app.php';

$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    $token = (string) ($_GET['token'] ?? '');
    $expected = (string) (defined('CLEANUP_TOKEN') ? CLEANUP_TOKEN : env('CLEANUP_TOKEN', ''));
    if ($expected === '' || !hash_equals($expected, $token)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        exit('Forbidden');
    }
}

$storage = defined('STORAGE_PATH') ? STORAGE_PATH : (dirname(__DIR__) . '/storage');
$log = [];

$removeOlderThan = static function (string $dir, int $maxAgeSeconds) use (&$log): int {
    if (!is_dir($dir)) {
        return 0;
    }
    $count = 0;
    $now = time();
    try {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $item) {
            $path = $item->getPathname();
            if ($item->isFile()) {
                if (($now - $item->getMTime()) > $maxAgeSeconds) {
                    if (@unlink($path)) {
                        $count++;
                    }
                }
            } elseif ($item->isDir()) {
                @rmdir($path); // só remove se vazio
            }
        }
    } catch (Throwable $e) {
        $log[] = 'erro em ' . $dir . ': ' . $e->getMessage();
    }
    return $count;
};

// 1) Temporários (> 6 horas)
$n = $removeOlderThan($storage . '/temp', 6 * 3600);
$log[] = "temp removidos: {$n}";

// 2) Jobs da fila em done/failed (> 7 dias)
$n = $removeOlderThan($storage . '/queue/done', 7 * 86400);
$log[] = "queue/done removidos: {$n}";
$n = $removeOlderThan($storage . '/queue/failed', 14 * 86400);
$log[] = "queue/failed removidos: {$n}";

// 3) Arquivos de usuário expirados (DB + disco)
$filesMarked = 0;
$filesUnlinked = 0;
try {
    require_once __DIR__ . '/../config/database.php';
    if (isset($pdo) && $pdo instanceof PDO) {
        $root = realpath($storage . '/user-files');
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
                    $filesUnlinked++;
                }
            }
            $pdo->prepare("UPDATE user_files SET status = 'expired' WHERE id = :id")
                ->execute(['id' => (int) $row['id']]);
            $filesMarked++;
        }
    }
} catch (Throwable $e) {
    $log[] = 'user_files: ' . $e->getMessage();
}
$log[] = "user_files marcados={$filesMarked} unlinked={$filesUnlinked}";

// 4) Rotaciona log PHP se > 10 MB
$phpLog = $storage . '/logs/php-error.log';
if (is_file($phpLog) && filesize($phpLog) > 10 * 1024 * 1024) {
    @rename($phpLog, $phpLog . '.' . date('Ymd_His'));
    $log[] = 'php-error.log rotacionado';
}

$msg = '[' . date('c') . "] cleanup\n" . implode("\n", $log) . "\n";
@file_put_contents($storage . '/logs/cleanup.log', $msg, FILE_APPEND | LOCK_EX);

if ($isCli) {
    echo $msg;
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
}
