<?php
$users = [];
$admins = [];
$error = false;
try {
    $pdo = db();
    try {
        $users = $pdo->query(
            'SELECT id, name, email, is_active, email_verified_at, last_login_at, created_at
             FROM users ORDER BY id DESC LIMIT 200'
        )->fetchAll();
    } catch (Throwable) {
        // sem is_active / last_login
        $users = $pdo->query(
            'SELECT id, name, email, email_verified_at, created_at FROM users ORDER BY id DESC LIMIT 200'
        )->fetchAll();
    }
    $admins = $pdo->query(
        'SELECT id, name, email, role, is_active, created_at FROM admin_users ORDER BY id ASC'
    )->fetchAll();
} catch (Throwable $e) {
    $error = true;
}
?>
<div class="page-heading">
    <div>
        <p class="eyebrow">ADMINISTRAÇÃO</p>
        <h1>Usuários</h1>
        <p class="muted">Contas do site e administradores do painel.</p>
    </div>
</div>
<?php if ($error): ?><div class="alert alert-warning">Não foi possível carregar usuários.</div><?php endif; ?>

<div class="panel" style="margin-bottom:20px;">
    <div class="panel-heading"><div><h2>Usuários do site</h2><p class="muted"><?= count($users) ?> conta(s)</p></div></div>
    <div class="table-wrap">
        <table id="usersTable">
            <thead><tr><th>ID</th><th>Nome</th><th>E-mail</th><th>Verificado</th><th>Ativo</th><th>Cadastro</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= (int) $u['id'] ?></td>
                    <td><strong><?= e($u['name']) ?></strong></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= !empty($u['email_verified_at']) ? 'Sim' : 'Não' ?></td>
                    <td>
                        <?php if (array_key_exists('is_active', $u)): ?>
                            <span class="status-badge <?= (int) $u['is_active'] === 1 ? 'status-active' : 'status-inactive' ?>">
                                <?= (int) $u['is_active'] === 1 ? 'Sim' : 'Não' ?>
                            </span>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td><?= e(isset($u['created_at']) ? date('d/m/Y H:i', strtotime($u['created_at'])) : '—') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$users): ?><tr><td colspan="6">Nenhum usuário cadastrado.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="panel">
    <div class="panel-heading"><div><h2>Administradores</h2><p class="muted"><?= count($admins) ?> conta(s)</p></div></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>ID</th><th>Nome</th><th>E-mail</th><th>Papel</th><th>Ativo</th></tr></thead>
            <tbody>
            <?php foreach ($admins as $a): ?>
                <tr>
                    <td><?= (int) $a['id'] ?></td>
                    <td><strong><?= e($a['name']) ?></strong></td>
                    <td><?= e($a['email']) ?></td>
                    <td><code><?= e($a['role']) ?></code></td>
                    <td><span class="status-badge <?= (int) $a['is_active'] === 1 ? 'status-active' : 'status-inactive' ?>"><?= (int) $a['is_active'] === 1 ? 'Sim' : 'Não' ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
