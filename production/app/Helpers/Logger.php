<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Log seguro em storage/logs — nunca registra senhas/tokens completos.
 */
final class Logger
{
    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    private static function write(string $level, string $message, array $context): void
    {
        $dir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }

        $safe = self::redact($context);
        $line = sprintf(
            "[%s] %s %s %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            $safe !== [] ? json_encode($safe, JSON_UNESCAPED_UNICODE) : ''
        );

        @file_put_contents($dir . '/app.log', $line, FILE_APPEND | LOCK_EX);
    }

    private static function redact(array $context): array
    {
        $sensitive = ['password', 'password_hash', 'token', 'reset_token', 'verify_token', 'authorization', 'cookie'];
        $out = [];
        foreach ($context as $key => $value) {
            $k = strtolower((string) $key);
            if (in_array($k, $sensitive, true) || str_contains($k, 'password') || str_contains($k, 'token')) {
                $out[$key] = '[redacted]';
                continue;
            }
            if (is_string($value) && preg_match('/[a-f0-9]{32,}/i', $value)) {
                $out[$key] = preg_replace('/[a-f0-9]{32,}/i', '[redacted]', $value);
                continue;
            }
            $out[$key] = $value;
        }
        return $out;
    }
}
