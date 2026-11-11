<?php

declare(strict_types=1);

namespace App\Exceptions;

final class ValidationException extends AppException
{
    public function __construct(string $message = 'Dados inválidos.', mixed $context = null)
    {
        parent::__construct($message, 422, 'validation_error', $context);
    }
}
