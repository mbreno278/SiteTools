
<?php

declare(strict_types=1);

/* ========================================
   FUNÇÕES AUXILIARES
======================================== */

/**
 * Escapa valores para saída em HTML.
 */
function e(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/* ========================================
   CONFIGURAÇÃO DOS HANDLERS
======================================== */

$handlers = [
    'word-counter' => [
        'type' => 'javascript',
        'file' => 'word-counter.js',
    ],

    'image-resizer' => [
        'type' => 'javascript',
        'file' => 'image-resizer.js',
    ],
];

/* ========================================
   DADOS DA FERRAMENTA
======================================== */

// Garante que $tool seja um array.
$tool = isset($tool) && is_array($tool) ? $tool : [];

// Normaliza os dados textuais.
$toolName = (string) ($tool['name'] ?? 'Ferramenta');
$toolDescription = (string) ($tool['description'] ?? '');
$toolCategory = (string) ($tool['category'] ?? 'Ferramentas');
$toolIcon = (string) ($tool['icon'] ?? 'build');

// Define título e descrição para SEO.
$metaTitle = trim((string) ($tool['meta_title'] ?? ''));
$metaDescription = trim((string) ($tool['meta_description'] ?? ''));

$pageTitle = $metaTitle !== '' ? $metaTitle : $toolName;

$pageDescription = $metaDescription !== ''
    ? $metaDescription
    : $toolDescription;

// Resolve o handler registrado.
$handlerKey = (string) ($tool['handler'] ?? '');
$handler = $handlers[$handlerKey] ?? null;

// Verifica se o handler é suportado.
$hasJavaScriptHandler = is_array($handler)
    && ($handler['type'] ?? null) === 'javascript'
    && isset($handler['file'])
    && is_string($handler['file']);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title><?= e($pageTitle) ?></title>

    <meta
        name="description"
        content="<?= e($pageDescription) ?>"
    >

    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/tool-page.css">
</head>

<body>

    <?php require __DIR__ . '/header.php'; ?>

    <main class="tool-page">

        <!-- Navegação estrutural -->
        <nav aria-label="Navegação estrutural">
            <a href="/index.php">Início</a>

            <span aria-hidden="true">/</span>

            <span><?= e($toolCategory) ?></span>
        </nav>

        <!-- Cabeçalho da ferramenta -->
        <header class="tool-page__header">

            <span
                class="material-symbols-outlined"
                aria-hidden="true"
            ><?= e($toolIcon) ?></span>

            <h1><?= e($toolName) ?></h1>

            <p><?= e($toolDescription) ?></p>

        </header>

        <!-- Área de execução da ferramenta -->
        <section
            class="tool-page__workspace"
            aria-label="<?= e($toolName) ?>"
        >

            <?php if ($hasJavaScriptHandler): ?>

                <div id="tool-app"></div>

                <script
                    type="module"
                    src="/tools/<?= e($handler['file']) ?>"
                ></script>

            <?php else: ?>

                <p>
                    Esta ferramenta ainda não está disponível.
                </p>

            <?php endif; ?>

        </section>

    </main>

    <?php require __DIR__ . '/footer.php'; ?>

</body>
</html>