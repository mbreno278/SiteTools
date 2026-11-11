<?php
/**
 * Backup lógico do MySQL via mysqldump.
 *
 * CLI: php tools/backup-db.php
 * Saída: storage/backups/db_YYYYMMDD_HHMMSS.sql
 *
 * Requer mysqldump no PATH ou MYSQLDUMP_PATH no .env
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/app.php';

$host = (string) env('DB_HOST', 'localhost');
$port = (string) env('DB_PORT', '3306');
$name = (string) env('DB_NAME', '');
$user = (string) env('DB_USER', '');
$pass = (string) env('DB_PASSWORD', '');

if ($name === '' || $user === '') {
    fwrite(STDERR, "DB_NAME/DB_USER não configurados.\n");
    exit(1);
}

$storage = defined('STORAGE_PATH') ? STORAGE_PATH : (dirname(__DIR__) . '/storage');
$dir = $storage . DIRECTORY_SEPARATOR . 'backups';
if (!is_dir($dir) && !@mkdir($dir, 0750, true)) {
    fwrite(STDERR, "Não foi possível criar {$dir}\n");
    exit(1);
}

$dumpBin = (string) env('MYSQLDUMP_PATH', 'mysqldump');
$out = $dir . DIRECTORY_SEPARATOR . 'db_' . date('Ymd_His') . '.sql';

// Argumentos separados — senha via env MYSQL_PWD (evita aparecer em process list em alguns SO)
$cmd = [
    $dumpBin,
    '--host=' . $host,
    '--port=' . $port,
    '--user=' . $user,
    '--single-transaction',
    '--routines',
    '--triggers',
    '--default-character-set=utf8mb4',
    $name,
];

$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['file', $out, 'w'],
    2 => ['pipe', 'w'],
];

$env = $_ENV;
$env['MYSQL_PWD'] = $pass;

$process = proc_open($cmd, $descriptors, $pipes, null, $env, ['bypass_shell' => true]);
if (!is_resource($process)) {
    // Fallback Windows
    $line = escapeshellarg($dumpBin)
        . ' --host=' . escapeshellarg($host)
        . ' --port=' . escapeshellarg($port)
        . ' --user=' . escapeshellarg($user)
        . ' --single-transaction --routines --triggers --default-character-set=utf8mb4 '
        . escapeshellarg($name);
    $process = proc_open($line, $descriptors, $pipes, null, $env);
}

if (!is_resource($process)) {
    fwrite(STDERR, "Falha ao iniciar mysqldump. Verifique se está no PATH.\n");
    exit(1);
}

fclose($pipes[0]);
$stderr = stream_get_contents($pipes[2]) ?: '';
fclose($pipes[2]);
$exit = proc_close($process);

if ($exit !== 0 || !is_file($out) || filesize($out) < 50) {
    @unlink($out);
    fwrite(STDERR, "Backup falhou (exit={$exit}): {$stderr}\n");
    exit(1);
}

// Mantém só os 10 backups mais recentes
$files = glob($dir . '/db_*.sql') ?: [];
rsort($files);
foreach (array_slice($files, 10) as $old) {
    @unlink($old);
}

echo "OK {$out} (" . filesize($out) . " bytes)\n";
