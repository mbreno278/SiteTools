<?php
require __DIR__ . '/_bootstrap.php';
$uid=(int)$currentUser['id'];
$q=$pdo->prepare(
'SELECT a.*,t.name,t.slug,t.icon,
 CASE WHEN a.status="active" AND a.starts_at<=NOW() AND (a.expires_at IS NULL OR a.expires_at>NOW()) THEN "active"
      WHEN a.status="active" AND a.expires_at IS NOT NULL AND a.expires_at<=NOW() THEN "expired"
      ELSE a.status END AS computed_status
 FROM user_tool_access a INNER JOIN tools t ON t.id=a.tool_id
 WHERE a.user_id=:uid ORDER BY a.created_at DESC'
);
$q->execute(['uid'=>$uid]);$rows=$q->fetchAll();
account_page_start('Meus acessos');account_header('Meus acessos','Acessos individuais adquiridos para ferramentas específicas.');
?>
<section class="ua-panel"><div class="ua-panel-head"><h2>Histórico de acessos</h2></div>
<?php if(!$rows): ?><div class="ua-empty"><span class="material-symbols-outlined">key_off</span>Nenhum acesso individual foi registrado na sua conta.</div>
<?php else: ?><div class="ua-table-wrap"><table class="ua-table"><thead><tr><th>Ferramenta</th><th>Início</th><th>Vencimento</th><th>Status</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?=user_area_e($r['name'])?></td><td><?=user_area_e(date('d/m/Y',strtotime($r['starts_at'])))?></td><td><?=$r['expires_at']?user_area_e(date('d/m/Y',strtotime($r['expires_at']))):'Sem vencimento'?></td><td><span class="ua-status <?= $r['computed_status']==='active'?'':'ua-status--expired' ?>"><?=user_area_e(ucfirst($r['computed_status']))?></span></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?>
</section>
<div class="ua-note">O SiteTools libera o acesso após a confirmação do pagamento pelo provedor. A integração de checkout e webhook será uma etapa separada.</div>

<?php
$pay=$pdo->prepare('SELECT p.*,t.name FROM user_payments p INNER JOIN tools t ON t.id=p.tool_id WHERE p.user_id=:uid ORDER BY p.created_at DESC LIMIT 30');
$pay->execute(['uid'=>$uid]);$payments=$pay->fetchAll();
?>
<section class="ua-panel"><div class="ua-panel-head"><h2>Histórico de pagamentos</h2></div>
<?php if(!$payments): ?><div class="ua-empty">Nenhum pagamento registrado até o momento.</div>
<?php else: ?><div class="ua-table-wrap"><table class="ua-table"><thead><tr><th>Ferramenta</th><th>Valor</th><th>Data</th><th>Status</th></tr></thead><tbody>
<?php foreach($payments as $p): ?><tr><td><?=user_area_e($p['name'])?></td><td><?=user_area_e($p['currency'])?> <?=number_format((float)$p['amount'],2,',','.')?></td><td><?=user_area_e(date('d/m/Y',strtotime($p['created_at'])))?></td><td><span class="ua-status <?=$p['status']==='paid'?'':'ua-status--pending'?>"><?=user_area_e($p['status'])?></span></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section>

<?php account_footer(); ?>
