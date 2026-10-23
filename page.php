
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

                        <?php if ($handler === 'word-counter'): ?>

                            <div class="workspace__header">

                                <div>
                                    <h2>Digite ou cole seu texto</h2>
                                    <p>
                                        A contagem é atualizada automaticamente
                                        no seu navegador.
                                    </p>
                                </div>

                                <button
                                    class="button button--secondary button--compact"
                                    type="button"
                                    data-clear-text
                                >
                                    <span
                                        class="material-symbols-outlined"
                                        aria-hidden="true"
                                    >delete</span>
                                    Limpar
                                </button>

                            </div>

                            <label
                                class="sr-only"
                                for="word-counter-input"
                            >
                                Texto para contar
                            </label>

                            <textarea
                                id="word-counter-input"
                                class="text-workspace"
                                placeholder="Comece a digitar ou cole seu texto aqui..."
                                spellcheck="true"
                            ></textarea>

                            <div class="stats-grid" aria-live="polite">

                                <div class="stat-card">
                                    <span>Palavras</span>
                                    <strong id="stat-words">0</strong>
                                </div>

                                <div class="stat-card">
                                    <span>Caracteres</span>
                                    <strong id="stat-chars">0</strong>
                                </div>

                                <div class="stat-card">
                                    <span>Sem espaços</span>
                                    <strong id="stat-chars-no-spaces">0</strong>
                                </div>

                                <div class="stat-card">
                                    <span>Linhas</span>
                                    <strong id="stat-lines">0</strong>
                                </div>

                            </div>

                            <div class="workspace__footer">

                                <span class="privacy-note">
                                    <span
                                        class="material-symbols-outlined"
                                        aria-hidden="true"
                                    >lock</span>
                                    Seu texto é processado localmente no navegador.
                                </span>

                                <button
                                    class="button button--primary"
                                    type="button"
                                    data-copy-text
                                >
                                    <span
                                        class="material-symbols-outlined"
                                        aria-hidden="true"
                                    >content_copy</span>
                                    Copiar texto
                                </button>

                            </div>

                        <?php elseif ($handler === 'image-resizer'): ?>

                            <div class="workspace__header">
                                <div>
                                    <h2>Redimensione sua imagem</h2>
                                    <p>
                                        Escolha uma imagem e defina as dimensões desejadas.
                                    </p>
                                </div>
                            </div>

                            <form
                                class="tool-form"
                                id="image-resizer-form"
                                enctype="multipart/form-data"
                            >

                                <label class="upload-zone" for="image-file">

                                    <span
                                        class="material-symbols-outlined"
                                        aria-hidden="true"
                                    >cloud_upload</span>

                                    <strong>Escolher uma imagem</strong>

                                    <span>PNG, JPG ou WebP · até 10 MB</span>

                                    <input
                                        id="image-file"
                                        name="file"
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp"
                                        required
                                    >

                                </label>

                                <div class="file-preview" id="image-preview" hidden>

                                    <img
                                        id="image-preview-img"
                                        alt="Pré-visualização da imagem selecionada"
                                    >

                                    <span id="image-file-name"></span>

                                </div>

                                <div class="form-row">

                                    <div class="field">
                                        <label for="image-width">
                                            Largura máxima (px)
                                        </label>

                                        <input
                                            id="image-width"
                                            name="width"
                                            type="number"
                                            min="1"
                                            max="5000"
                                            value="1200"
                                            required
                                        >
                                    </div>

                                    <div class="field">
                                        <label for="image-height">
                                            Altura máxima (px)
                                        </label>

                                        <input
                                            id="image-height"
                                            name="height"
                                            type="number"
                                            min="1"
                                            max="5000"
                                            value="1200"
                                            required
                                        >
                                    </div>

                                </div>

                                <p class="form-help">
                                    A proporção original será preservada.
                                    O processamento depende da API Python.
                                </p>

                                <button
                                    class="button button--primary"
                                    type="submit"
                                >
                                    <span
                                        class="material-symbols-outlined"
                                        aria-hidden="true"
                                    >photo_size_select_large</span>
                                    Redimensionar imagem
                                </button>

                                <p
                                    class="form-status"
                                    id="image-status"
                                    role="status"
                                    aria-live="polite"
                                ></p>

                            </form>

                        <?php elseif ($handler === 'pdf-merge'): ?>

                            <div class="workspace__header">
                                <div>
                                    <h2>Una seus arquivos PDF</h2>
                                    <p>
                                        Selecione dois ou mais PDFs.
                                        A ordem selecionada será usada na união.
                                    </p>
                                </div>
                            </div>

                            <form
                                class="tool-form"
                                id="pdf-merge-form"
                                enctype="multipart/form-data"
                            >

                                <label class="upload-zone" for="pdf-files">

                                    <span
                                        class="material-symbols-outlined"
                                        aria-hidden="true"
                                    >picture_as_pdf</span>

                                    <strong>Selecionar arquivos PDF</strong>

                                    <span>Selecione pelo menos dois arquivos</span>

                                    <input
                                        id="pdf-files"
                                        name="files[]"
                                        type="file"
                                        accept="application/pdf,.pdf"
                                        multiple
                                        required
                                    >

                                </label>

                                <ol
                                    class="selected-files"
                                    id="pdf-file-list"
                                ></ol>

                                <p class="form-help">
                                    Os arquivos devem ser validados pelo backend
                                    antes do processamento.
                                </p>

                                <button
                                    class="button button--primary"
                                    type="submit"
                                >
                                    <span
                                        class="material-symbols-outlined"
                                        aria-hidden="true"
                                    >merge</span>
                                    Unir PDFs
                                </button>

                                <p
                                    class="form-status"
                                    id="pdf-status"
                                    role="status"
                                    aria-live="polite"
                                ></p>

                            </form>

                        <?php endif; ?>

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
