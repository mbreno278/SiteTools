<?php
require __DIR__ . '/_bootstrap.php';

$uid = (int) $currentUser['id'];

$q = $pdo->prepare(
    'SELECT a.*, t.name, t.slug, t.icon,
            CASE
                WHEN a.status = "active" AND a.starts_at <= NOW() AND (a.expires_at IS NULL OR a.expires_at > NOW()) THEN "active"
                WHEN a.status = "active" AND a.expires_at IS NOT NULL AND a.expires_at <= NOW() THEN "expired"
                ELSE a.status
            END AS computed_status
     FROM user_tool_access a
     INNER JOIN tools t ON t.id = a.tool_id
     WHERE a.user_id = :uid
     ORDER BY
         CASE WHEN a.status = "active" AND a.starts_at <= NOW() AND (a.expires_at IS NULL OR a.expires_at > NOW()) THEN 0 ELSE 1 END,
         a.expires_at ASC,
         a.created_at DESC'
);
$q->execute(['uid' => $uid]);
$rows = $q->fetchAll();

$active = array_filter($rows, fn($r) => $r['computed_status'] === 'active');
$others = array_filter($rows, fn($r) => $r['computed_status'] !== 'active');

$pay = $pdo->prepare(
    'SELECT p.*, t.name
     FROM user_payments p
     INNER JOIN tools t ON t.id = p.tool_id
     WHERE p.user_id = :uid
     ORDER BY p.created_at DESC
     LIMIT 30'
);
$pay->execute(['uid' => $uid]);
$payments = $pay->fetchAll();

account_page_start('Meus acessos');
account_header('Meus acessos', 'Acessos individuais adquiridos para ferramentas específicas.');
?>

<section class="ua-panel">
    <div class="ua-panel-head">
        <h2>Acessos ativos</h2>
        <span class="ua-meta-text"><?= count($active) ?> ativo<?= count($active) !== 1 ? 's' : '' ?></span>
    </div>

    <?php if (!$active): ?>
        <div class="ua-empty">
            <span class="material-symbols-outlined">vpn_key_off</span>
            <p>Você não possui acessos ativos no momento.</p>
            <a class="ua-button ua-button--light" href="tools.php?filter=paid">Ver ferramentas pagas</a>
        </div>
    <?php else: ?>
        <div class="ua-list">
            <?php foreach ($active as $r): ?>
                <div class="ua-list-row">
                    <div class="ua-tool-icon">
                        <span class="material-symbols-outlined"><?= user_area_e($r['icon'] ?: 'build') ?></span>
                    </div>
                    <div class="ua-row-content">
                        <strong><?= user_area_e($r['name']) ?></strong>
                        <small>
                            Início: <?= user_area_e(date('d/m/Y', strtotime($r['starts_at']))) ?>
                            ·
                            <?= $r['expires_at']
                                ? 'Válido até ' . user_area_e(date('d/m/Y', strtotime($r['expires_at'])))
                                : 'Sem vencimento' ?>
                        </small>
                    </div>
                    <?= account_status_badge('active') ?>
                    <a class="ua-button" href="../page.php?slug=<?= rawurlencode($r['slug']) ?>">Usar ferramenta</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php if ($others): ?>
<section class="ua-panel">
    <div class="ua-panel-head">
        <h2>Histórico de acessos</h2>
    </div>
    <div class="ua-table-wrap">
        <table class="ua-table">
            <thead>
                <tr>
                    <th>Ferramenta</th>
                    <th>Início</th>
                    <th>Vencimento</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($others as $r): ?>
                    <tr>
                        <td><?= user_area_e($r['name']) ?></td>
                        <td><?= user_area_e(date('d/m/Y', strtotime($r['starts_at']))) ?></td>
                        <td><?= $r['expires_at'] ? user_area_e(date('d/m/Y', strtotime($r['expires_at']))) : 'Sem vencimento' ?></td>
                        <td><?= account_status_badge($r['computed_status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<div class="ua-note">
    O SiteTools libera o acesso após a confirmação do pagamento pelo provedor.
    A integração de checkout e webhook será uma etapa separada.
</div>

<section class="ua-panel">
    <div class="ua-panel-head">
        <h2>Histórico de pagamentos</h2>
    </div>

    <?php if (!$payments): ?>
        <div class="ua-empty">
            <span class="material-symbols-outlined">receipt_long</span>
            <p>Nenhum pagamento registrado até o momento.</p>
        </div>
    <?php else: ?>
        <div class="ua-table-wrap">
            <table class="ua-table">
                <thead>
                    <tr>
                        <th>Ferramenta</th>
                        <th>Valor</th>
                        <th>Data</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><?= user_area_e($p['name']) ?></td>
                            <td><?= user_area_e($p['currency']) ?> <?= number_format((float) $p['amount'], 2, ',', '.') ?></td>
                            <td><?= user_area_e(date('d/m/Y', strtotime($p['created_at']))) ?></td>
                            <td><?= account_status_badge($p['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php account_footer(); ?>
