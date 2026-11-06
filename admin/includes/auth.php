<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

/** Sessão admin: 2 horas absolutas, 45 min de inatividade */
const ADMIN_SESSION_LIFETIME = 7200;
const ADMIN_SESSION_IDLE = 2700;
const ADMIN_MAX_LOGIN_ATTEMPTS = 5;
const ADMIN_LOCKOUT_SECONDS = 900;

function admin_logged_in(): bool
{
    if (empty($_SESSION['admin']['id'])) {
        return false;
    }

    $now = time();
    $started = (int) ($_SESSION['admin']['_started_at'] ?? $now);
    $last = (int) ($_SESSION['admin']['_last_activity'] ?? $now);

    if (($now - $started) > ADMIN_SESSION_LIFETIME || ($now - $last) > ADMIN_SESSION_IDLE) {
        admin_force_logout();
        return false;
    }

    $_SESSION['admin']['_last_activity'] = $now;
    return true;
}

function admin_force_logout(): void
{
    unset($_SESSION['admin']);
}

function require_admin(): void
{
    if (admin_logged_in()) {
        return;
    }

    // APIs e fetch devem receber JSON, não redirect HTML
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $isApi = str_contains($uri, '/api/')
        || str_contains($uri, '/api\\')
        || (isset($_SERVER['HTTP_ACCEPT']) && str_contains((string) $_SERVER['HTTP_ACCEPT'], 'application/json'))
        || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strcasecmp((string) $_SERVER['HTTP_X_REQUESTED_WITH'], 'XMLHttpRequest') === 0);

    if ($isApi) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Sessão expirada. Faça login novamente.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    redirect(admin_url('login.php'));
}

function current_admin(): array
{
    return $_SESSION['admin'] ?? [];
}

function admin_login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['admin'] = [
        'id' => (int) $user['id'],
        'name' => (string) $user['name'],
        'email' => (string) $user['email'],
        'role' => (string) $user['role'],
        '_started_at' => time(),
        '_last_activity' => time(),
    ];
}

function admin_login_attempts_key(string $email): string
{
    return 'admin_login_attempts_' . hash('sha256', strtolower($email) . '|' . ($_SERVER['REMOTE_ADDR'] ?? ''));
}

function admin_is_login_locked(string $email): bool
{
    $key = admin_login_attempts_key($email);
    $data = $_SESSION[$key] ?? null;
    if (!is_array($data)) {
        return false;
    }
    $count = (int) ($data['count'] ?? 0);
    $until = (int) ($data['locked_until'] ?? 0);
    if ($until > time()) {
        return true;
    }
    if ($until > 0 && $until <= time()) {
        unset($_SESSION[$key]);
        return false;
    }
    return false;
}

function admin_record_login_failure(string $email): void
{
    $key = admin_login_attempts_key($email);
    $data = $_SESSION[$key] ?? ['count' => 0, 'locked_until' => 0];
    $data['count'] = (int) $data['count'] + 1;
    if ($data['count'] >= ADMIN_MAX_LOGIN_ATTEMPTS) {
        $data['locked_until'] = time() + ADMIN_LOCKOUT_SECONDS;
    }
    $_SESSION[$key] = $data;
}

function admin_clear_login_failures(string $email): void
{
    unset($_SESSION[admin_login_attempts_key($email)]);
}
