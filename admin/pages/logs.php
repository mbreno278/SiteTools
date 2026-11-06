<?php
$adminLogs = [];
$userLogs = [];
$error = false;
try {
    $pdo = db();
    $adminLogs = $pdo->query(
        'SELECT a.action, a.description, a.ip_address, a.created_at, u.name AS admin_name
         FROM admin_activity_logs a
         LEFT JOIN admin_users u ON u.id = a.admin_user_id
         ORDER BY a.id DESC LIMIT 100'
    )->fetchAll();
    try {
        $userLogs = $pdo->query(
            'SELECT action, description, ip_address, created_at, user_id
             FROM user_activity_logs ORDER BY id DESC LIMIT 100'
        )->fetchAll();
    } catch (Throwable) {
        $userLogs = [];
    }
} catch (Throwable) {
    $error = true;
}
?>
<div class="page-heading">
    <div>
        <p class="eyebrow">MONITORAMENTO</p>
        <h1>Logs e atividades</h1>
        <p class="muted">Eventos administrativos e de usuários.</p>
    </div>
</div>
<?php if ($error): ?><div class="alert alert-warning">Não foi possível carregar os logs.</div><?php endif; ?>

<div class="panel" style="margin-bottom:20px;">
    <div class="panel-heading"><div><h2>Admin</h2></div></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Quando</th><th>Admin</th><th>Ação</th><th>Descrição</th><th>IP</th></tr></thead>
            <tbody>
            <?php foreach ($adminLogs as $l): ?>
                <tr>
                    <td><?= e(date('d/m/Y H:i', strtotime((string) $l['created_at']))) ?></td>
                    <td><?= e($l['admin_name'] ?? '—') ?></td>
                    <td><code><?= e($l['action']) ?></code></td>
                    <td><?= e($l['description'] ?? '') ?></td>
                    <td><?= e($l['ip_address'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$adminLogs): ?><tr><td colspan="5">Sem registros.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="panel">
    <div class="panel-heading"><div><h2>Usuários</h2><p class="muted">Requer tabela user_activity_logs</p></div></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Quando</th><th>User ID</th><th>Ação</th><th>Descrição</th><th>IP</th></tr></thead>
            <tbody>
            <?php foreach ($userLogs as $l): ?>
                <tr>
                    <td><?= e(date('d/m/Y H:i', strtotime((string) $l['created_at']))) ?></td>
                    <td><?= (int) ($l['user_id'] ?? 0) ?></td>
                    <td><code><?= e($l['action']) ?></code></td>
                    <td><?= e($l['description'] ?? '') ?></td>
                    <td><?= e($l['ip_address'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$userLogs): ?><tr><td colspan="5">Sem registros (ou tabela ainda não criada).</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
