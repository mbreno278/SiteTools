<?php
require __DIR__ . '/_bootstrap.php';

$uid = (int) $currentUser['id'];
$stats = ['tools' => 0, 'access' => 0, 'operations' => 0, 'favorites' => 0];

$q = $pdo->prepare('SELECT COUNT(DISTINCT tool_id) FROM user_tool_usage WHERE user_id = :uid');
$q->execute(['uid' => $uid]); $stats['tools'] = (int) $q->fetchColumn();

$q = $pdo->prepare("SELECT COUNT(*) FROM user_tool_access WHERE user_id = :uid AND status='active' AND starts_at <= NOW() AND (expires_at IS NULL OR expires_at > NOW())");
$q->execute(['uid' => $uid]); $stats['access'] = (int) $q->fetchColumn();

$q = $pdo->prepare('SELECT COUNT(*) FROM user_tool_usage WHERE user_id = :uid');
$q->execute(['uid' => $uid]); $stats['operations'] = (int) $q->fetchColumn();

$q = $pdo->prepare('SELECT COUNT(*) FROM user_favorites WHERE user_id = :uid');
$q->execute(['uid' => $uid]); $stats['favorites'] = (int) $q->fetchColumn();

$q = $pdo->prepare(
    'SELECT u.tool_slug, u.status, u.created_at, t.name, t.icon
     FROM user_tool_usage u LEFT JOIN tools t ON t.id = u.tool_id
     WHERE u.user_id = :uid ORDER BY u.created_at DESC LIMIT 6'
);
$q->execute(['uid' => $uid]); $recent = $q->fetchAll();

$q = $pdo->prepare(
    "SELECT a.expires_at, a.status, t.name, t.icon
     FROM user_tool_access a INNER JOIN tools t ON t.id = a.tool_id
     WHERE a.user_id = :uid AND a.status='active'
       AND a.starts_at <= NOW() AND (a.expires_at IS NULL OR a.expires_at > NOW())
     ORDER BY a.expires_at ASC LIMIT 5"
);
$q->execute(['uid' => $uid]); $accesses = $q->fetchAll();

account_page_start('Meu painel');
account_header('Visão geral', 'Acompanhe suas ferramentas, acessos e atividades.');
?>
<section class="ua-grid">
<?php foreach ([['handyman',$stats['tools'],'Ferramentas utilizadas'],['key',$stats['access'],'Acessos ativos'],['task_alt',$stats['operations'],'Processamentos registrados'],['favorite',$stats['favorites'],'Favoritos']] as $item): ?>
<div class="ua-card"><div class="ua-stat-icon"><span class="material-symbols-outlined"><?= $item[0] ?></span></div><div class="ua-stat-number"><?= (int)$item[1] ?></div><div class="ua-stat-label"><?= user_area_e($item[2]) ?></div></div>
<?php endforeach; ?>
</section>
<section class="ua-panel">
<div class="ua-panel-head"><h2>Atividades recentes</h2><a class="ua-link" href="history.php">Ver histórico</a></div>
<div class="ua-list">
<?php if (!$recent): ?><div class="ua-empty"><span class="material-symbols-outlined">history</span>Suas operações realizadas enquanto estiver conectado aparecerão aqui.</div>
<?php else: foreach ($recent as $row): ?>
<div class="ua-list-row"><div class="ua-tool-icon"><span class="material-symbols-outlined"><?= user_area_e($row['icon'] ?: 'build') ?></span></div><div class="ua-row-content"><strong><?= user_area_e($row['name'] ?: $row['tool_slug']) ?></strong><small><?= user_area_e(date('d/m/Y H:i', strtotime($row['created_at']))) ?></small></div><span class="ua-status <?= $row['status']==='failed'?'ua-status--failed':'' ?>"><?= $row['status']==='completed'?'Concluído':($row['status']==='failed'?'Falhou':'Iniciado') ?></span></div>
<?php endforeach; endif; ?>
</div></section>
<section class="ua-panel">
<div class="ua-panel-head"><h2>Meus acessos ativos</h2><a class="ua-link" href="subscriptions.php">Ver todos</a></div>
<div class="ua-list">
<?php if (!$accesses): ?><div class="ua-empty"><span class="material-symbols-outlined">vpn_key_off</span>Você ainda não possui acessos temporários ativos.</div>
<?php else: foreach ($accesses as $row): ?>
<div class="ua-list-row"><div class="ua-tool-icon"><span class="material-symbols-outlined"><?= user_area_e($row['icon'] ?: 'build') ?></span></div><div class="ua-row-content"><strong><?= user_area_e($row['name']) ?></strong><small><?= $row['expires_at'] ? 'Válido até '.user_area_e(date('d/m/Y',strtotime($row['expires_at']))) : 'Acesso sem vencimento' ?></small></div><span class="ua-status">Ativo</span></div>
<?php endforeach; endif; ?>
</div></section>
<?php account_footer(); ?>
