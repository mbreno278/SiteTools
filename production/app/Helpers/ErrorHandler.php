<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Exceptions\AppException;
use Throwable;

/**
 * Converte exceções em respostas JSON seguras.
 */
final class ErrorHandler
{
    public static function register(): void
    {
        set_exception_handler([self::class, 'handleException']);
    }

    public static function handleException(Throwable $e): void
    {
        if ($e instanceof AppException) {
            Logger::error($e->getMessage(), [
                'code' => $e->getErrorCode(),
                'status' => $e->getStatusCode(),
            ]);
            Response::error(
                $e->getMessage(),
                $e->getStatusCode(),
                $e->getContext(),
                $e->getErrorCode()
            );
        }

        Logger::error($e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'type' => $e::class,
        ]);

        $isLocal = in_array(
            strtolower((string) (getenv('APP_ENV') ?: 'production')),
            ['local', 'development', 'dev'],
            true
        );

        Response::error(
            $isLocal ? $e->getMessage() : 'Ocorreu um erro interno. Tente novamente.',
            500,
            null,
            'internal_error'
        );
    }
}
