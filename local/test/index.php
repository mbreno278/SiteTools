<?php
/**
 * SiteTools - Front Controller
 * Sistema de URLs amigáveis + redirecionamentos
 */

declare(strict_types=1);

// Configurações básicas
define('BASE_PATH', __DIR__);
define('DATA_PATH', BASE_PATH . '/data');
define('REDIRECTS_FILE', DATA_PATH . '/redirects.json');

// Inicia sessão se necessário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carrega helpers
require_once BASE_PATH . '/config/helpers.php';

// =====================================================
// 1. SISTEMA DE REDIRECIONAMENTOS
// =====================================================
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestUri = rtrim($requestUri, '/') ?: '/';

// Remove query string e normaliza
$path = $requestUri;

// Verifica se existe redirecionamento configurado
$redirects = load_redirects();

if (isset($redirects[$path])) {
    $target = $redirects[$path];
    $code = $target['code'] ?? 301;
    $url  = $target['url'] ?? $target; // suporte a formato simples ou array

    // Evita loop
    if ($url !== $path) {
        header('Location: ' . $url, true, (int)$code);
        exit;
    }
}

// Também verifica com trailing slash
if (isset($redirects[$path . '/'])) {
    $target = $redirects[$path . '/'];
    $code = is_array($target) ? ($target['code'] ?? 301) : 301;
    $url  = is_array($target) ? ($target['url'] ?? '') : $target;
    if ($url) {
        header('Location: ' . $url, true, (int)$code);
        exit;
    }
}

// =====================================================
// 2. ROTEAMENTO DE URLs AMIGÁVEIS
// =====================================================

// Rotas conhecidas
$routes = [
    '/'                 => 'home',
    '/curl'             => 'tools/curl',
    '/ferramentas/curl' => 'tools/curl',
    '/redirects'        => 'admin/redirects',
    '/admin/redirects'  => 'admin/redirects',
];

// Tenta match exato
$route = $routes[$path] ?? null;

// Fallback: /ferramenta-slug -> tools/slug
if (!$route && preg_match('#^/([a-z0-9\-]+)$#', $path, $m)) {
    $slug = $m[1];
    $possible = BASE_PATH . '/tools/' . $slug . '.php';
    if (file_exists($possible)) {
        $route = 'tools/' . $slug;
    }
}

// Se ainda não achou, 404
if (!$route) {
    http_response_code(404);
    echo render_view('404', ['path' => $path]);
    exit;
}

// Carrega a view/tool
$file = BASE_PATH . '/' . $route . '.php';
if (!file_exists($file)) {
    http_response_code(404);
    echo render_view('404', ['path' => $path]);
    exit;
}

// Executa (home, tools/* ou admin/*)
require $file;
