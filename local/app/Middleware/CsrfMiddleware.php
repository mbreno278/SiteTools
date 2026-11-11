<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exceptions\AuthorizationException;

final class CsrfMiddleware
{
    public function handle(): void
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        if (!function_exists('csrf_verify')) {
            throw new AuthorizationException('Proteção CSRF indisponível.', 'csrf_unavailable', 503);
        }

        // csrf_verify encerra a request em caso de falha
        csrf_verify();
    }
}
