<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Exceção de aplicação (mensagem segura para o cliente).
 */
class AppException extends Exception
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 400,
        private readonly ?string $errorCode = null,
        private readonly mixed $context = null,
    ) {
        parent::__construct($message, $statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getContext(): mixed
    {
        return $this->context;
    }
}
