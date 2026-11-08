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

function admin_is_login_locked(string $email): bool
{
    try {
        $since = date('Y-m-d H:i:s', time() - ADMIN_LOCKOUT_SECONDS);
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $stmt = db()->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE attempted_at >= :since
               AND (email = :email OR ip_address = :ip)'
        );
        $stmt->execute([
            'since' => $since,
            'email' => strtolower(trim($email)),
            'ip' => $ip,
        ]);
        return (int) $stmt->fetchColumn() >= ADMIN_MAX_LOGIN_ATTEMPTS;
    } catch (Throwable $e) {
        // Se o armazenamento do rate limit falhar, bloqueia a tentativa.
        error_log('[admin rate limit] ' . $e->getMessage());
        return true;
    }
}

function admin_record_login_failure(string $email): void
{
    try {
        db()->prepare(
            'INSERT INTO login_attempts (email, ip_address) VALUES (:email, :ip)'
        )->execute([
            'email' => strtolower(trim($email)),
            'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'),
        ]);
    } catch (Throwable $e) {
        error_log('[admin rate limit record] ' . $e->getMessage());
    }
}

function admin_clear_login_failures(string $email): void
{
    try {
        db()->prepare(
            'DELETE FROM login_attempts WHERE email = :email OR ip_address = :ip'
        )->execute([
            'email' => strtolower(trim($email)),
            'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'),
        ]);
    } catch (Throwable $e) {
        error_log('[admin rate limit clear] ' . $e->getMessage());
    }
}
