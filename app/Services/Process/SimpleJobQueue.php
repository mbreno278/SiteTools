<?php

declare(strict_types=1);

namespace App\Services\Process;

/**
 * Fila simples baseada em arquivos — compatível com PHP CLI no Windows.
 * Não exige Redis/Beanstalk. Worker: php tools/queue-worker.php
 *
 * Uso somente quando a tarefa for pesada demais para o request HTTP.
 */
final class SimpleJobQueue
{
    private string $dir;

    public function __construct(?string $dir = null)
    {
        $this->dir = $dir ?: (dirname(__DIR__, 3) . '/storage/queue');
        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0750, true);
        }
        foreach (['pending', 'running', 'done', 'failed'] as $sub) {
            $p = $this->dir . DIRECTORY_SEPARATOR . $sub;
            if (!is_dir($p)) {
                @mkdir($p, 0750, true);
            }
        }
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function push(string $type, array $payload): string
    {
        $id = bin2hex(random_bytes(16));
        $job = [
            'id' => $id,
            'type' => $type,
            'payload' => $payload,
            'created_at' => date('c'),
            'attempts' => 0,
        ];
        $path = $this->dir . '/pending/' . $id . '.json';
        $tmp = $path . '.tmp';
        file_put_contents($tmp, json_encode($job, JSON_UNESCAPED_UNICODE), LOCK_EX);
        rename($tmp, $path);
        return $id;
    }

    /**
     * Reserva o próximo job (rename atômico pending → running).
     *
     * @return array<string,mixed>|null
     */
    public function reserve(): ?array
    {
        $pending = glob($this->dir . '/pending/*.json') ?: [];
        sort($pending);
        foreach ($pending as $file) {
            $id = basename($file, '.json');
            $dest = $this->dir . '/running/' . $id . '.json';
            if (@rename($file, $dest)) {
                $data = json_decode((string) file_get_contents($dest), true);
                if (!is_array($data)) {
                    @unlink($dest);
                    continue;
                }
                $data['attempts'] = (int) ($data['attempts'] ?? 0) + 1;
                $data['reserved_at'] = date('c');
                file_put_contents($dest, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
                return $data;
            }
        }
        return null;
    }

    public function complete(string $id, array $result = []): void
    {
        $src = $this->dir . '/running/' . $id . '.json';
        if (!is_file($src)) {
            return;
        }
        $data = json_decode((string) file_get_contents($src), true) ?: [];
        $data['result'] = $result;
        $data['completed_at'] = date('c');
        $dest = $this->dir . '/done/' . $id . '.json';
        file_put_contents($dest, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
        @unlink($src);
    }

    public function fail(string $id, string $error): void
    {
        $src = $this->dir . '/running/' . $id . '.json';
        if (!is_file($src)) {
            return;
        }
        $data = json_decode((string) file_get_contents($src), true) ?: [];
        $data['error'] = $error;
        $data['failed_at'] = date('c');
        $dest = $this->dir . '/failed/' . $id . '.json';
        file_put_contents($dest, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
        @unlink($src);
    }
}
