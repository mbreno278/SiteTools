<?php
require __DIR__ . '/_bootstrap.php';
$uid=(int)$currentUser['id'];
$q=$pdo->prepare(
"SELECT t.id,t.name,t.slug,t.description,t.icon,
 CASE WHEN EXISTS(SELECT 1 FROM user_tool_access a WHERE a.user_id=:uid AND a.tool_id=t.id AND a.status='active' AND a.starts_at<=NOW() AND (a.expires_at IS NULL OR a.expires_at>NOW())) THEN 1 ELSE 0 END AS has_access,
 r.access_mode
 FROM tools t LEFT JOIN tool_access_rules r ON r.tool_id=t.id
 WHERE t.is_active=1 ORDER BY t.name"
);
$q->execute(['uid'=>$uid]);$tools=$q->fetchAll();
account_page_start('Minhas ferramentas');account_header('Minhas ferramentas','Veja as ferramentas disponíveis e seus acessos.');
?>
<section class="ua-panel"><div class="ua-list">
<?php foreach($tools as $tool): $paid=$tool['access_mode']==='temporary'; ?>
<div class="ua-list-row"><div class="ua-tool-icon"><span class="material-symbols-outlined"><?=user_area_e($tool['icon']?:'build')?></span></div><div class="ua-row-content"><strong><?=user_area_e($tool['name'])?></strong><small><?=user_area_e($tool['description']??'')?></small></div>
<?php if(!$paid): ?><span class="ua-status">Gratuita</span><a class="ua-button ua-button--light" href="../page.php?slug=<?=rawurlencode($tool['slug'])?>">Abrir</a>
<?php elseif((int)$tool['has_access']===1): ?><span class="ua-status">Acesso ativo</span><a class="ua-button" href="../page.php?slug=<?=rawurlencode($tool['slug'])?>">Usar ferramenta</a>
<?php else: ?><span class="ua-status ua-status--expired">Sem acesso</span><a class="ua-button ua-button--light" href="subscriptions.php">Ver acesso</a><?php endif; ?>
</div><?php endforeach; ?>
</div></section>
<?php account_footer(); ?>
