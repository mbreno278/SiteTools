<?php
declare(strict_types=1);

    require_once __DIR__ . '/includes/bootstrap.php';
    require_once __DIR__ . '/includes/auth.php';
    require_once dirname(__DIR__) . '/config/csrf.php';

    /**
     * Logout administrativo — apenas POST com CSRF.
     */
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        // Fallback amigável: formulário de confirmação mínima
        header('Content-Type: text/html; charset=utf-8');
        $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
        echo '<!doctype html><meta charset="utf-8"><title>Sair</title>';
        echo '<form method="post"><input type="hidden" name="_csrf" value="' . $token . '">';
        echo '<p>Confirmar saída do painel?</p><button type="submit">Sair</button></form>';
        exit;
    }

    csrf_verify();

    if (admin_logged_in()) {
        try {
            if (function_exists('record_admin_activity')) {
                record_admin_activity('logout', 'Logout administrativo realizado.');
            }
        } catch (Throwable) {
        }
    }

    admin_force_logout();

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            (bool) ($params['secure'] ?? false),
            (bool) ($params['httponly'] ?? true)
        );
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }

    if (function_exists('redirect') && function_exists('admin_url')) {
        redirect(admin_url('login.php'));
    }

    header('Location: login.php');
    exit;
