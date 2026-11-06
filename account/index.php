<?php
require __DIR__ . '/_bootstrap.php';

$uid = (int) $currentUser['id'];

// Estatísticas
$stats = [
    'tools'      => 0,
    'access'     => 0,
    'operations' => 0,
    'favorites'  => 0,
    'files'      => 0,
];

$q = $pdo->prepare('SELECT COUNT(DISTINCT tool_id) FROM user_tool_usage WHERE user_id = :uid');
$q->execute(['uid' => $uid]);
$stats['tools'] = (int) $q->fetchColumn();

$q = $pdo->prepare("SELECT COUNT(*) FROM user_tool_access WHERE user_id = :uid AND status = 'active' AND starts_at <= NOW() AND (expires_at IS NULL OR expires_at > NOW())");
$q->execute(['uid' => $uid]);
$stats['access'] = (int) $q->fetchColumn();

$q = $pdo->prepare('SELECT COUNT(*) FROM user_tool_usage WHERE user_id = :uid');
$q->execute(['uid' => $uid]);
$stats['operations'] = (int) $q->fetchColumn();

$q = $pdo->prepare('SELECT COUNT(*) FROM user_favorites WHERE user_id = :uid');
$q->execute(['uid' => $uid]);
$stats['favorites'] = (int) $q->fetchColumn();

$q = $pdo->prepare("SELECT COUNT(*) FROM user_files WHERE user_id = :uid AND status = 'available' AND (expires_at IS NULL OR expires_at > NOW())");
$q->execute(['uid' => $uid]);
$stats['files'] = (int) $q->fetchColumn();

// Atividades recentes
$q = $pdo->prepare(
    'SELECT u.tool_slug, u.status, u.created_at, u.processing_time_ms, t.name, t.icon
     FROM user_tool_usage u
     LEFT JOIN tools t ON t.id = u.tool_id
     WHERE u.user_id = :uid
     ORDER BY u.created_at DESC
     LIMIT 8'
);
$q->execute(['uid' => $uid]);
$recent = $q->fetchAll();

// Acessos ativos
$q = $pdo->prepare(
    "SELECT a.expires_at, a.status, t.name, t.icon, t.slug
     FROM user_tool_access a
     INNER JOIN tools t ON t.id = a.tool_id
     WHERE a.user_id = :uid
       AND a.status = 'active'
       AND a.starts_at <= NOW()
       AND (a.expires_at IS NULL OR a.expires_at > NOW())
     ORDER BY a.expires_at ASC
     LIMIT 5"
);
$q->execute(['uid' => $uid]);
$accesses = $q->fetchAll();

// Ferramentas mais usadas
$q = $pdo->prepare(
    'SELECT t.name, t.slug, t.icon, COUNT(*) AS total
     FROM user_tool_usage u
     INNER JOIN tools t ON t.id = u.tool_id
     WHERE u.user_id = :uid AND t.is_active = 1
     GROUP BY t.id
     ORDER BY total DESC
     LIMIT 4'
);
$q->execute(['uid' => $uid]);
$mostUsed = $q->fetchAll();

// Favoritos rápidos
$q = $pdo->prepare(
    'SELECT t.name, t.slug, t.icon
     FROM user_favorites f
     INNER JOIN tools t ON t.id = f.tool_id
     WHERE f.user_id = :uid AND t.is_active = 1
     ORDER BY f.created_at DESC
     LIMIT 4'
);
$q->execute(['uid' => $uid]);
$favQuick = $q->fetchAll();

$firstName = explode(' ', $accountName)[0];

account_page_start('Meu painel');
account_header('Olá, ' . $firstName . '!', 'Acompanhe suas ferramentas, acessos e atividades.');
?>

<!-- Stats -->
<section class="ua-grid ua-grid--5">
    <?php
    $cards = [
        ['handyman',  $stats['tools'],      'Ferramentas utilizadas'],
        ['key',       $stats['access'],     'Acessos ativos'],
        ['task_alt',  $stats['operations'], 'Processamentos'],
        ['favorite',  $stats['favorites'],  'Favoritos'],
        ['folder_open', $stats['files'],    'Arquivos salvos'],
    ];
    foreach ($cards as $item):
    ?>
        <div class="ua-card">
            <div class="ua-stat-icon">
                <span class="material-symbols-outlined"><?= $item[0] ?></span>
            </div>
            <div class="ua-stat-number"><?= (int) $item[1] ?></div>
            <div class="ua-stat-label"><?= user_area_e($item[2]) ?></div>
        </div>
    <?php endforeach; ?>
</section>

