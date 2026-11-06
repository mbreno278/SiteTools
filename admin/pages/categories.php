<?php
$cats = [];
$error = false;
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cat_action'])) {
    verify_csrf();
    try {
        $pdo = db();
        $action = $_POST['cat_action'];
        if ($action === 'create') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $slug = trim((string) ($_POST['slug'] ?? ''));
            if ($slug === '') {
                $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name) ?? '');
                $slug = trim($slug, '-');
            }
            if ($name !== '' && $slug !== '') {
                $pdo->prepare(
                    'INSERT INTO categories (name, slug, description, is_active, sort_order)
                     VALUES (:n, :s, :d, 1, :o)'
                )->execute([
                    'n' => $name,
                    's' => $slug,
                    'd' => trim((string) ($_POST['description'] ?? '')),
                    'o' => (int) ($_POST['sort_order'] ?? 0),
                ]);
                record_admin_activity('category_create', 'Criou categoria ' . $name);
                $flash = 'Categoria criada.';
            }
        } elseif ($action === 'toggle') {
            $id = (int) ($_POST['id'] ?? 0);
            $pdo->prepare('UPDATE categories SET is_active = IF(is_active=1,0,1) WHERE id = :id')->execute(['id' => $id]);
            $flash = 'Status atualizado.';
        }
    } catch (Throwable $e) {
        $flash = 'Erro: ' . $e->getMessage();
    }
}

try {
    $cats = db()->query('SELECT * FROM categories ORDER BY sort_order ASC, name ASC')->fetchAll();
} catch (Throwable) {
    $error = true;
}
?>
<div class="page-heading">
    <div>
        <p class="eyebrow">GERENCIAMENTO</p>
        <h1>Categorias</h1>
        <p class="muted">Organize as ferramentas por categoria.</p>
    </div>
</div>
<?php if ($flash): ?><div class="alert alert-success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-warning">Tabela categories indisponível.</div><?php endif; ?>

<div class="panel" style="margin-bottom:20px;">
    <div class="panel-heading"><div><h2>Nova categoria</h2></div></div>
    <form method="post" class="admin-form">
        <?= csrf_field() ?>
        <input type="hidden" name="cat_action" value="create">
        <div class="form-row">
            <div>
                <label class="field-label">Nome</label>
                <input class="form-control" name="name" required>
            </div>
            <div>
                <label class="field-label">Slug</label>
                <input class="form-control" name="slug" placeholder="opcional">
            </div>
        </div>
        <label class="field-label">Descrição</label>
        <input class="form-control" name="description">
        <button class="button button-primary" type="submit" style="margin-top:12px;">Adicionar</button>
    </form>
</div>

<div class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>ID</th><th>Nome</th><th>Slug</th><th>Ordem</th><th>Ativa</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($cats as $c): ?>
                <tr>
                    <td><?= (int) $c['id'] ?></td>
                    <td><strong><?= e($c['name']) ?></strong></td>
                    <td><code><?= e($c['slug']) ?></code></td>
                    <td><?= (int) ($c['sort_order'] ?? 0) ?></td>
                    <td><span class="status-badge <?= (int) $c['is_active'] === 1 ? 'status-active' : 'status-inactive' ?>"><?= (int) $c['is_active'] === 1 ? 'Sim' : 'Não' ?></span></td>
                    <td>
                        <form method="post" style="display:inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="cat_action" value="toggle">
                            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                            <button class="button" type="submit">Ativar/Desativar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$cats && !$error): ?><tr><td colspan="6">Nenhuma categoria.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
