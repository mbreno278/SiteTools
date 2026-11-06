<?php
require __DIR__ . '/_bootstrap.php';

$uid = (int) $currentUser['id'];

$q = $pdo->prepare(
    'SELECT t.id, t.name, t.slug, t.description, t.icon
     FROM user_favorites f
     INNER JOIN tools t ON t.id = f.tool_id
     WHERE f.user_id = :uid AND t.is_active = 1
     ORDER BY f.created_at DESC'
);
$q->execute(['uid' => $uid]);
$rows = $q->fetchAll();

account_page_start('Favoritos');
account_header('Favoritos', 'Acesse rapidamente as ferramentas que você salvou.');
?>

<section class="ua-panel">
    <div class="ua-panel-head">
        <h2>Suas ferramentas favoritas</h2>
        <?php if ($rows): ?>
            <span class="ua-meta-text"><?= count($rows) ?> favorito<?= count($rows) !== 1 ? 's' : '' ?></span>
        <?php endif; ?>
    </div>

    <div class="ua-list">
        <?php if (!$rows): ?>
            <div class="ua-empty">
                <span class="material-symbols-outlined">favorite_border</span>
                <p>Você ainda não adicionou ferramentas aos favoritos.</p>
                <small>Nas páginas das ferramentas ou em “Minhas ferramentas”, clique no ícone de coração para salvar.</small>
                <a class="ua-button ua-button--light" href="tools.php" style="margin-top:14px">Ver ferramentas</a>
            </div>
        <?php else: ?>
            <?php foreach ($rows as $r): ?>
                <div class="ua-list-row" id="fav-<?= (int) $r['id'] ?>">
                    <div class="ua-tool-icon">
                        <span class="material-symbols-outlined"><?= user_area_e($r['icon'] ?: 'build') ?></span>
                    </div>
                    <div class="ua-row-content">
                        <strong><?= user_area_e($r['name']) ?></strong>
                        <small><?= user_area_e($r['description'] ?? '') ?></small>
                    </div>
                    <a class="ua-button ua-button--light" href="../page.php?slug=<?= rawurlencode($r['slug']) ?>">Abrir</a>
                    <button
                        class="ua-button ua-button--danger"
                        data-favorite-toggle="<?= (int) $r['id'] ?>"
                        data-active="1"
                        data-remove-row="1"
                    >
                        Remover
                    </button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php account_footer(); ?>
