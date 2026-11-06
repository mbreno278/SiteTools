<?php
$uploads = [];
$userFiles = [];
try {
    $pdo = db();
    try {
        $uploads = $pdo->query(
            'SELECT id, tool_slug, original_name, mime_type, size_bytes, status, created_at, expires_at
             FROM file_uploads ORDER BY id DESC LIMIT 100'
        )->fetchAll();
    } catch (Throwable) {
        $uploads = [];
    }
    try {
        $userFiles = $pdo->query(
            'SELECT id, user_id, original_name, mime_type, size_bytes, status, created_at
             FROM user_files ORDER BY id DESC LIMIT 100'
        )->fetchAll();
    } catch (Throwable) {
        $userFiles = [];
    }
} catch (Throwable) {
}
function fmt_bytes($b): string {
    $b = (int) $b;
    if ($b >= 1048576) return number_format($b / 1048576, 2, ',', '.') . ' MB';
    if ($b >= 1024) return number_format($b / 1024, 0, ',', '.') . ' KB';
    return $b . ' B';
}
?>
<div class="page-heading">
    <div>
        <p class="eyebrow">MONITORAMENTO</p>
        <h1>Arquivos</h1>
        <p class="muted">Uploads temporários e arquivos de usuários.</p>
    </div>
</div>

<div class="panel" style="margin-bottom:20px;">
    <div class="panel-heading"><div><h2>Uploads temporários</h2><p class="muted"><?= count($uploads) ?> registro(s)</p></div></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>ID</th><th>Arquivo</th><th>Ferramenta</th><th>Tamanho</th><th>Status</th><th>Criado</th></tr></thead>
            <tbody>
            <?php foreach ($uploads as $f): ?>
                <tr>
                    <td><?= (int) $f['id'] ?></td>
                    <td><?= e($f['original_name']) ?></td>
                    <td><code><?= e($f['tool_slug'] ?? '—') ?></code></td>
                    <td><?= e(fmt_bytes($f['size_bytes'] ?? 0)) ?></td>
                    <td><?= e($f['status']) ?></td>
                    <td><?= e(date('d/m/Y H:i', strtotime((string) $f['created_at']))) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$uploads): ?><tr><td colspan="6">Nenhum upload registrado.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="panel">
    <div class="panel-heading"><div><h2>Arquivos de usuários</h2></div></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>ID</th><th>User</th><th>Arquivo</th><th>Tamanho</th><th>Status</th><th>Criado</th></tr></thead>
            <tbody>
            <?php foreach ($userFiles as $f): ?>
                <tr>
                    <td><?= (int) $f['id'] ?></td>
                    <td><?= (int) $f['user_id'] ?></td>
                    <td><?= e($f['original_name']) ?></td>
                    <td><?= e(fmt_bytes($f['size_bytes'] ?? 0)) ?></td>
                    <td><?= e($f['status']) ?></td>
                    <td><?= e(date('d/m/Y H:i', strtotime((string) $f['created_at']))) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$userFiles): ?><tr><td colspan="6">Nenhum arquivo de usuário.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
