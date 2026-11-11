<?php

declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/tool-access.php';

if (!function_exists('e')) {
    function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

$query = $_GET['q'] ?? $_GET['search'] ?? '';
$categorySlug = $_GET['category'] ?? '';

$query = is_string($query) ? trim($query) : '';
$categorySlug = is_string($categorySlug) ? trim($categorySlug) : '';

if (strlen($query) > 100) {
    $query = substr($query, 0, 100);
}

if ($categorySlug !== '' && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $categorySlug)) {
    $categorySlug = '';
}

$pdo = null;
$categories = [];
$tools = [];
$dbError = false;

try {
    require __DIR__ . '/config/database.php';

    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new RuntimeException('Conexão com o banco indisponível.');
    }

    $categoryStmt = $pdo->query(
        "SELECT id, name, slug
         FROM categories
         WHERE is_active = 1
         ORDER BY sort_order ASC, name ASC"
    );
    $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $sql = "
        SELECT
            t.id,
            t.name,
            t.slug,
            t.description,
            t.category,
            t.icon,
            COALESCE(c.name, t.category) AS category_name,
            COALESCE(r.access_mode, t.access_type, 'free') AS access_mode
        FROM tools t
        LEFT JOIN categories c ON c.id = t.category_id
        LEFT JOIN tool_access_rules r ON r.tool_id = t.id
        WHERE t.is_active = 1
          AND (t.status = 'active' OR t.status IS NULL OR t.status = '')
    ";

    $params = [];

    if ($query !== '') {
        $sql .= " AND (
            t.name LIKE :query_name
            OR t.description LIKE :query_description
            OR t.category LIKE :query_category
            OR c.name LIKE :query_category_name
        )";
        $searchTerm = '%' . $query . '%';
        $params['query_name'] = $searchTerm;
        $params['query_description'] = $searchTerm;
        $params['query_category'] = $searchTerm;
        $params['query_category_name'] = $searchTerm;
    }

    if ($categorySlug !== '') {
        $sql .= " AND (
            c.slug = :category_slug
            OR t.category = :legacy_category
        )";
        $params['category_slug'] = $categorySlug;
        $params['legacy_category'] = $categorySlug;
    }

    $sql .= ' ORDER BY t.sort_order ASC, t.name ASC LIMIT 200';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tools = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $exception) {
    $dbError = true;
    error_log('[tools catalog] ' . $exception->getMessage());
}

$selectedCategoryName = '';
foreach ($categories as $category) {
    if (($category['slug'] ?? '') === $categorySlug) {
        $selectedCategoryName = (string) $category['name'];
        break;
    }
}

$pageHeading = $selectedCategoryName !== ''
    ? $selectedCategoryName
    : ($query !== '' ? 'Resultados da pesquisa' : 'Todas as ferramentas');

?>
<!DOCTYPE html>
<html lang="pt-BR">

<?php require __DIR__ . '/templates/head.php'; ?>

<body>

    <?php require __DIR__ . '/templates/header.php'; ?>

    <main class="main grid grid__layout">

        <?php require __DIR__ . '/templates/sidebar.php'; ?>

        <div class="main__content tools-catalog">

            <section class="section">
                <div class="section__header">
                    <div class="section__header__column">
                        <span class="section__header__logo material-symbols-outlined" aria-hidden="true">handyman</span>
                        <h1 class="section__header__title"><?= e($pageHeading) ?></h1>
                    </div>
                </div>

                <form class="tools-catalog__search" action="tools.php" method="get" role="search">
                    <label class="sr-only" for="tools-search">Pesquisar ferramentas</label>
                    <input
                        id="tools-search"
                        type="search"
                        name="q"
                        value="<?= e($query) ?>"
                        maxlength="100"
                        placeholder="Buscar por nome, descrição ou categoria..."
                    >
                    <?php if ($categorySlug !== ''): ?>
                        <input type="hidden" name="category" value="<?= e($categorySlug) ?>">
                    <?php endif; ?>
                    <button class="button button--primary" type="submit">Pesquisar</button>
                    <?php if ($query !== '' || $categorySlug !== ''): ?>
                        <a class="button" href="tools.php">Limpar filtros</a>
                    <?php endif; ?>
                </form>

                <?php if ($categories): ?>
                    <nav class="tools-catalog__categories" aria-label="Filtrar por categoria">
                        <a class="tools-catalog__category<?= $categorySlug === '' ? ' is-active' : '' ?>" href="tools.php">Todas</a>
                        <?php foreach ($categories as $category): ?>
                            <a
                                class="tools-catalog__category<?= $categorySlug === $category['slug'] ? ' is-active' : '' ?>"
                                href="tools.php?category=<?= rawurlencode((string) $category['slug']) ?>"
                            ><?= e((string) $category['name']) ?></a>
                        <?php endforeach; ?>
                    </nav>
                <?php endif; ?>

                <?php if ($dbError): ?>
                    <div class="tools-catalog__state" role="alert">
                        <span class="material-symbols-outlined" aria-hidden="true">cloud_off</span>
                        <h2>Catálogo temporariamente indisponível</h2>
                        <p>Não foi possível carregar as ferramentas. Tente novamente em instantes.</p>
                    </div>
                <?php elseif (!$tools): ?>
                    <div class="tools-catalog__state" role="status">
                        <span class="material-symbols-outlined" aria-hidden="true">search_off</span>
                        <h2>Nenhuma ferramenta encontrada</h2>
                        <p>Altere os termos da pesquisa ou selecione outra categoria.</p>
                    </div>
                <?php else: ?>
                    <p class="tools-catalog__count">
                        <?= count($tools) ?> ferramenta(s) encontrada(s)
                    </p>

                    <div class="section__content grid grid__content">
                        <?php foreach ($tools as $tool): ?>
                            <a class="card card--link" href="page.php?slug=<?= rawurlencode((string) $tool['slug']) ?>">
                                <span class="card__logo material-symbols-outlined" aria-hidden="true"><?= e((string) ($tool['icon'] ?: 'build')) ?></span>
                                <h2 class="card__title"><?= e((string) $tool['name']) ?></h2>
                                <span class="badge"><?= e((string) $tool['category_name']) ?></span>
                                <div class="card__badge">
                                    <?= tool_access_badge_html((string) $tool['access_mode']) ?>
                                </div>
                                <p class="card__description"><?= e((string) $tool['description']) ?></p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

        </div>
    </main>

    <?php require __DIR__ . '/templates/footer.php'; ?>
    <?php include __DIR__ . '/content/content-login.php'; ?>

</body>
</html>
