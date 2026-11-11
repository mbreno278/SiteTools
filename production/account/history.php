<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

$uid = (int) $currentUser['id'];
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

$total = 0;
$rows = [];

try {
    $c = $pdo->prepare('SELECT COUNT(*) FROM user_tool_usage WHERE user_id = :uid');
    $c->execute(['uid' => $uid]);
    $total = (int) $c->fetchColumn();

    $stmt = $pdo->prepare(
        'SELECT u.tool_slug, u.status, u.processing_time_ms, u.file_size_bytes, u.created_at,
                t.name AS tool_name, t.icon
         FROM user_tool_usage u
         LEFT JOIN tools t ON t.id = u.tool_id
         WHERE u.user_id = :uid
         ORDER BY u.created_at DESC
         LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset
    );
    $stmt->execute(['uid' => $uid]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log('[account/history] ' . $e->getMessage());
}

$pages = max(1, (int) ceil($total / $perPage));

account_page_start('Histórico');
account_header('Histórico', 'Operações realizadas com a sua conta. Conteúdo dos arquivos não é armazenado aqui.');
?>
<section class="ua-panel">
    <?php if (!$rows): ?>
        <div class="ua-empty"><span class="material-symbols-outlined">history</span>Nenhuma operação no histórico.</div>
    <?php else: ?>
        <div class="ua-table-wrap">
            <table class="ua-table">
                <thead>
                    <tr>
                        <th>Ferramenta</th>
                        <th>Data</th>
                        <th>Status</th>
                        <th>Tempo</th>
                        <th>Tamanho</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= user_area_e((string) ($r['tool_name'] ?: $r['tool_slug'])) ?></td>
                        <td><?= user_area_e(date('d/m/Y H:i', strtotime((string) $r['created_at']))) ?></td>
                        <td><span class="ua-badge ua-badge--<?= user_area_e((string) $r['status']) ?>"><?= user_area_e(user_area_status_label((string) $r['status'])) ?></span></td>
                        <td><?= $r['processing_time_ms'] !== null ? (int) $r['processing_time_ms'] . ' ms' : '—' ?></td>
                        <td><?= $r['file_size_bytes'] !== null ? user_area_e(user_area_format_bytes((int) $r['file_size_bytes'])) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($pages > 1): ?>
            <div class="ua-filters" style="margin-top:14px">
                <?php if ($page > 1): ?><a class="ua-chip" href="?page=<?= $page - 1 ?>">Anterior</a><?php endif; ?>
                <span class="ua-chip is-active">Página <?= $page ?> / <?= $pages ?></span>
                <?php if ($page < $pages): ?><a class="ua-chip" href="?page=<?= $page + 1 ?>">Próxima</a><?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php account_footer(); ?>
