<?php
require __DIR__ . '/_bootstrap.php';

$uid = (int) $currentUser['id'];

$statusFilter = $_GET['status'] ?? 'all';
$allowedStatuses = ['all', 'completed', 'failed', 'started'];
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = 'all';
}

$sql = 'SELECT u.*, t.name, t.icon
        FROM user_tool_usage u
        LEFT JOIN tools t ON t.id = u.tool_id
        WHERE u.user_id = :uid';
$params = ['uid' => $uid];

if ($statusFilter !== 'all') {
    $sql .= ' AND u.status = :status';
    $params['status'] = $statusFilter;
}

$sql .= ' ORDER BY u.created_at DESC LIMIT 100';

$q = $pdo->prepare($sql);
$q->execute($params);
$rows = $q->fetchAll();

// Contagens para filtros
$counts = ['all' => 0, 'completed' => 0, 'failed' => 0, 'started' => 0];
$cq = $pdo->prepare('SELECT status, COUNT(*) AS total FROM user_tool_usage WHERE user_id = :uid GROUP BY status');
$cq->execute(['uid' => $uid]);
foreach ($cq->fetchAll() as $c) {
    $st = $c['status'];
    $counts[$st] = (int) $c['total'];
    $counts['all'] += (int) $c['total'];
}

account_page_start('Histórico');
account_header('Histórico de atividades', 'Operações registradas enquanto você estava conectado.');
?>

<section class="ua-panel">
    <div class="ua-panel-head">
        <h2>Últimas operações</h2>
        <div class="ua-filters">
            <?php
            $labels = [
                'all'       => 'Todas',
                'completed' => 'Concluídas',
                'failed'    => 'Falhas',
                'started'   => 'Iniciadas',
            ];
            foreach ($labels as $key => $label):
                $active = $statusFilter === $key ? ' is-active' : '';
            ?>
                <a class="ua-filter<?= $active ?>" href="history.php?status=<?= $key ?>">
                    <?= user_area_e($label) ?>
                    <span class="ua-filter-count"><?= $counts[$key] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (!$rows): ?>
        <div class="ua-empty">
            <span class="material-symbols-outlined">history</span>
            <p>Seu histórico ainda está vazio.</p>
            <small>O uso anônimo (sem login) não é associado a uma conta.</small>
            <a class="ua-button ua-button--light" href="../index.php" style="margin-top:14px">Usar uma ferramenta</a>
        </div>
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
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td>
                                <div class="ua-file-name">
                                    <span class="material-symbols-outlined"><?= user_area_e($r['icon'] ?: 'build') ?></span>
                                    <?= user_area_e($r['name'] ?: $r['tool_slug']) ?>
                                </div>
                            </td>
                            <td><?= user_area_e(date('d/m/Y H:i', strtotime($r['created_at']))) ?></td>
                            <td><?= account_status_badge($r['status']) ?></td>
                            <td>
                                <?= $r['processing_time_ms'] !== null
                                    ? (int) $r['processing_time_ms'] . ' ms'
                                    : '—' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="ua-meta-text" style="margin-top:12px">Exibindo até as 100 operações mais recentes.</p>
    <?php endif; ?>
</section>

<?php account_footer(); ?>
