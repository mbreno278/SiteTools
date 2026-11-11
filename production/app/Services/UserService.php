<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Repositories\UserRepository;
use PDO;

final class UserService
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public static function make(PDO $pdo): self
    {
        return new self(new UserRepository($pdo));
    }

    public function updateName(int $userId, string $name): void
    {
        $name = trim($name);
        if (mb_strlen($name) < 2 || mb_strlen($name) > 80) {
            throw new ValidationException('Informe um nome válido (2 a 80 caracteres).');
        }
        $this->users->updateName($userId, $name);
    }

    public function changePassword(int $userId, string $current, string $new, string $confirm): void
    {
        if ($new === '' || strlen($new) < 8) {
            throw new ValidationException('A nova senha deve ter no mínimo 8 caracteres.');
        }
        if ($new !== $confirm) {
            throw new ValidationException('A confirmação da senha não confere.');
        }

        $user = $this->users->findById($userId);
        if (!$user || empty($user['password_hash'])) {
            throw new ValidationException('Usuário não encontrado.');
        }
        if (!password_verify($current, (string) $user['password_hash'])) {
            throw new ValidationException('Senha atual incorreta.');
        }

        $this->users->updatePasswordHash($userId, password_hash($new, PASSWORD_DEFAULT));
    }
}
