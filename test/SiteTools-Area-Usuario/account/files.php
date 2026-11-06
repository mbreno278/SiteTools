<?php
require __DIR__ . '/_bootstrap.php';
$uid=(int)$currentUser['id'];
$q=$pdo->prepare("SELECT id,original_name,mime_type,size_bytes,created_at,expires_at FROM user_files WHERE user_id=:uid AND status='available' AND (expires_at IS NULL OR expires_at>NOW()) ORDER BY created_at DESC LIMIT 100");
$q->execute(['uid'=>$uid]);$rows=$q->fetchAll();
account_page_start('Meus arquivos');account_header('Meus arquivos','Arquivos que as ferramentas salvaram na sua conta.');
?>
<section class="ua-panel">
<?php if(!$rows): ?><div class="ua-empty"><span class="material-symbols-outlined">folder_open</span>Nenhum arquivo salvo na sua conta. Arquivos temporários de processamento não aparecem aqui automaticamente.</div>
<?php else: ?><div class="ua-table-wrap"><table class="ua-table"><thead><tr><th>Arquivo</th><th>Tamanho</th><th>Adicionado</th><th></th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?=user_area_e($r['original_name'])?></td><td><?=number_format(((int)$r['size_bytes'])/1048576,2,',','.')?> MB</td><td><?=user_area_e(date('d/m/Y',strtotime($r['created_at'])))?></td><td><a class="ua-link" href="download.php?id=<?=(int)$r['id']?>">Baixar</a></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?>
</section>
<?php account_footer(); ?>
