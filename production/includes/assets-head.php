<?php
/**
 * Includes padrão de CSS/JS base (evita duplicar csrf.js e ordem errada).
 *
 * Uso em page.php / index.php (dentro de <head>):
 *   $assetBase = './'; // ou '../' conforme o arquivo
 *   require __DIR__ . '/includes/assets-head.php';
 *
 * Variáveis opcionais:
 *   $assetBase (string) prefixo dos assets — default './'
 *   $extraCss (list<string>) folhas extras
 *   $extraJs  (list<string>) scripts extras
 *   $includeAuth (bool) inclui auth.css + auth.js
 */

declare(strict_types=1);

$assetBase = $assetBase ?? './';
$extraCss = $extraCss ?? [];
$extraJs = $extraJs ?? [];
$includeAuth = $includeAuth ?? false;

$cssCore = [
    'assets/css/variables.css',
    'assets/css/style.css',
    'assets/css/layout.css',
    'assets/css/components.css',
    'assets/css/modo.css',
    'assets/css/ux.css',
];

foreach ($cssCore as $href) {
    echo '<link rel="stylesheet" href="' . htmlspecialchars($assetBase . $href, ENT_QUOTES, 'UTF-8') . '">' . "\n";
}

foreach ($extraCss as $href) {
    echo '<link rel="stylesheet" href="' . htmlspecialchars($assetBase . $href, ENT_QUOTES, 'UTF-8') . '">' . "\n";
}

if ($includeAuth) {
    echo '<link rel="stylesheet" href="' . htmlspecialchars($assetBase . 'assets/css/auth.css', ENT_QUOTES, 'UTF-8') . '">' . "\n";
    echo '<link rel="stylesheet" href="' . htmlspecialchars($assetBase . 'assets/css/auth-extra.css', ENT_QUOTES, 'UTF-8') . '">' . "\n";
}

// JS base — ordem fixa, sem duplicar csrf
$jsCore = [
    'assets/js/config.js',
    'assets/js/csrf.js',
    'assets/js/modules/toast.js',
    'assets/js/modules/loading.js',
    'assets/js/modules/http.js',
    'assets/js/modules/a11y.js',
    'assets/js/core.js',
];

foreach ($jsCore as $src) {
    echo '<script src="' . htmlspecialchars($assetBase . $src, ENT_QUOTES, 'UTF-8') . '" defer></script>' . "\n";
}

if ($includeAuth) {
    echo '<script src="' . htmlspecialchars($assetBase . 'assets/js/auth.js', ENT_QUOTES, 'UTF-8') . '" defer></script>' . "\n";
}

foreach ($extraJs as $src) {
    echo '<script src="' . htmlspecialchars($assetBase . $src, ENT_QUOTES, 'UTF-8') . '" defer></script>' . "\n";
}