<!-- Ações rápidas -->
<section class="ua-panel">
    <div class="ua-panel-head">
        <h2>Ações rápidas</h2>
    </div>
    <div class="ua-quick-actions">
        <a class="ua-quick-card" href="tools.php">
            <span class="material-symbols-outlined">handyman</span>
            <strong>Ver ferramentas</strong>
            <small>Todas as disponíveis</small>
        </a>
        <a class="ua-quick-card" href="favorites.php">
            <span class="material-symbols-outlined">favorite</span>
            <strong>Meus favoritos</strong>
            <small><?= $stats['favorites'] ?> salvos</small>
        </a>
        <a class="ua-quick-card" href="files.php">
            <span class="material-symbols-outlined">folder_open</span>
            <strong>Meus arquivos</strong>
            <small><?= $stats['files'] ?> disponíveis</small>
        </a>
        <a class="ua-quick-card" href="subscriptions.php">
            <span class="material-symbols-outlined">key</span>
            <strong>Meus acessos</strong>
            <small><?= $stats['access'] ?> ativos</small>
        </a>
    </div>
</section>

<div class="ua-two-col">
    <!-- Atividades recentes -->
    <section class="ua-panel">
        <div class="ua-panel-head">
            <h2>Atividades recentes</h2>
            <a class="ua-link" href="history.php">Ver histórico</a>
        </div>
        <div class="ua-list">
            <?php if (!$recent): ?>
                <div class="ua-empty">
                    <span class="material-symbols-outlined">history</span>
                    <p>Suas operações realizadas enquanto estiver conectado aparecerão aqui.</p>
                    <a class="ua-button ua-button--light" href="../index.php">Explorar ferramentas</a>
                </div>
            <?php else: ?>
                <?php foreach ($recent as $row): ?>
                    <div class="ua-list-row">
                        <div class="ua-tool-icon">
                            <span class="material-symbols-outlined"><?= user_area_e($row['icon'] ?: 'build') ?></span>
                        </div>
                        <div class="ua-row-content">
                            <strong><?= user_area_e($row['name'] ?: $row['tool_slug']) ?></strong>
                            <small>
                                <?= user_area_e(date('d/m/Y H:i', strtotime($row['created_at']))) ?>
                                <?php if ($row['processing_time_ms'] !== null): ?>
                                    · <?= (int) $row['processing_time_ms'] ?> ms
                                <?php endif; ?>
                            </small>
                        </div>
                        <?= account_status_badge($row['status']) ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Acessos ativos -->
    <section class="ua-panel">
        <div class="ua-panel-head">
            <h2>Meus acessos ativos</h2>
            <a class="ua-link" href="subscriptions.php">Ver todos</a>
        </div>
        <div class="ua-list">
            <?php if (!$accesses): ?>
                <div class="ua-empty">
                    <span class="material-symbols-outlined">vpn_key_off</span>
                    <p>Você ainda não possui acessos temporários ativos.</p>
                    <a class="ua-button ua-button--light" href="tools.php">Ver ferramentas pagas</a>
                </div>
            <?php else: ?>
                <?php foreach ($accesses as $row): ?>
                    <div class="ua-list-row">
                        <div class="ua-tool-icon">
                            <span class="material-symbols-outlined"><?= user_area_e($row['icon'] ?: 'build') ?></span>
                        </div>
                        <div class="ua-row-content">
                            <strong><?= user_area_e($row['name']) ?></strong>
                            <small>
                                <?= $row['expires_at']
                                    ? 'Válido até ' . user_area_e(date('d/m/Y', strtotime($row['expires_at'])))
                                    : 'Acesso sem vencimento' ?>
                            </small>
                        </div>
                        <a class="ua-button ua-button--light" href="../page.php?slug=<?= rawurlencode($row['slug']) ?>">Usar</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php if ($mostUsed || $favQuick): ?>
<div class="ua-two-col">
    <?php if ($mostUsed): ?>
    <section class="ua-panel">
        <div class="ua-panel-head">
            <h2>Mais usadas por você</h2>
        </div>
        <div class="ua-list">
            <?php foreach ($mostUsed as $row): ?>
                <div class="ua-list-row">
                    <div class="ua-tool-icon">
                        <span class="material-symbols-outlined"><?= user_area_e($row['icon'] ?: 'build') ?></span>
                    </div>
                    <div class="ua-row-content">
                        <strong><?= user_area_e($row['name']) ?></strong>
                        <small><?= (int) $row['total'] ?> uso<?= $row['total'] > 1 ? 's' : '' ?></small>
                    </div>
                    <a class="ua-button ua-button--light" href="../page.php?slug=<?= rawurlencode($row['slug']) ?>">Abrir</a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($favQuick): ?>
    <section class="ua-panel">
        <div class="ua-panel-head">
            <h2>Favoritos rápidos</h2>
            <a class="ua-link" href="favorites.php">Ver todos</a>
        </div>
        <div class="ua-list">
            <?php foreach ($favQuick as $row): ?>
                <div class="ua-list-row">
                    <div class="ua-tool-icon">
                        <span class="material-symbols-outlined"><?= user_area_e($row['icon'] ?: 'build') ?></span>
                    </div>
                    <div class="ua-row-content">
                        <strong><?= user_area_e($row['name']) ?></strong>
                    </div>
                    <a class="ua-button ua-button--light" href="../page.php?slug=<?= rawurlencode($row['slug']) ?>">Abrir</a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php account_footer(); ?>
