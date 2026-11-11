<?php

declare(strict_types=1);

/**
 * SiteTools — proteção CSRF
 *
 * Uso:
 *   require_once __DIR__ . '/csrf.php';
 *   // No HTML: <?= csrf_meta() ?>  ou  <?= csrf_field() ?>
 *   // No POST (tools): csrf_verify();
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'name'            => 'SITETOOLSSESSID',
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'use_strict_mode' => true,
        'use_only_cookies'=> true,
    ]);
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    return '<input type="hidden" name="_csrf" value="' . $token . '">';
}

function csrf_meta(): string
{
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    return '<meta name="csrf-token" content="' . $token . '">';
}

/**
 * Valida o token CSRF da requisição atual.
 * Aceita:
 *  - POST field _csrf
 *  - Header X-CSRF-Token
 *  - JSON body { "_csrf": "..." }
 */
function csrf_verify(): void
{
    $sessionToken = $_SESSION['_csrf_token'] ?? '';

    $requestToken = '';

    if (isset($_POST['_csrf']) && is_string($_POST['_csrf'])) {
        $requestToken = $_POST['_csrf'];
    } elseif (!empty($_SERVER['HTTP_X_CSRF_TOKEN']) && is_string($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $requestToken = $_SERVER['HTTP_X_CSRF_TOKEN'];
    } else {
        // JSON body
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            if (is_string($raw) && $raw !== '') {
                $json = json_decode($raw, true);
                if (is_array($json) && isset($json['_csrf']) && is_string($json['_csrf'])) {
                    $requestToken = $json['_csrf'];
                }
                // Guarda o JSON para os tools reutilizarem (evita consumir o stream duas vezes)
                $GLOBALS['_csrf_json_body'] = is_array($json) ? $json : null;
            }
        }
    }

    if (
        !is_string($sessionToken) || $sessionToken === ''
        || !is_string($requestToken) || $requestToken === ''
        || !hash_equals($sessionToken, $requestToken)
    ) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode(['error' => 'Token CSRF inválido ou ausente.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/**
 * Lê o corpo JSON uma vez (compatível com csrf_verify).
 */
function csrf_json_input(): ?array
{
    if (isset($GLOBALS['_csrf_json_body']) && is_array($GLOBALS['_csrf_json_body'])) {
        return $GLOBALS['_csrf_json_body'];
    }

    $raw = file_get_contents('php://input');
    if (!is_string($raw) || $raw === '') {
        return null;
    }

    $json = json_decode($raw, true);

    return is_array($json) ? $json : null;
}
