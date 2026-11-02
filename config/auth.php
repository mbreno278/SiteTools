<?php

declare(strict_types=1);

/**
 * SiteTools — autenticação (sessão)
 */

require_once __DIR__ . '/csrf.php';

function auth_user(): ?array
{
    if (empty($_SESSION['user_id']) || !is_int($_SESSION['user_id']) && !ctype_digit((string) $_SESSION['user_id'])) {
        return null;
    }

    return [
        'id'    => (int) $_SESSION['user_id'],
        'name'  => (string) ($_SESSION['user_name'] ?? ''),
        'email' => (string) ($_SESSION['user_email'] ?? ''),
    ];
}

function auth_check(): bool
{
    return auth_user() !== null;
}

function auth_login(int $id, string $name, string $email): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $id;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
}

function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', (bool) $p['secure'], (bool) $p['httponly']);
    }
    session_destroy();
}

function auth_json(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function auth_pdo(): PDO
{
    require_once __DIR__ . '/database.php';
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        auth_json(['error' => 'Banco de dados indisponível.'], 500);
    }
    return $pdo;
}
