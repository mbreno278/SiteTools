<?php
$currentPage = $_GET['page'] ?? 'dashboard';
$groups = [
    'GERENCIAMENTO' => [
        ['dashboard', 'Visão geral', 'layout-dashboard'],
        ['tools', 'Ferramentas', 'wrench'],
        ['categories', 'Categorias', 'folder-tree'],
        ['users', 'Usuários', 'users'],
    ],
    'MONITORAMENTO' => [
        ['analytics', 'Estatísticas', 'chart-no-axes-combined'],
        ['files', 'Arquivos', 'files'],
        ['logs', 'Logs e atividades', 'scroll-text'],
    ],
    'NEGÓCIOS' => [
        ['advertisements', 'Publicidade', 'megaphone'],
        ['support', 'Suporte', 'messages-square'],
        ['seo', 'SEO e conteúdo', 'search'],
    ],
    'SISTEMA' => [
        ['security', 'Segurança', 'shield-check'],
        ['settings', 'Configurações', 'settings'],
        ['maintenance', 'Manutenção', 'server'],
    ],
];
?>
<aside class="sidebar" id="sidebar">
    <a class="brand" href="<?= e(admin_url('index.php?page=dashboard')) ?>">
        <span class="brand-mark">S</span>
        <span><strong>SiteTools</strong><small>Admin Panel</small></span>
    </a>

    <nav class="side-nav" aria-label="Navegação administrativa">
        <?php foreach ($groups as $groupName => $items): ?>
            <div class="nav-group">
                <div class="nav-label"><?= e($groupName) ?></div>
                <?php foreach ($items as [$slug, $label, $icon]): ?>
                    <a class="nav-link <?= $currentPage === $slug ? 'active' : '' ?>"
                       href="<?= e(admin_url('index.php?page=' . $slug)) ?>">
                        <span class="material-symbols-rounded"><?= e($icon) ?></span>
                        <span><?= e($label) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-bottom">
        <div class="sidebar-status"><span class="status-dot"></span> Sistema administrativo</div>
        <a class="nav-link" href="<?= e(BASE_URL . '/') ?>" target="_blank" rel="noopener">
            <span class="material-symbols-rounded">open_in_new</span><span>Ver site</span>
        </a>
    </div>
</aside>
