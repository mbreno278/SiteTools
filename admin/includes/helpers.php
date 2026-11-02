<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function admin_url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/admin/' . ltrim($path, '/');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $submitted = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(419);
        exit('Token de segurança inválido. Atualize a página e tente novamente.');
    }
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function record_admin_activity(string $action, string $description = ''): void
{
    try {
        $stmt = db()->prepare(
            'INSERT INTO admin_activity_logs (admin_user_id, action, description, ip_address, created_at)
             VALUES (:admin_id, :action, :description, :ip, NOW())'
        );
        $stmt->execute([
            ':admin_id' => $_SESSION['admin']['id'] ?? null,
            ':action' => $action,
            ':description' => mb_substr($description, 0, 500),
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Throwable $exception) {
        error_log('[SiteTools admin activity] ' . $exception->getMessage());
    }
}
