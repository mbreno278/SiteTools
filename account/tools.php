<?php
require __DIR__ . '/_bootstrap.php';

$uid = (int) $currentUser['id'];

// Filtro
$filter = $_GET['filter'] ?? 'all';
$search = trim((string) ($_GET['q'] ?? ''));

$q = $pdo->prepare(
    "SELECT t.id, t.name, t.slug, t.description, t.icon, t.access_type,
            COALESCE(r.access_mode, t.access_type, 'free') AS access_mode,
            CASE WHEN EXISTS (
                SELECT 1 FROM user_tool_access a
                WHERE a.user_id = :uid AND a.tool_id = t.id
                  AND a.status = 'active'
                  AND a.starts_at <= NOW()
                  AND (a.expires_at IS NULL OR a.expires_at > NOW())
            ) THEN 1 ELSE 0 END AS has_access,
            CASE WHEN EXISTS (
                SELECT 1 FROM user_favorites f
                WHERE f.user_id = :uid2 AND f.tool_id = t.id
            ) THEN 1 ELSE 0 END AS is_favorite
     FROM tools t
     LEFT JOIN tool_access_rules r ON r.tool_id = t.id
     WHERE t.is_active = 1
     ORDER BY t.name"
);
$q->execute(['uid' => $uid, 'uid2' => $uid]);
$allTools = $q->fetchAll();

/** Normaliza modo de acesso conforme schema real (free|login|paid|temporary|...) */
$normalizeMode = static function (array $tool): string {
    $mode = strtolower((string) ($tool['access_mode'] ?? $tool['access_type'] ?? 'free'));
    if (in_array($mode, ['paid', 'temporary'], true)) {
        return 'paid';
    }
    if ($mode === 'login') {
        return 'login';
    }
    return 'free';
};

// Aplicar filtros em PHP
$tools = array_filter($allTools, function ($tool) use ($filter, $search, $normalizeMode) {
    $mode = $normalizeMode($tool);
    $hasAccess = (int) $tool['has_access'] === 1;

    if ($search !== '') {
        $hay = mb_strtolower($tool['name'] . ' ' . ($tool['description'] ?? ''));
        if (!str_contains($hay, mb_strtolower($search))) {
            return false;
        }
    }

    return match ($filter) {
        'free'     => $mode === 'free',
        'login'    => $mode === 'login',
        'access'   => $hasAccess || $mode !== 'paid',
        'paid'     => $mode === 'paid',
        'favorite' => (int) $tool['is_favorite'] === 1,
        default    => true,
    };
});

$counts = [
    'all'      => count($allTools),
    'free'     => 0,
    'login'    => 0,
    'access'   => 0,
    'paid'     => 0,
    'favorite' => 0,
];
foreach ($allTools as $t) {
    $mode = $normalizeMode($t);
    $hasAccess = (int) $t['has_access'] === 1;
    if ($mode === 'free') {
        $counts['free']++;
    }
    if ($mode === 'login') {
        $counts['login']++;
    }
    if ($hasAccess || $mode !== 'paid') {
        $counts['access']++;
    }
    if ($mode === 'paid') {
        $counts['paid']++;
    }
    if ((int) $t['is_favorite'] === 1) {
        $counts['favorite']++;
    }
}

account_page_start('Minhas ferramentas');
account_header('Minhas ferramentas', 'Veja as ferramentas disponíveis e gerencie seus acessos.');
?>

<section class="ua-panel">
    <div class="ua-toolbar">
        <form class="ua-search" method="get" action="tools.php">
            <input type="hidden" name="filter" value="<?= user_area_e($filter) ?>">
            <span class="material-symbols-outlined">search</span>
            <input type="search" name="q" value="<?= user_area_e($search) ?>" placeholder="Buscar ferramenta..." autocomplete="off">
        </form>

        <div class="ua-filters">
            <?php
            $filters = [
                'all'      => 'Todas',
                'free'     => 'Gratuitas',
                'login'    => 'Com login',
                'access'   => 'Com acesso',
                'paid'     => 'Pagas',
                'favorite' => 'Favoritas',
            ];
            foreach ($filters as $key => $label):
                $active = $filter === $key ? ' is-active' : '';
                $url = 'tools.php?filter=' . urlencode($key) . ($search ? '&q=' . urlencode($search) : '');
            ?>
                <a class="ua-filter<?= $active ?>" href="<?= $url ?>">
                    <?= user_area_e($label) ?>
                    <span class="ua-filter-count"><?= (int) ($counts[$key] ?? 0) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="ua-list">
        <?php if (!$tools): ?>
            <div class="ua-empty">
                <span class="material-symbols-outlined">search_off</span>
                <p>Nenhuma ferramenta encontrada com os filtros atuais.</p>
                <a class="ua-button ua-button--light" href="tools.php">Limpar filtros</a>
            </div>
        <?php else: ?>
            <?php foreach ($tools as $tool):
                $mode = $normalizeMode($tool);
                $hasAccess = (int) $tool['has_access'] === 1;
                $isFav = (int) $tool['is_favorite'] === 1;
            ?>
                <div class="ua-list-row">
                    <div class="ua-tool-icon">
                        <span class="material-symbols-outlined"><?= user_area_e($tool['icon'] ?: 'build') ?></span>
                    </div>
                    <div class="ua-row-content">
                        <strong><?= user_area_e($tool['name']) ?></strong>
                        <small><?= user_area_e($tool['description'] ?? '') ?></small>
                    </div>

                    <?php if ($mode === 'free'): ?>
                        <span class="ua-status">Gratuita</span>
                        <a class="ua-button ua-button--light" href="../page.php?slug=<?= rawurlencode($tool['slug']) ?>">Abrir</a>
                    <?php elseif ($mode === 'login'): ?>
                        <span class="ua-status">Login</span>
                        <a class="ua-button" href="../page.php?slug=<?= rawurlencode($tool['slug']) ?>">Usar ferramenta</a>
                    <?php elseif ($hasAccess): ?>
                        <span class="ua-status">Acesso ativo</span>
                        <a class="ua-button" href="../page.php?slug=<?= rawurlencode($tool['slug']) ?>">Usar ferramenta</a>
                    <?php else: ?>
                        <span class="ua-status ua-status--expired">Sem acesso</span>
                        <a class="ua-button ua-button--light" href="subscriptions.php">Ver acesso</a>
                    <?php endif; ?>

                    <button
                        class="ua-icon-btn<?= $isFav ? ' is-active' : '' ?>"
                        data-favorite-toggle="<?= (int) $tool['id'] ?>"
                        data-active="<?= $isFav ? '1' : '0' ?>"
                        title="<?= $isFav ? 'Remover dos favoritos' : 'Adicionar aos favoritos' ?>"
                    >
                        <span class="material-symbols-outlined"><?= $isFav ? 'favorite' : 'favorite_border' ?></span>
                    </button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php account_footer(); ?>
