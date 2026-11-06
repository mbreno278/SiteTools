<?php
require __DIR__ . '/_bootstrap.php';
$uid=(int)$currentUser['id'];
$q=$pdo->prepare('SELECT u.*,t.name,t.icon FROM user_tool_usage u LEFT JOIN tools t ON t.id=u.tool_id WHERE u.user_id=:uid ORDER BY u.created_at DESC LIMIT 100');
$q->execute(['uid'=>$uid]);$rows=$q->fetchAll();
account_page_start('Histórico');account_header('Histórico de atividades','Operações registradas enquanto você estava conectado.');
?>
<section class="ua-panel"><div class="ua-panel-head"><h2>Últimas 100 operações</h2></div>
<?php if(!$rows): ?><div class="ua-empty"><span class="material-symbols-outlined">history</span>Seu histórico ainda está vazio. O uso anônimo não é associado a uma conta.</div>
<?php else: ?><div class="ua-table-wrap"><table class="ua-table"><thead><tr><th>Ferramenta</th><th>Data</th><th>Status</th><th>Tempo</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?=user_area_e($r['name']?:$r['tool_slug'])?></td><td><?=user_area_e(date('d/m/Y H:i',strtotime($r['created_at'])))?></td><td><?=user_area_e($r['status'])?></td><td><?=$r['processing_time_ms']!==null?(int)$r['processing_time_ms'].' ms':'—'?></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section>
<?php account_footer(); ?>
