<?php
$pageTitle = $pageTitle ?? 'Dashboard';
$admin = current_admin();
?>
<header class="topbar">
    <div class="topbar-left">
        <button class="icon-button mobile-menu-button" id="sidebarToggle" aria-label="Abrir menu">
            <span class="material-symbols-rounded">menu</span>
        </button>
        <div class="breadcrumb"><span>SiteTools</span><span class="breadcrumb-separator">/</span><strong><?= e($pageTitle) ?></strong></div>
    </div>
    <div class="topbar-actions">
        <label class="global-search">
            <span class="material-symbols-rounded">search</span>
            <input type="search" placeholder="Pesquisar..." aria-label="Pesquisar">
            <kbd>⌘ K</kbd>
        </label>
        <button class="icon-button" id="themeToggle" aria-label="Alternar tema">
            <span class="material-symbols-rounded">dark_mode</span>
        </button>
        <div class="admin-profile">
            <div class="avatar"><?= e(mb_strtoupper(mb_substr($admin['name'] ?? 'A', 0, 1))) ?></div>
            <div class="profile-copy"><strong><?= e($admin['name'] ?? 'Administrador') ?></strong><small><?= e($admin['role'] ?? 'admin') ?></small></div>
        </div>
        <a class="icon-button" href="<?= e(admin_url('logout.php')) ?>" title="Sair" aria-label="Sair">
            <span class="material-symbols-rounded">logout</span>
        </a>
    </div>
</header>
