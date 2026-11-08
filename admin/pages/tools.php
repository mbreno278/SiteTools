<?php
declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

$tools = [];
$loadError = false;
$editId = (int) ($_GET['edit'] ?? 0);
$editTool = null;
$flash = '';
$flashType = 'success';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['form'] ?? '') === 'tool_update') {
    try {
        verify_csrf();
        $pdo = db();
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            throw new RuntimeException('ID inválido.');
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $category = trim((string) ($_POST['category'] ?? ''));
        $accessType = (string) ($_POST['access_type'] ?? 'free');
        $status = (string) ($_POST['status'] ?? 'active');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);

        if ($name === '') {
            throw new RuntimeException('Nome obrigatório.');
        }
        if (!in_array($accessType, ['free', 'login', 'paid'], true)) {
            $accessType = 'free';
        }
        if (!in_array($status, ['active', 'development', 'unavailable'], true)) {
            $status = 'active';
        }

        $cols = $pdo->query('SHOW COLUMNS FROM tools')->fetchAll(PDO::FETCH_COLUMN);

        $fields = [];
        $values = [];
        $fields[] = 'name = ?';
        $values[] = $name;
        $fields[] = 'description = ?';
        $values[] = $description;
        $fields[] = 'category = ?';
        $values[] = $category;
        $fields[] = 'is_active = ?';
        $values[] = $isActive;

        if (in_array('access_type', $cols, true)) {
            $fields[] = 'access_type = ?';
            $values[] = $accessType;
        }
        if (in_array('status', $cols, true)) {
            $fields[] = 'status = ?';
            $values[] = $status;
        }
        if (in_array('is_featured', $cols, true)) {
            $fields[] = 'is_featured = ?';
            $values[] = $isFeatured;
        }
        if (in_array('sort_order', $cols, true)) {
            $fields[] = 'sort_order = ?';
            $values[] = $sortOrder;
        }

        $values[] = $id;
        $sql = 'UPDATE `tools` SET ' . implode(', ', $fields) . ' WHERE `id` = ? LIMIT 1';
        $pdo->prepare($sql)->execute($values);

        try {
            $mode = ($accessType === 'free') ? 'free' : 'temporary';
            $pdo->prepare(
                'INSERT INTO tool_access_rules (tool_id, access_mode, guest_allowed)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE access_mode = VALUES(access_mode), guest_allowed = VALUES(guest_allowed)'
            )->execute([$id, $mode, $accessType === 'free' ? 1 : 0]);
        } catch (Throwable $e) {
            error_log('[tools rules] ' . $e->getMessage());
        }

        if (function_exists('record_admin_activity')) {
            record_admin_activity('tool_update', 'Atualizou ferramenta #' . $id . ' (' . $name . ')');
        }

        $flash = 'Ferramenta atualizada com sucesso.';
        $flashType = 'success';
        $editId = 0;
        $editTool = null;
    } catch (Throwable $e) {
        $flash = 'Erro ao salvar: ' . $e->getMessage();
        $flashType = 'danger';
        $editId = (int) ($_POST['id'] ?? 0);
    }
}

