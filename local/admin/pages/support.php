<?php

require_once __DIR__ . '/_guard.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    require_once dirname(__DIR__) . '/includes/permissions.php';
    require_permission('support.manage');
}

$messages = [];
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $status = (string) ($_POST['status'] ?? 'read');
    if ($id > 0 && in_array($status, ['new', 'read', 'closed'], true)) {
        try {
            db()->prepare('UPDATE support_messages SET status = :s WHERE id = :id')->execute(['s' => $status, 'id' => $id]);
            record_admin_activity('support_update', 'Atualizou ticket #' . $id);
            $flash = 'Status atualizado.';
        } catch (Throwable $e) {
            $flash = 'Erro ao atualizar.';
        }
    }
}
try {
    $messages = db()->query('SELECT * FROM support_messages ORDER BY id DESC LIMIT 100')->fetchAll();
} catch (Throwable) {
    $messages = null;
}
?>
<div class="page-heading">
    <div>
        <p class="eyebrow">NEGÓCIOS</p>
        <h1>Suporte</h1>
        <p class="muted">Mensagens recebidas pelo site.</p>
    </div>
</div>
<?php if ($flash): ?><div class="alert alert-success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($messages === null): ?>
    <div class="alert alert-warning">Tabela support_messages indisponível.</div>
<?php else: ?>
<div class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>ID</th><th>Quando</th><th>De</th><th>Assunto</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($messages as $m): ?>
                <tr>
                    <td><?= (int) $m['id'] ?></td>
                    <td><?= e(date('d/m/Y H:i', strtotime((string) $m['created_at']))) ?></td>
                    <td>
                        <strong><?= e($m['name'] ?? '—') ?></strong>
                        <small class="table-subtext"><?= e($m['email'] ?? '') ?></small>
                    </td>
                    <td>
                        <?= e($m['subject'] ?? '—') ?>
                        <small class="table-subtext"><?= e(mb_substr((string) ($m['message'] ?? ''), 0, 80)) ?></small>
                    </td>
                    <td><span class="status-badge"><?= e($m['status']) ?></span></td>
                    <td>
                        <form method="post" style="display:flex;gap:6px;flex-wrap:wrap;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                            <select class="form-control" name="status" style="width:auto;">
                                <?php foreach (['new','read','closed'] as $st): ?>
                                    <option value="<?= $st ?>" <?= $m['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="button" type="submit">OK</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$messages): ?><tr><td colspan="6">Nenhuma mensagem.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
