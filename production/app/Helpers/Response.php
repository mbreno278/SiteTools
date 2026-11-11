<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Respostas HTTP JSON consistentes.
 */
final class Response
{
    public static function json(array $payload, int $status = 200): never
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: no-store');
        }

        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function success(mixed $data = null, string $message = 'OK', int $status = 200): never
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public static function error(string $message, int $status = 400, mixed $data = null, ?string $code = null): never
    {
        $payload = [
            'success' => false,
            'message' => $message,
            'data' => $data,
        ];
        if ($code !== null) {
            $payload['code'] = $code;
        }
        self::json($payload, $status);
    }
}
