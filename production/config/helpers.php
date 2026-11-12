<?php
/**
 * Helpers gerais do SiteTools
 */

declare(strict_types=1);

/**
 * Carrega a lista de redirecionamentos do arquivo JSON
 */
function load_redirects(): array
{
    if (!file_exists(REDIRECTS_FILE)) {
        // Cria arquivo vazio se não existir
        if (!is_dir(dirname(REDIRECTS_FILE))) {
            mkdir(dirname(REDIRECTS_FILE), 0755, true);
        }
        file_put_contents(REDIRECTS_FILE, json_encode(new stdClass(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return [];
    }

    $content = file_get_contents(REDIRECTS_FILE);
    $data = json_decode($content, true);

    return is_array($data) ? $data : [];
}

/**
 * Salva a lista de redirecionamentos
 */
function save_redirects(array $redirects): bool
{
    $json = json_encode($redirects, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return file_put_contents(REDIRECTS_FILE, $json) !== false;
}

/**
 * Adiciona ou atualiza um redirecionamento
 */
function add_redirect(string $from, string $to, int $code = 301): bool
{
    $from = '/' . ltrim(trim($from), '/');
    $from = rtrim($from, '/') ?: '/';

    $redirects = load_redirects();
    $redirects[$from] = [
        'url'  => $to,
        'code' => $code,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    return save_redirects($redirects);
}

/**
 * Remove um redirecionamento
 */
function remove_redirect(string $from): bool
{
    $from = '/' . ltrim(trim($from), '/');
    $from = rtrim($from, '/') ?: '/';

    $redirects = load_redirects();
    if (isset($redirects[$from])) {
        unset($redirects[$from]);
        return save_redirects($redirects);
    }
    return false;
}

/**
 * Renderiza uma view simples (layout básico)
 */
function render_view(string $view, array $data = []): string
{
    extract($data);
    ob_start();

    $viewFile = BASE_PATH . '/views/' . $view . '.php';
    if (file_exists($viewFile)) {
        include $viewFile;
    } else {
        // Fallback simples
        echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>SiteTools</title>';
        echo '<style>body{font-family:system-ui;max-width:900px;margin:40px auto;padding:0 20px;}</style></head><body>';
        if ($view === '404') {
            echo '<h1>404 - Página não encontrada</h1>';
            echo '<p>O caminho <code>' . htmlspecialchars($path ?? '') . '</code> não existe.</p>';
            echo '<p><a href="/">Voltar ao início</a></p>';
        }
        echo '</body></html>';
    }

    return ob_get_clean();
}

/**
 * Escapa HTML
 */
function e(?string $str): string
{
    return htmlspecialchars((string)$str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Gera token CSRF simples
 */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_check(?string $token): bool
{
    return isset($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], (string)$token);
}
