<?php

declare(strict_types=1);

namespace App\Exceptions;

final class AuthorizationException extends AppException
{
    public function __construct(string $message = 'Acesso negado.', string $errorCode = 'forbidden', int $status = 403)
    {
        parent::__construct($message, $status, $errorCode);
    }
}
