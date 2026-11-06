<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';

/**
 * Logout exclusivamente via POST com CSRF.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    auth_json(['error' => 'Método não permitido.'], 405);
}

csrf_verify();

auth_logout();

$wantsJson = false;
$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
$xrw = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

if (
    str_contains($accept, 'application/json')
    || strcasecmp($xrw, 'XMLHttpRequest') === 0
    || (isset($_POST['_ajax']) && $_POST['_ajax'] === '1')
    || isset($_SERVER['HTTP_X_CSRF_TOKEN'])
) {
    $wantsJson = true;
}

if ($wantsJson) {
    auth_json(['ok' => true]);
}

$base = dirname(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base = str_replace('\\', '/', $base);
if ($base === '/' || $base === '.') {
    $base = '';
}
header('Location: ' . $base . '/index.php');
exit;
