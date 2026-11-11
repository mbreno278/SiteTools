<?php
/**
 * Worker CLI da fila simples (Windows/WAMP ok).
 * Uso: php tools/queue-worker.php
 * Opcional: loop contínuo com --loop
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require_once __DIR__ . '/../app/bootstrap.php';

use App\Services\Process\SimpleJobQueue;

$loop = in_array('--loop', $argv ?? [], true);
$queue = new SimpleJobQueue();

do {
    $job = $queue->reserve();
    if ($job === null) {
        if (!$loop) {
            echo "No jobs.\n";
            break;
        }
        usleep(500000);
        continue;
    }

    $id = (string) $job['id'];
    $type = (string) ($job['type'] ?? '');
    echo date('c') . " job={$id} type={$type}\n";

    try {
        // Handlers por tipo — expandir conforme necessário
        match ($type) {
            'ping' => $queue->complete($id, ['pong' => true]),
            default => $queue->fail($id, 'Tipo de job desconhecido: ' . $type),
        };
    } catch (Throwable $e) {
        $queue->fail($id, $e->getMessage());
        fwrite(STDERR, $e->getMessage() . "\n");
    }
} while ($loop);
