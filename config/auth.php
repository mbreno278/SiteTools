<?php

declare(strict_types=1);

/**
 * SiteTools — autenticação segura
 *
 * - Limite de tentativas de login
 * - Expiração de sessão
 * - Verificação de e-mail
 * - Recuperação de senha
 */

require_once __DIR__ . '/csrf.php';

/** Tempo máximo de sessão (segundos) — 2 horas */
const AUTH_SESSION_LIFETIME = 7200;

/** Inatividade máxima (segundos) — 30 minutos */
const AUTH_SESSION_IDLE = 1800;

/** Tentativas máximas por e-mail ou IP */
const AUTH_MAX_ATTEMPTS = 5;

/** Janela de bloqueio (segundos) — 15 minutos */
const AUTH_LOCKOUT_WINDOW = 900;

/** Validade do token de verificação / reset (segundos) — 1 hora */
const AUTH_TOKEN_TTL = 3600;

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
    // 1) Conexão já aberta no escopo global (page.php / index.php)
    if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
        return $GLOBALS['pdo'];
    }

    // 2) Abre nova conexão
    $dbFile = __DIR__ . '/database.php';
    if (!is_file($dbFile)) {
        auth_json(['error' => 'Arquivo config/database.php não encontrado.'], 500);
    }

    try {
        // include em escopo de função: variáveis do arquivo ficam locais aqui
        include $dbFile;
    } catch (Throwable $e) {
        auth_json([
            'error' => 'Falha na conexão com o banco. Verifique DB_HOST, DB_NAME, DB_USER e DB_PASSWORD.',
        ], 500);
    }

    if (!isset($pdo) || !($pdo instanceof PDO)) {
        auth_json([
            'error' => 'Banco de dados indisponível. Confira config/database.php e o usuário MySQL.',
        ], 500);
    }

    $GLOBALS['pdo'] = $pdo;
    return $pdo;
}

function auth_client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return is_string($ip) ? substr($ip, 0, 45) : '0.0.0.0';
}

function auth_user_agent(): string
{
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return is_string($ua) ? substr($ua, 0, 255) : '';
}

/* ===================== Sessão ===================== */

function auth_touch_session(): void
{
    $_SESSION['_auth_last_activity'] = time();
    if (empty($_SESSION['_auth_started_at'])) {
        $_SESSION['_auth_started_at'] = time();
    }
}

function auth_session_valid(): bool
{
    if (empty($_SESSION['user_id'])) {
        return false;
    }

    $now = time();
    $started = (int) ($_SESSION['_auth_started_at'] ?? $now);
    $last = (int) ($_SESSION['_auth_last_activity'] ?? $now);

    if (($now - $started) > AUTH_SESSION_LIFETIME) {
        return false;
    }
    if (($now - $last) > AUTH_SESSION_IDLE) {
        return false;
    }

    return true;
}

function auth_user(): ?array
{
    if (!auth_session_valid()) {
        if (!empty($_SESSION['user_id'])) {
            // Sessão expirada — limpa sem destruir cookie CSRF se possível
            auth_clear_user_session();
        }
        return null;
    }

    auth_touch_session();

    return [
        'id'    => (int) $_SESSION['user_id'],
        'name'  => (string) ($_SESSION['user_name'] ?? ''),
        'email' => (string) ($_SESSION['user_email'] ?? ''),
        'email_verified' => !empty($_SESSION['user_email_verified']),
    ];
}

function auth_check(): bool
{
    return auth_user() !== null;
}

function auth_clear_user_session(): void
{
    unset(
        $_SESSION['user_id'],
        $_SESSION['user_name'],
        $_SESSION['user_email'],
        $_SESSION['user_email_verified'],
        $_SESSION['_auth_started_at'],
        $_SESSION['_auth_last_activity']
    );
}

