<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exceptions\AuthorizationException;

/**
 * Exige uma sessão de usuário válida.
 *
 * O middleware usa a API de autenticação já existente em config/auth.php,
 * evitando manter uma segunda implementação de sessão.
 */
final class AuthMiddleware
{
    public function handle(): array
    {
        if (!function_exists('auth_user')) {
            throw new AuthorizationException('Serviço de autenticação indisponível.', 'auth_unavailable', 503);
        }

        $user = auth_user();
        if (!is_array($user) || (int) ($user['id'] ?? 0) < 1) {
            throw new AuthorizationException('Faça login para continuar.', 'authentication_required', 401);
        }

        return $user;
    }
}
