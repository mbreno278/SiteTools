<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

$uid = (int) $currentUser['id'];
$stats = [
    'tools_used' => 0,
    'access' => 0,
    'operations' => 0,
    'favorites' => 0,
    'files' => 0,
];

try {
    $q = $pdo->prepare('SELECT COUNT(DISTINCT tool_id) FROM user_tool_usage WHERE user_id = :uid AND tool_id IS NOT NULL');
    $q->execute(['uid' => $uid]);
    $stats['tools_used'] = (int) $q->fetchColumn();

    $q = $pdo->prepare(
        "SELECT COUNT(*) FROM user_tool_access
         WHERE user_id = :uid AND status = 'active'
           AND starts_at <= NOW()
           AND (expires_at IS NULL OR expires_at > NOW())
           AND revoked_at IS NULL"
    );
    $q->execute(['uid' => $uid]);
    $stats['access'] = (int) $q->fetchColumn();

    $q = $pdo->prepare('SELECT COUNT(*) FROM user_tool_usage WHERE user_id = :uid');
    $q->execute(['uid' => $uid]);
    $stats['operations'] = (int) $q->fetchColumn();

    $q = $pdo->prepare('SELECT COUNT(*) FROM user_favorites WHERE user_id = :uid');
    $q->execute(['uid' => $uid]);
    $stats['favorites'] = (int) $q->fetchColumn();

    $q = $pdo->prepare(
        "SELECT COUNT(*) FROM user_files
         WHERE user_id = :uid AND status = 'available'
           AND (expires_at IS NULL OR expires_at > NOW())"
    );
    $q->execute(['uid' => $uid]);
    $stats['files'] = (int) $q->fetchColumn();
} catch (Throwable $e) {
    error_log('[account/index] ' . $e->getMessage());
}

// Últimas operações
$recent = [];
try {
    $q = $pdo->prepare(
        'SELECT u.tool_slug, u.status, u.processing_time_ms, u.created_at, t.name AS tool_name
         FROM user_tool_usage u
         LEFT JOIN tools t ON t.id = u.tool_id
         WHERE u.user_id = :uid
         ORDER BY u.created_at DESC
         LIMIT 8'
    );
    $q->execute(['uid' => $uid]);
    $recent = $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable) {
}

// Acessos ativos próximos do vencimento
$expiring = [];
try {
    $q = $pdo->prepare(
        "SELECT a.expires_at, t.name, t.slug
         FROM user_tool_access a
         INNER JOIN tools t ON t.id = a.tool_id
         WHERE a.user_id = :uid AND a.status = 'active'
           AND a.revoked_at IS NULL
           AND a.expires_at IS NOT NULL
           AND a.expires_at > NOW()
           AND a.expires_at < DATE_ADD(NOW(), INTERVAL 7 DAY)
         ORDER BY a.expires_at ASC
         LIMIT 5"
    );
    $q->execute(['uid' => $uid]);
    $expiring = $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable) {
}

account_page_start('Painel');
account_header('Painel', 'Resumo da sua conta no SiteTools.');
?>
<section class="ua-grid">
    <article class="ua-card">
        <div class="ua-card-label">Ferramentas usadas</div>
        <div class="ua-stat-number"><?= (int) $stats['tools_used'] ?></div>
        <a class="ua-card-link" href="tools.php">Ver ferramentas</a>
    </article>
    <article class="ua-card">
        <div class="ua-card-label">Acessos ativos</div>
        <div class="ua-stat-number"><?= (int) $stats['access'] ?></div>
        <a class="ua-card-link" href="subscriptions.php">Gerenciar acessos</a>
    </article>
    <article class="ua-card">
        <div class="ua-card-label">Operações</div>
        <div class="ua-stat-number"><?= (int) $stats['operations'] ?></div>
        <a class="ua-card-link" href="history.php">Ver histórico</a>
    </article>
    <article class="ua-card">
        <div class="ua-card-label">Arquivos</div>
        <div class="ua-stat-number"><?= (int) $stats['files'] ?></div>
        <a class="ua-card-link" href="files.php">Meus arquivos</a>
    </article>
    <article class="ua-card">
        <div class="ua-card-label">Favoritos</div>
        <div class="ua-stat-number"><?= (int) $stats['favorites'] ?></div>
        <a class="ua-card-link" href="favorites.php">Abrir favoritos</a>
    </article>
</section>

<section class="ua-actions">
    <a class="ua-button" href="../index.php">Explorar ferramentas</a>
    <a class="ua-button ua-button--light" href="profile.php">Editar perfil</a>
    <a class="ua-button ua-button--light" href="security.php">Segurança</a>
</section>

<section class="ua-panel" style="margin-top:20px">
    <h2 class="ua-panel-title">Atividade recente</h2>
    <?php if (!$recent): ?>
        <div class="ua-empty"><span class="material-symbols-outlined">history</span>Nenhuma operação registrada ainda. Use uma ferramenta estando logado para começar o histórico.</div>
    <?php else: ?>
        <div class="ua-table-wrap">
            <table class="ua-table">
                <thead>
                    <tr>
                        <th>Ferramenta</th>
                        <th>Data</th>
                        <th>Status</th>
                        <th>Tempo</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recent as $r): ?>
                    <tr>
                        <td><?= user_area_e((string) ($r['tool_name'] ?: $r['tool_slug'])) ?></td>
                        <td><?= user_area_e(date('d/m/Y H:i', strtotime((string) $r['created_at']))) ?></td>
                        <td><span class="ua-badge ua-badge--<?= user_area_e((string) $r['status']) ?>"><?= user_area_e(user_area_status_label((string) $r['status'])) ?></span></td>
                        <td><?= $r['processing_time_ms'] !== null ? (int) $r['processing_time_ms'] . ' ms' : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php if ($expiring): ?>
<section class="ua-panel" style="margin-top:16px">
    <h2 class="ua-panel-title">Acessos a vencer (7 dias)</h2>
    <div class="ua-list">
        <?php foreach ($expiring as $e): ?>
            <div class="ua-list-row">
                <div class="ua-row-content">
                    <strong><?= user_area_e((string) $e['name']) ?></strong>
                    <small>Expira em <?= user_area_e(date('d/m/Y H:i', strtotime((string) $e['expires_at']))) ?></small>
                </div>
                <a class="ua-button ua-button--light" href="../page.php?slug=<?= rawurlencode((string) $e['slug']) ?>">Abrir</a>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php account_footer(); ?>
