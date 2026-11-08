<?php

require_once __DIR__ . '/_guard.php';
$attempts = [];
$sessions = [];
try {
    $pdo = db();
    try {
        $attempts = $pdo->query(
            'SELECT email, ip_address, attempted_at FROM login_attempts ORDER BY id DESC LIMIT 50'
        )->fetchAll();
    } catch (Throwable) {}
    try {
        $sessions = $pdo->query(
            'SELECT user_id, session_id, ip_address, last_activity, expires_at FROM user_sessions ORDER BY id DESC LIMIT 50'
        )->fetchAll();
    } catch (Throwable) {}
} catch (Throwable) {}
?>
<div class="page-heading">
    <div>
        <p class="eyebrow">SISTEMA</p>
        <h1>Segurança</h1>
        <p class="muted">Tentativas de login e sessões ativas.</p>
    </div>
</div>

<div class="panel" style="margin-bottom:20px;">
    <div class="panel-heading"><div><h2>Tentativas de login</h2></div></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>E-mail</th><th>IP</th><th>Quando</th></tr></thead>
            <tbody>
            <?php foreach ($attempts as $a): ?>
                <tr>
                    <td><?= e($a['email']) ?></td>
                    <td><?= e($a['ip_address']) ?></td>
                    <td><?= e(date('d/m/Y H:i', strtotime((string) $a['attempted_at']))) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$attempts): ?><tr><td colspan="3">Nenhuma tentativa registrada.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="panel">
    <div class="panel-heading"><div><h2>Sessões de usuários</h2></div></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>User</th><th>Session</th><th>IP</th><th>Atividade</th><th>Expira</th></tr></thead>
            <tbody>
            <?php foreach ($sessions as $s): ?>
                <tr>
                    <td><?= (int) $s['user_id'] ?></td>
                    <td><code><?= e(mb_substr((string) $s['session_id'], 0, 16)) ?>…</code></td>
                    <td><?= e($s['ip_address'] ?? '') ?></td>
                    <td><?= e(date('d/m H:i', strtotime((string) $s['last_activity']))) ?></td>
                    <td><?= e(date('d/m H:i', strtotime((string) $s['expires_at']))) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$sessions): ?><tr><td colspan="5">Nenhuma sessão.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
