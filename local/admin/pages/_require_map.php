<?php
/**
 * Mapa sugerido: incluir no topo de cada página admin após bootstrap.
 *
 * Exemplo em pages/tools.php:
 *   require_once __DIR__ . '/../includes/permissions.php';
 *   require_permission('tools.view');
 *
 * Páginas → permissão mínima:
 */
return [
    'dashboard.php'      => 'dashboard.view',
    'tools.php'          => 'tools.view',
    'categories.php'     => 'categories.view',
    'users.php'          => 'users.view',
    'analytics.php'      => 'analytics.view',
    'files.php'          => 'files.view',
    'logs.php'           => 'logs.view',
    'support.php'        => 'support.view',
    'settings.php'       => 'settings.view',
    'seo.php'            => 'seo.view',
    'advertisements.php' => 'advertisements.view',
    'security.php'       => 'security.view',
    'maintenance.php'    => 'security.view',
];
