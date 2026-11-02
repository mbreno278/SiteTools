<?php
$stats = ['tools' => 0, 'categories' => 0, 'admins' => 0, 'operations' => 0];
$recent = [];
$dbError = false;

try {
    $pdo = db();
    $stats['tools'] = (int)$pdo->query('SELECT COUNT(*) FROM tools')->fetchColumn();
    $stats['categories'] = (int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    $stats['admins'] = (int)$pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
    $stats['operations'] = (int)$pdo->query('SELECT COUNT(*) FROM tool_usage')->fetchColumn();
    $recent = $pdo->query('SELECT action, description, created_at FROM admin_activity_logs ORDER BY id DESC LIMIT 6')->fetchAll();
} catch (Throwable $exception) {
    $dbError = true;
    error_log('[SiteTools dashboard] ' . $exception->getMessage());
}
?>
<div class="page-heading"><div><p class="eyebrow">PAINEL DE CONTROLE</p><h1>Visão geral</h1><p class="muted">Acompanhe os principais indicadores da plataforma.</p></div><span class="date-chip"><?= date('d/m/Y') ?></span></div>
<?php if ($dbError): ?><div class="alert alert-warning">Não foi possível carregar todos os indicadores. Confira a conexão e execute <code>database/schema.sql</code>.</div><?php endif; ?>
<section class="stats-grid">
    <article class="stat-card"><div class="stat-top"><span>Ferramentas cadastradas</span><span class="stat-icon purple">build</span></div><strong><?= number_format($stats['tools'], 0, ',', '.') ?></strong><small>Registros na tabela tools</small></article>
    <article class="stat-card"><div class="stat-top"><span>Categorias</span><span class="stat-icon blue">folder</span></div><strong><?= number_format($stats['categories'], 0, ',', '.') ?></strong><small>Categorias cadastradas</small></article>
    <article class="stat-card"><div class="stat-top"><span>Administradores</span><span class="stat-icon green">group</span></div><strong><?= number_format($stats['admins'], 0, ',', '.') ?></strong><small>Contas administrativas</small></article>
    <article class="stat-card"><div class="stat-top"><span>Operações registradas</span><span class="stat-icon orange">analytics</span></div><strong><?= number_format($stats['operations'], 0, ',', '.') ?></strong><small>Eventos na tabela tool_usage</small></article>
</section>
<section class="dashboard-grid">
    <article class="panel panel-large"><div class="panel-heading"><div><h2>Atividade administrativa</h2><p class="muted">Últimos eventos registrados no painel</p></div><a href="<?= e(admin_url('index.php?page=logs')) ?>">Ver logs</a></div>
    <?php if (!$recent): ?><div class="empty-state"><span class="material-symbols-rounded">history</span><strong>Nenhuma atividade registrada</strong><p>As ações administrativas aparecerão aqui.</p></div>
    <?php else: ?><div class="activity-list"><?php foreach ($recent as $item): ?><div class="activity-item"><span class="activity-dot"></span><div><strong><?= e($item['action']) ?></strong><p><?= e($item['description']) ?></p><small><?= e($item['created_at']) ?></small></div></div><?php endforeach; ?></div><?php endif; ?>
    </article>
    <article class="panel"><div class="panel-heading"><div><h2>Acessos rápidos</h2><p class="muted">Atalhos administrativos</p></div></div>
        <div class="quick-links">
            <a href="<?= e(admin_url('index.php?page=tools')) ?>"><span class="material-symbols-rounded">add_circle</span><span><strong>Ferramentas</strong><small>Gerenciar ferramentas</small></span></a>
            <a href="<?= e(admin_url('index.php?page=categories')) ?>"><span class="material-symbols-rounded">create_new_folder</span><span><strong>Categorias</strong><small>Organizar ferramentas</small></span></a>
            <a href="<?= e(admin_url('index.php?page=logs')) ?>"><span class="material-symbols-rounded">receipt_long</span><span><strong>Logs</strong><small>Investigar ocorrências</small></span></a>
            <a href="<?= e(admin_url('index.php?page=settings')) ?>"><span class="material-symbols-rounded">settings</span><span><strong>Configurações</strong><small>Ajustar o sistema</small></span></a>
        </div>
    </article>
</section>
