<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

$uid = (int) $currentUser['id'];
$filter = strtolower(trim((string) ($_GET['filter'] ?? 'all')));
if (!in_array($filter, ['all', 'used', 'free', 'login', 'paid', 'favorites'], true)) {
    $filter = 'all';
}

$sql = "SELECT t.id, t.name, t.slug, t.description, t.icon, t.access_type,
               COALESCE(r.access_mode, t.access_type) AS access_mode,
               (SELECT COUNT(*) FROM user_tool_usage u WHERE u.user_id = :uid AND u.tool_id = t.id) AS use_count,
               (SELECT MAX(u2.created_at) FROM user_tool_usage u2 WHERE u2.user_id = :uid2 AND u2.tool_id = t.id) AS last_used,
               (SELECT COUNT(*) FROM user_favorites f WHERE f.user_id = :uid3 AND f.tool_id = t.id) AS is_fav,
               (SELECT COUNT(*) FROM user_tool_access a
                 WHERE a.user_id = :uid4 AND a.tool_id = t.id AND a.status = 'active'
                   AND a.starts_at <= NOW() AND (a.expires_at IS NULL OR a.expires_at > NOW())
                   AND a.revoked_at IS NULL) AS has_access
        FROM tools t
        LEFT JOIN tool_access_rules r ON r.tool_id = t.id
        WHERE t.is_active = 1 AND t.status = 'active'";

$params = [
    'uid' => $uid,
    'uid2' => $uid,
    'uid3' => $uid,
    'uid4' => $uid,
];

if ($filter === 'used') {
    $sql .= ' AND EXISTS (SELECT 1 FROM user_tool_usage u WHERE u.user_id = :uidf AND u.tool_id = t.id)';
    $params['uidf'] = $uid;
} elseif ($filter === 'favorites') {
    $sql .= ' AND EXISTS (SELECT 1 FROM user_favorites f WHERE f.user_id = :uidf AND f.tool_id = t.id)';
    $params['uidf'] = $uid;
} elseif (in_array($filter, ['free', 'login', 'paid'], true)) {
    $sql .= ' AND COALESCE(r.access_mode, t.access_type) = :mode';
    $params['mode'] = $filter;
}

$sql .= ' ORDER BY last_used IS NULL, last_used DESC, t.name ASC LIMIT 200';

$rows = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log('[account/tools] ' . $e->getMessage());
}

account_page_start('Minhas ferramentas');
account_header('Minhas ferramentas', 'Ferramentas do catálogo, uso e favoritos.');
?>
<div class="ua-filters">
    <?php
    $filters = [
        'all' => 'Todas',
        'used' => 'Já usei',
        'favorites' => 'Favoritas',
        'free' => 'Gratuitas',
        'login' => 'Com login',
        'paid' => 'Pagas',
    ];
    foreach ($filters as $key => $label):
        $cls = $filter === $key ? ' ua-chip is-active' : ' ua-chip';
    ?>
        <a class="<?= $cls ?>" href="?filter=<?= user_area_e($key) ?>"><?= user_area_e($label) ?></a>
    <?php endforeach; ?>
</div>

<section class="ua-panel">
    <div class="ua-list">
        <?php if (!$rows): ?>
            <div class="ua-empty"><span class="material-symbols-outlined">handyman</span>Nenhuma ferramenta encontrada para este filtro.</div>
        <?php else: foreach ($rows as $r):
            $mode = (string) ($r['access_mode'] ?? 'free');
            $modeLabel = match ($mode) {
                'login' => 'Login',
                'paid' => 'Paga',
                'temporary' => 'Temporária',
                default => 'Grátis',
            };
            ?>
            <div class="ua-list-row">
                <div class="ua-tool-icon"><span class="material-symbols-outlined"><?= user_area_e((string) ($r['icon'] ?: 'build')) ?></span></div>
                <div class="ua-row-content">
                    <strong><?= user_area_e((string) $r['name']) ?></strong>
                    <small>
                        <?= user_area_e((string) ($r['description'] ?? '')) ?>
                        · <?= user_area_e($modeLabel) ?>
                        <?php if ((int) $r['use_count'] > 0): ?>
                            · <?= (int) $r['use_count'] ?> uso(s)
                        <?php endif; ?>
                        <?php if ((int) $r['has_access'] > 0): ?>
                            · <span class="ua-badge ua-badge--active">Acesso ativo</span>
                        <?php endif; ?>
                    </small>
                </div>
                <button
                    type="button"
                    class="ua-button ua-button--light"
                    data-favorite-toggle="<?= (int) $r['id'] ?>"
                    data-active="<?= (int) $r['is_fav'] > 0 ? '1' : '0' ?>"
                ><?= (int) $r['is_fav'] > 0 ? '★ Favorito' : '☆ Favoritar' ?></button>
                <a class="ua-button" href="../page.php?slug=<?= rawurlencode((string) $r['slug']) ?>">Abrir</a>
            </div>
        <?php endforeach; endif; ?>
    </div>
</section>
<?php account_footer(); ?>
