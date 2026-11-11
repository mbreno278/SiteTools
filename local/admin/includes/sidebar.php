<?php
$currentPage = $_GET['page'] ?? 'dashboard';

/**
 * Ícones = nomes oficiais do Google Material Symbols (underscore).
 * Nomes estilo Lucide (layout-dashboard, folder-tree) aparecem como texto.
 */
$groups = [
    'GERENCIAMENTO' => [
        ['dashboard', 'Visão geral', 'dashboard'],
        ['tools', 'Ferramentas', 'build'],
        ['categories', 'Categorias', 'folder'],
        ['users', 'Usuários', 'group'],
    ],
    'MONITORAMENTO' => [
        ['analytics', 'Estatísticas', 'bar_chart'],
        ['files', 'Arquivos', 'folder_open'],
        ['logs', 'Logs e atividades', 'history'],
    ],
    'NEGÓCIOS' => [
        ['advertisements', 'Publicidade', 'campaign'],
        ['support', 'Suporte', 'support_agent'],
        ['seo', 'SEO e conteúdo', 'travel_explore'],
    ],
    'SISTEMA' => [
        ['security', 'Segurança', 'shield'],
        ['settings', 'Configurações', 'settings'],
        ['maintenance', 'Manutenção', 'dns'],
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
                        <span class="material-symbols-rounded" aria-hidden="true"><?= e($icon) ?></span>
                        <span><?= e($label) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-bottom">
        <div class="sidebar-status"><span class="status-dot"></span> Sistema administrativo</div>
        <a class="nav-link" href="<?= e(rtrim((string) BASE_URL, '/') . '/') ?>" target="_blank" rel="noopener">
            <span class="material-symbols-rounded" aria-hidden="true">open_in_new</span>
            <span>Ver site</span>
        </a>
    </div>
</aside>
