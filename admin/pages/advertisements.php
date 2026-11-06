<?php
$ads = [];
try { $ads = db()->query('SELECT * FROM advertisements ORDER BY id DESC')->fetchAll(); } catch (Throwable) { $ads = null; }
?>
<div class="page-heading"><div><p class="eyebrow">NEGÓCIOS</p><h1>Publicidade</h1><p class="muted">Blocos de anúncio cadastrados.</p></div></div>
<?php if ($ads === null): ?><div class="alert alert-warning">Tabela advertisements indisponível.</div>
<?php else: ?>
<div class="panel"><div class="table-wrap"><table>
<thead><tr><th>ID</th><th>Nome</th><th>Placement</th><th>Ativo</th></tr></thead>
<tbody>
<?php foreach ($ads as $a): ?>
<tr><td><?= (int)$a['id'] ?></td><td><?= e($a['name']) ?></td><td><code><?= e($a['placement']) ?></code></td>
<td><?= (int)$a['is_active'] === 1 ? 'Sim' : 'Não' ?></td></tr>
<?php endforeach; ?>
<?php if (!$ads): ?><tr><td colspan="4">Nenhum anúncio.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php endif; ?>
