<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

$uid = (int) $currentUser['id'];
$rows = [];

try {
    $q = $pdo->prepare(
        'SELECT t.id, t.name, t.slug, t.description, t.icon
         FROM user_favorites f
         INNER JOIN tools t ON t.id = f.tool_id
         WHERE f.user_id = :uid AND t.is_active = 1
         ORDER BY f.created_at DESC'
    );
    $q->execute(['uid' => $uid]);
    $rows = $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log('[account/favorites] ' . $e->getMessage());
}

account_page_start('Favoritos');
account_header('Favoritos', 'Acesse rapidamente as ferramentas que você salvou.');
?>
<section class="ua-panel">
    <div class="ua-list">
        <?php if (!$rows): ?>
            <div class="ua-empty"><span class="material-symbols-outlined">favorite_border</span>Você ainda não adicionou ferramentas aos favoritos.</div>
        <?php else: foreach ($rows as $r): ?>
            <div class="ua-list-row">
                <div class="ua-tool-icon"><span class="material-symbols-outlined"><?= user_area_e((string) ($r['icon'] ?: 'build')) ?></span></div>
                <div class="ua-row-content">
                    <strong><?= user_area_e((string) $r['name']) ?></strong>
                    <small><?= user_area_e((string) ($r['description'] ?? '')) ?></small>
                </div>
                <a class="ua-button ua-button--light" href="../page.php?slug=<?= rawurlencode((string) $r['slug']) ?>">Abrir</a>
                <button class="ua-button ua-button--danger" type="button" data-favorite-toggle="<?= (int) $r['id'] ?>" data-active="1">Remover</button>
            </div>
        <?php endforeach; endif; ?>
    </div>
</section>
<?php account_footer(); ?>
