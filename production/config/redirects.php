<?php
declare(strict_types=1);

define('REDIRECTS_FILE', __DIR__ . '/../storage/redirects.json'); // ou data/redirects.json

function load_redirects(): array
{
    if (!file_exists(REDIRECTS_FILE)) {
        @mkdir(dirname(REDIRECTS_FILE), 0755, true);
        file_put_contents(REDIRECTS_FILE, '{}');
        return [];
    }
    $data = json_decode((string)file_get_contents(REDIRECTS_FILE), true);
    return is_array($data) ? $data : [];
}

function save_redirects(array $redirects): bool
{
    return file_put_contents(
        REDIRECTS_FILE,
        json_encode($redirects, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    ) !== false;
}

function add_redirect(string $from, string $to, int $code = 301): bool
{
    $from = '/' . trim($from, '/');
    if ($from === '//') $from = '/';

    $redirects = load_redirects();
    $redirects[$from] = [
        'url'        => $to,
        'code'       => $code,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ];
    return save_redirects($redirects);
}

function remove_redirect(string $from): bool
{
    $from = '/' . trim($from, '/');
    $redirects = load_redirects();
    if (isset($redirects[$from])) {
        unset($redirects[$from]);
        return save_redirects($redirects);
    }
    return false;
}

/**
 * Executa o redirecionamento se existir na lista.
 * Chame no INÍCIO do index.php e do page.php
 */
function handle_redirects(): void
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $path = rtrim($path, '/') ?: '/';

    $redirects = load_redirects();

    // tenta com e sem barra final
    $candidates = [$path, $path . '/'];

    foreach ($candidates as $candidate) {
        if (isset($redirects[$candidate])) {
            $target = $redirects[$candidate];
            $url  = is_array($target) ? ($target['url'] ?? '') : $target;
            $code = is_array($target) ? (int)($target['code'] ?? 301) : 301;

            if ($url && $url !== $candidate) {
                header('Location: ' . $url, true, $code);
                exit;
            }
        }
    }
}