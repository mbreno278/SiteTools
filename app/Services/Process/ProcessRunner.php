<?php

declare(strict_types=1);

namespace App\Services\Process;

/**
 * Execução segura de processos externos (proc_open + args separados + timeout).
 * Compatível com Windows (WAMP) e Linux.
 */
final class ProcessRunner
{
    /**
     * @param list<string> $command argv[0] = binário, demais = argumentos
     * @return array{exit:int,stdout:string,stderr:string,timed_out:bool}
     */
    public function run(array $command, ?string $cwd = null, int $timeoutSeconds = 120, array $env = []): array
    {
        if ($command === []) {
            return ['exit' => -1, 'stdout' => '', 'stderr' => 'Comando vazio.', 'timed_out' => false];
        }

        if (!function_exists('proc_open')) {
            return ['exit' => -1, 'stdout' => '', 'stderr' => 'proc_open desabilitado no PHP.', 'timed_out' => false];
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $options = [];
        // bypass_shell evita interpretação de shell (mais seguro)
        if (PHP_OS_FAMILY !== 'Windows') {
            $options['bypass_shell'] = true;
        } else {
            // No Windows, proc_open com array funciona no PHP 7.4+ / 8+
            $options['bypass_shell'] = true;
        }

        $pipes = [];
        $process = @proc_open($command, $descriptors, $pipes, $cwd, $env === [] ? null : $env, $options);

        if (!is_resource($process)) {
            // Fallback controlado: ainda com escapeshellarg, sem concatenação livre
            $line = implode(' ', array_map('escapeshellarg', $command));
            $process = @proc_open($line, $descriptors, $pipes, $cwd, $env === [] ? null : $env);
        }

        if (!is_resource($process)) {
            return ['exit' => -1, 'stdout' => '', 'stderr' => 'Falha ao iniciar processo.', 'timed_out' => false];
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = '';
        $stderr = '';
        $start = time();
        $timedOut = false;

        while (true) {
            $status = proc_get_status($process);
            $stdout .= stream_get_contents($pipes[1]) ?: '';
            $stderr .= stream_get_contents($pipes[2]) ?: '';

            if (!$status['running']) {
                break;
            }

            if ((time() - $start) > $timeoutSeconds) {
                $timedOut = true;
                // Tenta encerrar de forma progressiva
                @proc_terminate($process);
                usleep(200000);
                $st = proc_get_status($process);
                if (!empty($st['running'])) {
                    @proc_terminate($process, 9);
                }
                break;
            }

            usleep(100000);
        }

        $stdout .= stream_get_contents($pipes[1]) ?: '';
        $stderr .= stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);

        if ($timedOut) {
            return [
                'exit' => -1,
                'stdout' => $stdout,
                'stderr' => trim($stderr . ' Tempo limite excedido.'),
                'timed_out' => true,
            ];
        }

        return [
            'exit' => (int) $exit,
            'stdout' => $stdout,
            'stderr' => $stderr,
            'timed_out' => false,
        ];
    }
}
