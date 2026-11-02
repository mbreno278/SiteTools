<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';

/**
 * Aceita POST (preferencial) com CSRF.
 * Resposta JSON para fetch, ou redirect se veio de formulário.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    auth_json(['error' => 'Método não permitido.'], 405);
}

// CSRF: header, campo _csrf ou (fallback) mesma origem
try {
    csrf_verify();
} catch (Throwable) {
    // csrf_verify já encerra a request; este catch é só segurança
}

auth_logout();

$wantsJson = false;
$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
$xrw = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

if (
    str_contains($accept, 'application/json')
    || strcasecmp($xrw, 'XMLHttpRequest') === 0
    || (isset($_POST['_ajax']) && $_POST['_ajax'] === '1')
) {
    $wantsJson = true;
}

// fetch do auth.js envia FormData sem Accept específico — detecta via header CSRF ou _ajax
if (isset($_SERVER['HTTP_X_CSRF_TOKEN']) || isset($_POST['_ajax'])) {
    $wantsJson = true;
}

if ($wantsJson) {
    auth_json(['ok' => true]);
}

// Formulário HTML: redireciona para o início
$base = dirname(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base = str_replace('\\', '/', $base);
if ($base === '/' || $base === '.') {
    $base = '';
}
header('Location: ' . $base . '/index.php');
exit;
