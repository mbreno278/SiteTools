
<?php

require __DIR__ . '/config/database.php';

/**
 * Escapa valores para saída HTML.
 */
function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/**
 * Identifica a ferramenta solicitada.
 */
$slug = $_GET['slug'] ?? '';

if (
    !is_string($slug) ||
    !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)
) {
    http_response_code(404);
    $slug = '';
}

$tool = null;

if ($slug !== '') {
    $stmt = $pdo->prepare(
        'SELECT id, name, slug, description, category, icon,
                handler, meta_title, meta_description
         FROM tools
         WHERE slug = :slug AND is_active = 1
         LIMIT 1'
    );

    $stmt->execute(['slug' => $slug]);

    $tool = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * Ferramenta não encontrada.
 */
if (!$tool) {
    http_response_code(404);

    $tool = [
        'name' => 'Ferramenta não encontrada',
        'slug' => '',
        'description' => 'A ferramenta solicitada não existe ou está indisponível.',
        'category' => '',
        'icon' => 'search_off',
        'handler' => 'not-found',
        'meta_title' => 'Ferramenta não encontrada | SiteTools',
        'meta_description' => 'A ferramenta solicitada não existe ou está indisponível.',
    ];
}

/**
 * Lista de handlers permitidos.
 * O banco de dados não determina caminhos de arquivos executáveis.
 */
$allowedHandlers = [
    'word-counter' => 'word-counter',
    'image-resizer' => 'image-resizer',
    'pdf-merge' => 'pdf-merge',
    'image-compressor' => 'image-compressor',
    'image-converter' => 'image-converter',
    'image-cropper' => 'image-cropper',
    'pdf-to-word' => 'pdf-to-word'
];

$handler = $allowedHandlers[$tool['handler'] ?? ''] ?? 'not-found';

$metaTitle = $tool['meta_title']
    ?: $tool['name'] . ' | SiteTools';

$metaDescription = $tool['meta_description']
    ?: $tool['description'];

$categoryLabels = [
    'imagem' => 'Editor de imagem',
    'pdf' => 'Ferramentas PDF',
    'texto' => 'Ferramentas de texto',
    'conversores' => 'Conversores',
    'utilitarios' => 'Utilitários',
];

$category = $tool['category'] ?? '';

$categoryLabel = $categoryLabels[$category]
    ?? ucfirst($category);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($metaTitle) ?></title>

    <meta
        name="description"
        content="<?= e($metaDescription) ?>"
    >

    <meta name="theme-color" content="#F8FAFC">

    <link rel="icon" href="./assets/img/logo.png" type="image/png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400,0..1,0"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="./assets/css/variables.css">
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/layout.css">
    <link rel="stylesheet" href="./assets/css/components.css">
    <link rel="stylesheet" href="./assets/css/modo.css">
    <link rel="stylesheet" href="./assets/css/page.css">

    <script src="./assets/js/tool-page.js" defer></script>

    <?php if ($handler === 'image-resizer'): ?>
        <link
            rel="stylesheet"
            href="./assets/css/image-resizer.css">
        <script
            src="./assets/js/image-resizer.js"
            defer></script>
    <?php endif; ?>

    <?php if ($handler === 'image-compressor'): ?>
        <link
            rel="stylesheet"
            href="./assets/css/image-compressor.css"
        >
        <script
            src="./assets/js/image-compressor.js"
            defer
        ></script>
    <?php endif; ?>

    <?php if ($handler === 'image-converter'): ?>
        <link
            rel="stylesheet"
            href="./assets/css/image-converter.css"
        >
        <script
            src="./assets/js/image-converter.js"
            defer
        ></script>
    <?php endif; ?>

    <?php if ($handler === 'image-cropper'): ?>
        <link
            rel="stylesheet"
            href="./assets/css/image-cropper.css"
        >
        <script
            src="./assets/js/image-cropper.js"
            defer
        ></script>
    <?php endif; ?>

    <?php if ($handler == 'pdf-to-word'): ?>
        <link rel="stylesheet" href="assets/css/pdf-to-word.css">
        <script src="assets/js/pdf-to-word.js" defer></script>
    <?php endif; ?>
    <script>
        try {
            const theme = localStorage.getItem("sitetools-theme");

            document.documentElement.dataset.theme =
                theme === "dark" ? "dark" : "light";
        } catch (error) {
            document.documentElement.dataset.theme = "light";
        }
    </script>
</head>

<body>

    <!-- Cabeçalho compartilhado -->
    <?php include __DIR__ . '/templates/header.php'; ?>

    <!-- Layout principal: mesma estrutura do index.php -->
    <main class="main grid grid__layout">

        <!-- Navegação lateral compartilhada -->
        <?php include __DIR__ . '/templates/sidebar.php'; ?>

        <!-- Conteúdo da ferramenta -->
        <div class="main__content">

            <section class="section tool-page">

                <!-- Caminho de navegação -->
                <nav class="breadcrumbs" aria-label="Você está aqui">

                    <a href="./index.php">Início</a>

                    <span
                        class="material-symbols-outlined"
                        aria-hidden="true"
                    >chevron_right</span>

                    <?php if ($categoryLabel !== ''): ?>

                        <a href="./tools.php?category=<?= rawurlencode($category) ?>">
                            <?= e($categoryLabel) ?>
                        </a>

                        <span
                            class="material-symbols-outlined"
                            aria-hidden="true"
                        >chevron_right</span>

                    <?php endif; ?>

                    <span aria-current="page">
                        <?= e($tool['name']) ?>
                    </span>

                </nav>

                <?php if ($handler === 'not-found'): ?>

                    <section class="tool-not-found">

                        <span
                            class="material-symbols-outlined"
                            aria-hidden="true"
                        >search_off</span>

                        <h1><?= e($tool['name']) ?></h1>

                        <p><?= e($tool['description']) ?></p>

                        <a
                            class="button button--primary"
                            href="./index.php"
                        >
                            Voltar ao início
                        </a>

                    </section>

                <?php else: ?>

                    <!-- Cabeçalho da ferramenta -->
                    <header class="tool-heading">

                        <div class="tool-heading__icon">
                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            ><?= e($tool['icon'] ?: 'build') ?></span>
                        </div>

                        <div class="tool-heading__content">

                            <?php if ($categoryLabel !== ''): ?>
                                <span class="tool-heading__eyebrow">
                                    <?= e($categoryLabel) ?>
                                </span>
                            <?php endif; ?>

                            <h1><?= e($tool['name']) ?></h1>

                            <p class="tool-heading__description">
                                <?= e($tool['description']) ?>
                            </p>

                        </div>

                    </header>

                    <!-- Área funcional da ferramenta -->
                    <section
                        class="tool-workspace"
                        aria-label="Área da ferramenta"
                    >

                        <?php if ($handler === 'word-counter'):
                            include __DIR__ . '/content/word-counter.php';
                        elseif ($handler === 'image-resizer'):
                            include __DIR__ . '/content/image-resizer.php';
                        elseif ($handler === 'image-compressor'):
                            include __DIR__ . '/content/image-compressor.php';
                        elseif ($handler === 'pdf-merge'):
                            include __DIR__ . '/content/pdf-merge.php';
                        elseif ($handler === 'image-converter'):
                            include __DIR__ . '/content/image-converter.php';
                        elseif ($handler === 'image-cropper'):
                            include __DIR__ . '/content/image-cropper.php';
                        elseif ($handler == 'pdf-to-word'):
                            include __DIR__ . '/content/pdf-to-word.php';
                        endif; ?>

                    </section>

                    <!-- Informações da ferramenta -->
                    <section class="tool-info">

                        <div class="tool-info__item">

                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            >bolt</span>

                            <div>
                                <strong>Simples e rápido</strong>
                                <p>
                                    Interface fácil de usar,
                                    sem etapas desnecessárias.
                                </p>
                            </div>

                        </div>

                        <div class="tool-info__item">

                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            >shield</span>

                            <div>
                                <strong>Privacidade em primeiro lugar</strong>
                                <p>
                                    Os arquivos devem ser validados e removidos
                                    após o uso conforme a implementação do serviço.
                                </p>
                            </div>

                        </div>

                        <div class="tool-info__item">

                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            >devices</span>

                            <div>
                                <strong>Em vários dispositivos</strong>
                                <p>
                                    Layout responsivo para computadores,
                                    tablets e celulares.
                                </p>
                            </div>

                        </div>

                    </section>

                <?php endif; ?>

            </section>

        </div>

    </main>

    <!-- Rodapé compartilhado -->
    <?php include __DIR__ . '/templates/footer.php'; ?>

</body>
</html>
