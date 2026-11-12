<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

/**
 * Meus acessos — modelo por ferramenta (não assinatura global).
 */
$uid = (int) $currentUser['id'];
$tab = strtolower(trim((string) ($_GET['tab'] ?? 'active')));
if (!in_array($tab, ['active', 'history', 'payments'], true)) {
    $tab = 'active';
}

$accessRows = [];
$paymentRows = [];

try {
    if ($tab === 'active') {
        $stmt = $pdo->prepare(
            "SELECT a.id, a.status, a.access_type, a.starts_at, a.expires_at, a.source,
                    t.name, t.slug, t.icon
             FROM user_tool_access a
             INNER JOIN tools t ON t.id = a.tool_id
             WHERE a.user_id = :uid
               AND a.status = 'active'
               AND a.revoked_at IS NULL
               AND a.starts_at <= NOW()
               AND (a.expires_at IS NULL OR a.expires_at > NOW())
             ORDER BY a.expires_at IS NULL DESC, a.expires_at ASC"
        );
        $stmt->execute(['uid' => $uid]);
        $accessRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } elseif ($tab === 'history') {
        $stmt = $pdo->prepare(
            "SELECT a.id, a.status, a.access_type, a.starts_at, a.expires_at, a.revoked_at, a.source,
                    t.name, t.slug, t.icon
             FROM user_tool_access a
             INNER JOIN tools t ON t.id = a.tool_id
             WHERE a.user_id = :uid
             ORDER BY a.created_at DESC
             LIMIT 100"
        );
        $stmt->execute(['uid' => $uid]);
        $accessRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } else {
        $stmt = $pdo->prepare(
            "SELECT p.id, p.amount, p.currency, p.status, p.paid_at, p.created_at,
                    p.provider, t.name AS tool_name
             FROM user_payments p
             LEFT JOIN tools t ON t.id = p.tool_id
             WHERE p.user_id = :uid
             ORDER BY p.created_at DESC
             LIMIT 50"
        );
        $stmt->execute(['uid' => $uid]);
        $paymentRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
} catch (Throwable $e) {
    error_log('[account/subscriptions] ' . $e->getMessage());
}

account_page_start('Minhas Assinaturas');
account_header('Minhas Assinaturas', 'Acessos individuais por ferramenta — não há assinatura global.');
?>
<div class="ua-filters">
    <a class="ua-chip<?= $tab === 'active' ? ' is-active' : '' ?>" href="?tab=active">Ativos</a>
    <a class="ua-chip<?= $tab === 'history' ? ' is-active' : '' ?>" href="?tab=history">Histórico de acessos</a>
    <a class="ua-chip<?= $tab === 'payments' ? ' is-active' : '' ?>" href="?tab=payments">Pagamentos</a>
</div>

<section class="ua-panel">
    <?php if ($tab === 'payments'): ?>
        <?php if (!$paymentRows): ?>
            <div class="ua-empty"><span class="material-symbols-outlined">payments</span>Nenhum pagamento registrado.</div>
        <?php else: ?>
            <div class="ua-table-wrap">
                <table class="ua-table">
                    <thead>
                        <tr>
                            <th>Ferramenta</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Provedor</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($paymentRows as $p): ?>
                        <tr>
                            <td><?= user_area_e((string) ($p['tool_name'] ?? '—')) ?></td>
                            <td><?= user_area_e(number_format((float) $p['amount'], 2, ',', '.') . ' ' . ($p['currency'] ?? 'BRL')) ?></td>
                            <td><span class="ua-badge"><?= user_area_e(user_area_status_label((string) $p['status'])) ?></span></td>
                            <td><?= user_area_e((string) ($p['provider'] ?? '—')) ?></td>
                            <td><?= user_area_e(date('d/m/Y H:i', strtotime((string) $p['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <?php if (!$accessRows): ?>
            <div class="ua-empty">
                <span class="material-symbols-outlined">key</span>
                <?= $tab === 'active' ? 'Você não possui acessos pagos ativos no momento. Ferramentas gratuitas continuam disponíveis sem compra.' : 'Nenhum histórico de acesso encontrado.' ?>
            </div>
        <?php else: ?>
            <div class="ua-list">
                <?php foreach ($accessRows as $a): ?>
                    <div class="ua-list-row">
                        <div class="ua-tool-icon"><span class="material-symbols-outlined"><?= user_area_e((string) ($a['icon'] ?? 'key')) ?></span></div>
                        <div class="ua-row-content">
                            <strong><?= user_area_e((string) $a['name']) ?></strong>
                            <small>
                                <?= user_area_e(user_area_status_label((string) $a['status'])) ?>
                                · Início <?= user_area_e(date('d/m/Y', strtotime((string) $a['starts_at']))) ?>
                                <?php if (!empty($a['expires_at'])): ?>
                                    · Expira <?= user_area_e(date('d/m/Y H:i', strtotime((string) $a['expires_at']))) ?>
                                <?php else: ?>
                                    · Sem expiração
                                <?php endif; ?>
                                · Origem: <?= user_area_e((string) ($a['source'] ?? '—')) ?>
                            </small>
                        </div>
                        <a class="ua-button" href="<?= e(tool_url((string) ($a['slug'] ?? ''))) ?>">Abrir</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    <p class="ua-note" style="margin-top:16px">
        Cada ferramenta paga possui acesso próprio (início, expiração, renovação e cancelamento individuais).
        A liberação ocorre somente após confirmação do pagamento no servidor.
    </p>
</section>
<?php account_footer(); ?>