try {
    $pdo = db();
    $cols = $pdo->query('SHOW COLUMNS FROM tools')->fetchAll(PDO::FETCH_COLUMN);
    $select = 'id, name, slug, description, category, icon, handler, is_active';
    foreach (['access_type', 'status', 'is_featured', 'sort_order'] as $c) {
        if (in_array($c, $cols, true)) {
            $select .= ', ' . $c;
        }
    }
    $tools = $pdo->query("SELECT {$select} FROM tools ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

    if ($editId > 0) {
        foreach ($tools as $t) {
            if ((int) $t['id'] === $editId) {
                $editTool = $t;
                break;
            }
        }
    }
} catch (Throwable $e) {
    $loadError = true;
    error_log('[SiteTools tools] ' . $e->getMessage());
}

if (!function_exists('access_label')) {
    function access_label(string $v): string
    {
        return match ($v) {
            'login' => 'Login',
            'paid' => 'Pago',
            default => 'Grátis',
        };
    }
}
if (!function_exists('status_label')) {
    function status_label(string $v): string
    {
        return match ($v) {
            'development' => 'Em desenvolvimento',
            'unavailable' => 'Indisponível',
            default => 'Ativa',
        };
    }
}
?>
<div class="page-heading">
    <div>
        <p class="eyebrow">GERENCIAMENTO</p>
        <h1>Ferramentas</h1>
        <p class="muted">Gerencie status, acesso e visibilidade das ferramentas.</p>
    </div>
</div>

<?php if ($flash !== ''): ?>
    <div class="alert alert-<?= $flashType === 'danger' ? 'danger' : 'success' ?>">
        <?= e($flash) ?>
    </div>
<?php endif; ?>

<?php if (!empty($loadError)): ?>
    <div class="alert alert-warning">Não foi possível consultar a tabela <code>tools</code>.</div>
<?php endif; ?>

<?php if ($editTool): ?>
<div class="panel" style="margin-bottom:20px;">
    <div class="panel-heading">
        <div>
            <h2>Editar: <?= e($editTool['name']) ?></h2>
            <p class="muted"><code><?= e($editTool['slug']) ?></code></p>
        </div>
        <a class="panel-link" href="<?= e(admin_url('index.php?page=tools')) ?>">Voltar à lista</a>
    </div>

    <form method="post" action="<?= e(admin_url('index.php?page=tools&edit=' . (int) $editTool['id'])) ?>" class="admin-form">
        <?= csrf_field() ?>
        <input type="hidden" name="form" value="tool_update">
        <input type="hidden" name="id" value="<?= (int) $editTool['id'] ?>">

        <div class="form-row">
            <div>
                <label class="field-label" for="name">Nome</label>
                <input class="form-control" id="name" name="name" required value="<?= e($editTool['name']) ?>">
            </div>
            <div>
                <label class="field-label" for="category">Categoria</label>
                <input class="form-control" id="category" name="category" value="<?= e($editTool['category'] ?? '') ?>">
            </div>
        </div>

        <label class="field-label" for="description">Descrição</label>
        <textarea class="form-control" id="description" name="description" rows="3"><?= e($editTool['description'] ?? '') ?></textarea>

        <div class="form-row" style="margin-top:14px;">
            <div>
                <label class="field-label" for="access_type">Tipo de acesso</label>
                <select class="form-control" id="access_type" name="access_type">
                    <?php foreach (['free' => 'Gratuito', 'login' => 'Requer login', 'paid' => 'Pago'] as $val => $lab): ?>
                        <option value="<?= $val ?>" <?= (($editTool['access_type'] ?? 'free') === $val) ? 'selected' : '' ?>><?= $lab ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="field-label" for="status">Status</label>
                <select class="form-control" id="status" name="status">
                    <?php foreach (['active' => 'Ativa', 'development' => 'Em desenvolvimento', 'unavailable' => 'Indisponível'] as $val => $lab): ?>
                        <option value="<?= $val ?>" <?= (($editTool['status'] ?? 'active') === $val) ? 'selected' : '' ?>><?= $lab ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row" style="margin-top:14px;">
            <div>
                <label class="field-label" for="sort_order">Ordem</label>
                <input class="form-control" type="number" id="sort_order" name="sort_order" value="<?= (int) ($editTool['sort_order'] ?? 0) ?>">
            </div>
            <div style="display:flex;flex-direction:column;gap:10px;justify-content:flex-end;">
                <label><input type="checkbox" name="is_active" value="1" <?= (int) ($editTool['is_active'] ?? 0) === 1 ? 'checked' : '' ?>> Ativa no site</label>
                <label><input type="checkbox" name="is_featured" value="1" <?= (int) ($editTool['is_featured'] ?? 0) === 1 ? 'checked' : '' ?>> Destaque</label>
            </div>
        </div>

        <button type="submit" class="button button-primary" style="margin-top:16px;">Salvar alterações</button>
    </form>
</div>
<?php endif; ?>

<div class="panel">
    <div class="table-toolbar">
        <div>
            <strong><?= count($tools) ?> ferramenta(s)</strong>
            <p class="muted">Clique em Editar para alterar acesso e status.</p>
        </div>
        <input class="form-control table-search" data-table-search="toolsTable" placeholder="Buscar...">
    </div>
    <div class="table-wrap">
        <table id="toolsTable">
            <thead>
                <tr>
                    <th>Ferramenta</th>
                    <th>Slug</th>
                    <th>Categoria</th>
                    <th>Acesso</th>
                    <th>Status</th>
                    <th>Ativa</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tools as $tool): ?>
                <tr>
                    <td><strong><?= e($tool['name']) ?></strong></td>
                    <td><code><?= e($tool['slug']) ?></code></td>
                    <td><?= e($tool['category'] ?? '—') ?></td>
                    <td><?= e(access_label((string) ($tool['access_type'] ?? 'free'))) ?></td>
                    <td><?= e(status_label((string) ($tool['status'] ?? 'active'))) ?></td>
                    <td><?= (int) ($tool['is_active'] ?? 0) === 1 ? 'Sim' : 'Não' ?></td>
                    <td>
                        <a class="button" href="<?= e(admin_url('index.php?page=tools&edit=' . (int) $tool['id'])) ?>">Editar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
