<?php

require_once __DIR__ . '/_guard.php';
$stats = [
    'tools' => 0,
    'categories' => 0,
    'admins' => 0,
    'operations' => 0,
    'users' => 0,
];
$recent = [];
$topTools = [];
$dbError = false;

try {
    $pdo = db();
    $stats['tools'] = (int) $pdo->query('SELECT COUNT(*) FROM tools')->fetchColumn();
    try {
        $stats['categories'] = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    } catch (Throwable) {
        $stats['categories'] = 0;
    }
    $stats['admins'] = (int) $pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
    try {
        $stats['operations'] = (int) $pdo->query('SELECT COUNT(*) FROM tool_usage')->fetchColumn();
    } catch (Throwable) {
        $stats['operations'] = 0;
    }
    try {
        $stats['users'] = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    } catch (Throwable) {
        $stats['users'] = 0;
    }
    try {
        $recent = $pdo->query(
            'SELECT action, description, created_at FROM admin_activity_logs ORDER BY id DESC LIMIT 8'
        )->fetchAll();
    } catch (Throwable) {
        $recent = [];
    }
    try {
        $topTools = $pdo->query(
            'SELECT t.name, COUNT(u.id) AS total
             FROM tool_usage u
             INNER JOIN tools t ON t.id = u.tool_id
             GROUP BY t.id, t.name
             ORDER BY total DESC
             LIMIT 5'
        )->fetchAll();
    } catch (Throwable) {
        $topTools = [];
    }
} catch (Throwable $exception) {
    $dbError = true;
    error_log('[SiteTools dashboard] ' . $exception->getMessage());
}

$admin = current_admin();
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Bom dia' : ($hour < 18 ? 'Boa tarde' : 'Boa noite');
?>

<div class="page-heading">
    <div>
        <p class="eyebrow">PAINEL DE CONTROLE</p>
        <h1><?= e($greeting) ?>, <?= e(explode(' ', $admin['name'] ?? 'Admin')[0]) ?></h1>
        <p class="muted">Acompanhe os principais indicadores da plataforma.</p>
    </div>
    <span class="date-chip">
        <span class="material-symbols-rounded">calendar_today</span>
        <?= date('d/m/Y') ?>
    </span>
</div>

<?php if ($dbError): ?>
    <div class="alert alert-warning">
        Não foi possível carregar todos os indicadores. Confira a conexão e o schema do banco.
    </div>
<?php endif; ?>

<section class="stats-grid">
    <article class="stat-card">
        <div class="stat-top">
            <span>Ferramentas</span>
            <span class="stat-icon purple">build</span>
        </div>
        <strong><?= number_format($stats['tools'], 0, ',', '.') ?></strong>
        <small>Cadastradas no sistema</small>
    </article>
    <article class="stat-card">
        <div class="stat-top">
            <span>Categorias</span>
            <span class="stat-icon blue">folder</span>
        </div>
        <strong><?= number_format($stats['categories'], 0, ',', '.') ?></strong>
        <small>Organização do catálogo</small>
    </article>
    <article class="stat-card">
        <div class="stat-top">
            <span>Usuários</span>
            <span class="stat-icon green">group</span>
        </div>
        <strong><?= number_format($stats['users'], 0, ',', '.') ?></strong>
        <small>Contas do site</small>
    </article>
    <article class="stat-card">
        <div class="stat-top">
            <span>Operações</span>
            <span class="stat-icon orange">analytics</span>
        </div>
        <strong><?= number_format($stats['operations'], 0, ',', '.') ?></strong>
        <small>Uso das ferramentas</small>
    </article>
</section>

<section class="dashboard-grid">
    <article class="panel panel-large">
        <div class="panel-heading">
            <div>
                <h2>Atividade administrativa</h2>
                <p class="muted">Últimos eventos registrados no painel</p>
            </div>
            <a class="panel-link" href="<?= e(admin_url('index.php?page=logs')) ?>">Ver logs</a>
        </div>
        <?php if (!$recent): ?>
            <div class="empty-state">
                <span class="material-symbols-rounded">inbox</span>
                <p>Nenhuma atividade registrada ainda.</p>
            </div>
        <?php else: ?>
            <ul class="activity-list">
                <?php foreach ($recent as $row): ?>
                    <li>
                        <div class="activity-icon">
                            <span class="material-symbols-rounded">history</span>
                        </div>
                        <div class="activity-body">
                            <strong><?= e((string) ($row['action'] ?? '')) ?></strong>
                            <p><?= e((string) ($row['description'] ?? '')) ?></p>
                        </div>
                        <time datetime="<?= e((string) ($row['created_at'] ?? '')) ?>">
                            <?= e(isset($row['created_at']) ? date('d/m H:i', strtotime((string) $row['created_at'])) : '') ?>
                        </time>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>

    <article class="panel">
        <div class="panel-heading">
            <div>
                <h2>Ferramentas mais usadas</h2>
                <p class="muted">Top 5 por uso</p>
            </div>
        </div>
        <?php if (!$topTools): ?>
            <div class="empty-state compact">
                <p>Sem dados de uso ainda.</p>
            </div>
        <?php else: ?>
            <ol class="rank-list">
                <?php foreach ($topTools as $i => $tool): ?>
                    <li>
                        <span class="rank-pos"><?= $i + 1 ?></span>
                        <span class="rank-name"><?= e((string) $tool['name']) ?></span>
                        <span class="rank-count"><?= number_format((int) $tool['total'], 0, ',', '.') ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>

        <div class="quick-actions">
            <h3>Atalhos</h3>
            <div class="quick-actions__grid">
                <a href="<?= e(admin_url('index.php?page=tools')) ?>">
                    <span class="material-symbols-rounded">build</span> Ferramentas
                </a>
                <a href="<?= e(admin_url('index.php?page=users')) ?>">
                    <span class="material-symbols-rounded">group</span> Usuários
                </a>
                <a href="<?= e(admin_url('index.php?page=settings')) ?>">
                    <span class="material-symbols-rounded">settings</span> Configurações
                </a>
                <a href="<?= e(BASE_URL . '/') ?>" target="_blank" rel="noopener">
                    <span class="material-symbols-rounded">open_in_new</span> Ver site
                </a>
            </div>
        </div>
    </article>
</section>