function auth_login(int $id, string $name, string $email, bool $emailVerified = false): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $id;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_email_verified'] = $emailVerified;
    $_SESSION['_auth_started_at'] = time();
    $_SESSION['_auth_last_activity'] = time();

    // Registra sessão no banco (melhor esforço)
    try {
        $pdo = auth_pdo();
        $pdo->prepare(
            'INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, expires_at)
             VALUES (:uid, :sid, :ip, :ua, :exp)
             ON DUPLICATE KEY UPDATE last_activity = NOW(), expires_at = VALUES(expires_at)'
        )->execute([
            'uid' => $id,
            'sid' => session_id(),
            'ip'  => auth_client_ip(),
            'ua'  => auth_user_agent(),
            'exp' => date('Y-m-d H:i:s', time() + AUTH_SESSION_LIFETIME),
        ]);
    } catch (Throwable) {
        // tabela pode ainda não existir
    }
}

function auth_logout(): void
{
    $sid = session_id();

    try {
        if (!empty($_SESSION['user_id']) && $sid !== '') {
            // Não usa auth_pdo() aqui para não interromper o logout se o DB falhar
            require_once __DIR__ . '/database.php';
            if (isset($pdo) && $pdo instanceof PDO) {
                $pdo->prepare('DELETE FROM user_sessions WHERE session_id = :sid')
                    ->execute(['sid' => $sid]);
            }
        }
    } catch (Throwable) {
        // ignora falha de banco no logout
    }

    auth_clear_user_session();

    // Novo id de sessão (mantém CSRF se ainda existir na sessão)
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/* ===================== Rate limit ===================== */

function auth_is_locked(string $email): bool
{
    try {
        $pdo = auth_pdo();
        $since = date('Y-m-d H:i:s', time() - AUTH_LOCKOUT_WINDOW);
        $ip = auth_client_ip();

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE attempted_at >= :since
               AND (email = :email OR ip_address = :ip)'
        );
        $stmt->execute(['since' => $since, 'email' => $email, 'ip' => $ip]);
        $count = (int) $stmt->fetchColumn();

        return $count >= AUTH_MAX_ATTEMPTS;
    } catch (Throwable) {
        return false;
    }
}

function auth_record_attempt(string $email): void
{
    try {
        $pdo = auth_pdo();
        $pdo->prepare(
            'INSERT INTO login_attempts (email, ip_address) VALUES (:email, :ip)'
        )->execute(['email' => $email, 'ip' => auth_client_ip()]);
    } catch (Throwable) {
    }
}

function auth_clear_attempts(string $email): void
{
    try {
        $pdo = auth_pdo();
        $pdo->prepare('DELETE FROM login_attempts WHERE email = :email OR ip_address = :ip')
            ->execute(['email' => $email, 'ip' => auth_client_ip()]);
    } catch (Throwable) {
    }
}

/* ===================== Tokens ===================== */

function auth_random_token(): string
{
    return bin2hex(random_bytes(32));
}

/**
 * Em ambiente local sem SMTP, grava o link em arquivo de log.
 * Em produção, substitua por envio real de e-mail.
 */
function auth_send_mail(string $to, string $subject, string $body): bool
{
    $from = getenv('MAIL_FROM') ?: 'noreply@sitetools.local';

    // Tenta mail() do PHP
    $headers = "From: {$from}\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    $sent = @mail($to, $subject, $body, $headers);

    // Sempre registra em log local (útil no Wamp)
    $logDir = dirname(__DIR__) . '/uploads';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/mail-log.txt';
    $entry = sprintf(
        "[%s] TO:%s | %s\n%s\n---\n",
        date('Y-m-d H:i:s'),
        $to,
        $subject,
        $body
    );
    @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);

    return $sent || true; // no Wamp consideramos ok se logou
}

function auth_app_base_url(): string
{
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    // tools/xxx.php → sobe um nível
    $base = rtrim(str_replace('\\', '/', dirname(dirname($script))), '/');
    if ($base === '/' || $base === '\\') {
        $base = '';
    }
    return ($https ? 'https' : 'http') . '://' . $host . $base;
}
