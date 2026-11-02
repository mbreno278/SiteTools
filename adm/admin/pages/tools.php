<?php
$tools = [];
$loadError = false;
try {
    $tools = db()->query('SELECT id, name, slug, description, category, icon, handler, is_active FROM tools ORDER BY id DESC')->fetchAll();
} catch (Throwable $exception) {
    $loadError = true;
    error_log('[SiteTools tools] ' . $exception->getMessage());
}
?>
<div class="page-heading"><div><p class="eyebrow">GERENCIAMENTO</p><h1>Ferramentas</h1><p class="muted">Consulte as ferramentas cadastradas no SiteTools.</p></div></div>
<?php if ($loadError): ?><div class="alert alert-warning">Não foi possível consultar a tabela <code>tools</code>. Confira a conexão e os nomes das colunas do banco atual.</div><?php endif; ?>
<div class="panel">
    <div class="table-toolbar"><div><strong><?= count($tools) ?> ferramenta(s)</strong><p class="muted">Esta primeira versão é somente leitura para preservar seus handlers existentes.</p></div><input class="form-control table-search" data-table-search="toolsTable" placeholder="Buscar ferramenta..."></div>
    <div class="table-wrap"><table id="toolsTable"><thead><tr><th>Ferramenta</th><th>Slug</th><th>Categoria</th><th>Handler</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($tools as $tool): ?><tr><td><strong><?= e($tool['name']) ?></strong><small class="table-subtext"><?= e($tool['description'] ?? '') ?></small></td><td><code><?= e($tool['slug']) ?></code></td><td><?= e($tool['category'] ?? '—') ?></td><td><?= e($tool['handler'] ?? '—') ?></td><td><span class="status-badge <?= (int)$tool['is_active'] === 1 ? 'status-active' : 'status-inactive' ?>"><?= (int)$tool['is_active'] === 1 ? 'Ativa' : 'Inativa' ?></span></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <?php if (!$tools && !$loadError): ?><div class="empty-state"><span class="material-symbols-rounded">handyman</span><strong>Nenhuma ferramenta encontrada</strong><p>Cadastre uma ferramenta pelo fluxo atual do SiteTools.</p></div><?php endif; ?>
</div>
