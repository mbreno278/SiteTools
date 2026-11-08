<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/permissions.php';
require_admin();

$allowedPages = [
    'dashboard' => 'dashboard.php',
    'tools' => 'tools.php',
    'categories' => 'categories.php',
    'users' => 'users.php',
    'analytics' => 'analytics.php',
    'files' => 'files.php',
    'logs' => 'logs.php',
    'advertisements' => 'advertisements.php',
    'support' => 'support.php',
    'seo' => 'seo.php',
    'security' => 'security.php',
    'settings' => 'settings.php',
    'maintenance' => 'maintenance.php',
];

$page = (string) ($_GET['page'] ?? 'dashboard');
if (!isset($allowedPages[$page])) {
    http_response_code(404);
    $page = 'dashboard';
}

$pagePermissions = admin_page_permission_map();
require_permission($pagePermissions[$page] ?? 'dashboard.view');

?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(ucfirst($page)) ?> · SiteTools Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,400,0,0" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(admin_url('assets/css/admin.css')) ?>">
    <link rel="stylesheet" href="<?= e(admin_url('assets/css/components.css')) ?>">
    <link rel="stylesheet" href="<?= e(admin_url('assets/css/responsive.css')) ?>">
    <link rel="stylesheet" href="<?= e(admin_url('assets/css/dashboard-extra.css')) ?>">
</head>
<body>
<div class="admin-shell">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main-area">
        <?php
        $pageTitle = [
            'dashboard'=>'Visão geral','tools'=>'Ferramentas','categories'=>'Categorias','users'=>'Usuários',
            'analytics'=>'Estatísticas','files'=>'Arquivos','logs'=>'Logs e atividades',
            'advertisements'=>'Publicidade','support'=>'Suporte','seo'=>'SEO e conteúdo',
            'security'=>'Segurança','settings'=>'Configurações','maintenance'=>'Manutenção'
        ][$page];
        require __DIR__ . '/includes/header.php';
        ?>
        <main class="content">
            <?php require __DIR__ . '/pages/' . $allowedPages[$page]; ?>
        </main>
        <?php require __DIR__ . '/includes/footer.php'; ?>
    </div>
</div>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<script>window.SITETOOLS_ADMIN = { baseUrl: <?= json_encode(admin_url()) ?>, csrf: <?= json_encode(csrf_token()) ?> };</script>
<script src="<?= e(admin_url('assets/js/admin.js')) ?>" defer></script>
<script src="<?= e(admin_url('assets/js/charts.js')) ?>" defer></script>
<script src="<?= e(admin_url('assets/js/notifications.js')) ?>" defer></script>
</body>
</html>
