<?php

require_once __DIR__ . '/_guard.php';
$stats = [
    'tools' => 0, 'users' => 0, 'usage' => 0, 'support_new' => 0,
];
$byCategory = [];
$recentUsage = [];
try {
    $pdo = db();
    $stats['tools'] = (int) $pdo->query('SELECT COUNT(*) FROM tools WHERE is_active = 1')->fetchColumn();
    try { $stats['users'] = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(); } catch (Throwable) {}
    try { $stats['usage'] = (int) $pdo->query('SELECT COUNT(*) FROM tool_usage')->fetchColumn(); } catch (Throwable) {}
    try { $stats['support_new'] = (int) $pdo->query("SELECT COUNT(*) FROM support_messages WHERE status = 'new'")->fetchColumn(); } catch (Throwable) {}
    try {
        $byCategory = $pdo->query(
            'SELECT category, COUNT(*) AS total FROM tools GROUP BY category ORDER BY total DESC'
        )->fetchAll();
    } catch (Throwable) {}
    try {
        $recentUsage = $pdo->query(
            'SELECT tool_slug, status, created_at FROM tool_usage ORDER BY id DESC LIMIT 20'
        )->fetchAll();
    } catch (Throwable) {}
} catch (Throwable) {}
?>
<div class="page-heading">
    <div>
        <p class="eyebrow">MONITORAMENTO</p>
        <h1>Estatísticas</h1>
        <p class="muted">Indicadores resumidos do SiteTools.</p>
    </div>
</div>

<section class="stats-grid">
    <article class="stat-card"><div class="stat-top"><span>Ferramentas ativas</span><span class="stat-icon purple">build</span></div><strong><?= number_format($stats['tools'], 0, ',', '.') ?></strong></article>
    <article class="stat-card"><div class="stat-top"><span>Usuários</span><span class="stat-icon green">group</span></div><strong><?= number_format($stats['users'], 0, ',', '.') ?></strong></article>
    <article class="stat-card"><div class="stat-top"><span>Usos registrados</span><span class="stat-icon orange">analytics</span></div><strong><?= number_format($stats['usage'], 0, ',', '.') ?></strong></article>
    <article class="stat-card"><div class="stat-top"><span>Suporte (novos)</span><span class="stat-icon blue">mail</span></div><strong><?= number_format($stats['support_new'], 0, ',', '.') ?></strong></article>
</section>

<div class="dashboard-grid" style="margin-top:20px;">
    <article class="panel">
        <div class="panel-heading"><div><h2>Por categoria</h2></div></div>
        <ul class="rank-list">
            <?php foreach ($byCategory as $i => $row): ?>
                <li>
                    <span class="rank-pos"><?= $i + 1 ?></span>
                    <span class="rank-name"><?= e($row['category'] ?: '—') ?></span>
                    <span class="rank-count"><?= (int) $row['total'] ?></span>
                </li>
            <?php endforeach; ?>
            <?php if (!$byCategory): ?><li><span class="rank-name">Sem dados</span></li><?php endif; ?>
        </ul>
    </article>
    <article class="panel">
        <div class="panel-heading"><div><h2>Uso recente</h2></div></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Ferramenta</th><th>Status</th><th>Quando</th></tr></thead>
                <tbody>
                <?php foreach ($recentUsage as $u): ?>
                    <tr>
                        <td><code><?= e($u['tool_slug'] ?? '—') ?></code></td>
                        <td><?= e($u['status']) ?></td>
                        <td><?= e(date('d/m H:i', strtotime((string) $u['created_at']))) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$recentUsage): ?><tr><td colspan="3">Sem uso registrado.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</div>
