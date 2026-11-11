
<?php

    require_once __DIR__ . '/config/csrf.php';
    require_once __DIR__ . '/config/auth.php';
    require_once __DIR__ . '/config/tool-access.php';
    //include_once __DIR__ . '/config/functions-user.php';

    // ===== Sistema de Redirecionamentos =====
    require_once __DIR__ . '/config/redirects.php';
    handle_redirects();
    // ========================================

    try {
        require __DIR__ . '/config/database.php';
    } catch (Throwable $e) {
        $pdo = null;
    }

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

    if ($slug !== '' && isset($pdo) && $pdo instanceof PDO) {
        try {
            $stmt = $pdo->prepare(
                'SELECT id, name, slug, description, category, icon,
                        handler, meta_title, meta_description,
                        COALESCE(access_type, \'free\') AS access_type,
                        COALESCE(is_active, 1) AS is_active,
                        COALESCE(status, \'active\') AS status
                FROM tools
                WHERE slug = :slug
                LIMIT 1'
            );

            $stmt->execute(['slug' => $slug]);

            $tool = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (is_array($tool) && !array_key_exists('is_active', $tool)) {
                $tool['is_active'] = 1;
            }
            if (is_array($tool) && !array_key_exists('status', $tool)) {
                $tool['status'] = 'active';
            }
        } catch (Throwable $e) {
            $tool = null;
        }
    }

    /**
     * Fallback para ferramentas registradas no código
     * (útil quando o banco ainda não tem o registro).
     */
    // $fallbackTools = [
    //     'download-video-youtube' => [
    //         'name' => 'Download de Vídeo do YouTube',
    //         'slug' => 'download-video-youtube',
    //         'description' => 'Baixe vídeos do YouTube em diferentes qualidades e formatos, de forma rápida e simples.',
    //         'category' => 'downloads',
    //         'icon' => 'smart_display',
    //         'handler' => 'download-video-youtube',
    //         'meta_title' => 'Download de Vídeo do YouTube | SiteTools',
    //         'meta_description' => 'Baixe vídeos do YouTube em MP4 e outros formatos. Escolha a qualidade desejada e faça o download facilmente.',
    //     ],'formatar-texto' => [
    //         'name' => 'Formatar Texto',
    //         'slug' => 'formatar-texto',
    //         'description' => 'Converta texto em maiúsculas, minúsculas e outros formatos.',
    //         'category' => 'texto',
    //         'icon' => 'text_fields',
    //         'handler' => 'formatar-texto',
    //         'meta_title' => 'Formatar Texto | SiteTools',
    //         'meta_description' => 'Transforme texto em maiúsculas, minúsculas e outros formatos.',
    //     ],
    // ];

    if (!$tool && isset($fallbackTools[$slug])) {
        $tool = $fallbackTools[$slug];
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
        'pdf-to-word' => 'pdf-to-word',
        'download-video-youtube' => 'download-video-youtube',
        'download-audio-youtube' => 'download-audio-youtube',
        'download-video-instagram' => 'download-video-instagram',
        'download-video-tiktok' => 'download-video-tiktok',
        'compactar-pdf' => 'compactar-pdf',
        'unir-pdf' => 'unir-pdf',
        'separar-pdf' => 'separar-pdf',
        'proteger-pdf' => 'proteger-pdf',
        'desbloquear-pdf' => 'desbloquear-pdf',
        'formatar-texto' => 'formatar-texto',
        'remover-espacos' => 'remover-espacos',
        'gerador-lorem' => 'gerador-lorem',
        'word-to-pdf' => 'word-to-pdf',
    ];

    $handler = $allowedHandlers[$tool['handler'] ?? ''] ?? 'not-found';

    /** Assets e content file por handler (config/tool-registry.php) */
    $toolRegistry = is_file(__DIR__ . '/config/tool-registry.php')
        ? require __DIR__ . '/config/tool-registry.php'
        : [];
    if (!is_array($toolRegistry)) {
        $toolRegistry = [];
    }
    $toolAssets = $toolRegistry[$handler] ?? null;

    if ($handler !== 'not-found' && function_exists('module_allows_handler') && !module_allows_handler($handler)) {
        $handler = 'not-found';
        $toolAssets = null;
        $toolAccessAllowed = false;
        $toolAccess = [
            'allowed' => false,
            'mode' => 'unavailable',
            'status' => 'unavailable',
            'reason' => 'module_disabled',
            'label' => 'Indisponível',
            'tool_slug' => (string) ($tool['slug'] ?? ''),
        ];
    }

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
        'downloads' => 'Downloads',
    ];

    $category = $tool['category'] ?? '';

    $categoryLabel = $categoryLabels[$category]
        ?? ucfirst($category);

    /* Acesso / status da ferramenta */
    $toolAccess = [
        'allowed' => true,
        'mode' => 'free',
        'status' => 'active',
        'reason' => 'public',
        'label' => 'Gratuito',
    ];
    $toolAccessAllowed = true;

    // Desativada no admin → bloqueio total
    if (
        is_array($tool)
        && ($handler ?? '') !== 'not-found'
        && array_key_exists('is_active', $tool)
        && (int) $tool['is_active'] !== 1
    ) {
        $toolAccess = [
            'allowed' => false,
            'mode' => 'unavailable',
            'status' => 'unavailable',
            'reason' => 'unavailable',
            'label' => 'Indisponível',
            'tool_slug' => (string) ($tool['slug'] ?? ''),
        ];
        $toolAccessAllowed = false;
    }

    // Status operacional
    if (
        $toolAccessAllowed
        && is_array($tool)
        && ($handler ?? '') !== 'not-found'
    ) {
        $st = strtolower((string) ($tool['status'] ?? 'active'));
        if ($st === 'development' || $st === 'unavailable') {
            $toolAccess = [
                'allowed' => false,
                'mode' => $st,
                'status' => $st,
                'reason' => $st,
                'label' => $st === 'development' ? 'Em desenvolvimento' : 'Indisponível',
                'tool_slug' => (string) ($tool['slug'] ?? ''),
            ];
            $toolAccessAllowed = false;
        }
    }

    // free / login / paid
    if ($handler !== 'not-found' && $toolAccessAllowed && isset($pdo) && $pdo instanceof PDO && is_array($tool)) {
        try {
            if (!function_exists('tool_access_check')) {
                require_once __DIR__ . '/config/tool-access.php';
            }
            if (empty($tool['access_type'])) {
                $tool['access_type'] = 'free';
            }
            $toolAccess = tool_access_check($pdo, $tool);
            $toolAccessAllowed = !empty($toolAccess['allowed']);
        } catch (Throwable $e) {
            // Fail-closed: erro ao resolver autorização = acesso negado
            error_log('[page.php tool-access] ' . $e->getMessage());
            $toolAccess = [
                'allowed' => false,
                'mode' => 'unavailable',
                'status' => 'unavailable',
                'reason' => 'authorization_error',
                'label' => 'Indisponível',
                'tool_slug' => (string) ($tool['slug'] ?? ''),
            ];
            $toolAccessAllowed = false;
        }
    }

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <?= csrf_meta() ?>
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
    <link rel="stylesheet" href="./assets/css/tool-access.css">

    <script src="./assets/js/csrf.js" defer></script>
    <script src="./assets/js/tool-page.js" defer></script>

    <link rel="stylesheet" href="./assets/css/auth.css">
    <link rel="stylesheet" href="./assets/css/auth-extra.css">
    
    <script src="./assets/js/auth.js" defer></script>

    <?php
    // CSS/JS da ferramenta via registry (evita dezenas de if/elseif)
    if (is_array($toolAssets)):
        $emittedCss = [];
        foreach (($toolAssets['shared_css'] ?? []) as $cssFile):
            if (isset($emittedCss[$cssFile])) continue;
            $emittedCss[$cssFile] = true;
            $href = './assets/css/' . rawurlencode(basename((string) $cssFile));
            echo '    <link rel="stylesheet" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . "
";
        endforeach;
        foreach (($toolAssets['css'] ?? []) as $cssFile):
            if (isset($emittedCss[$cssFile])) continue;
            $emittedCss[$cssFile] = true;
            $href = './assets/css/' . rawurlencode(basename((string) $cssFile));
            echo '    <link rel="stylesheet" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . "
";
        endforeach;
        foreach (($toolAssets['js'] ?? []) as $jsFile):
            $src = './assets/js/' . rawurlencode(basename((string) $jsFile));
            echo '    <script src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" defer></script>' . "
";
        endforeach;
    endif;
    ?>

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
                            <?php if (!empty($toolAccess['mode'])): ?>
                                <div class="tool-heading__badges" style="margin-top:8px;">
                                    <?= function_exists('tool_access_badge_html') ? tool_access_badge_html((string)($toolAccess['mode'] ?? 'free')) : '' ?>
                                </div>
                            <?php endif; ?>

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
                            <?php if (!empty($toolAccess['mode'])): ?>
                                <div class="tool-heading__badges" style="margin-top:8px;">
                                    <?= function_exists('tool_access_badge_html') ? tool_access_badge_html((string)($toolAccess['mode'] ?? 'free')) : '' ?>
                                </div>
                            <?php endif; ?>

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

                        <?php if (!$toolAccessAllowed): ?>
                            <?= function_exists('tool_access_block_html') ? tool_access_block_html($toolAccess) : '<p>Ferramenta indisponível.</p>' ?>
                        <?php else: ?>
                        <?php
                        if (is_array($toolAssets) && !empty($toolAssets['content'])) {
                            $contentFile = basename((string) $toolAssets['content']);
                            $contentPath = __DIR__ . '/content/' . $contentFile;
                            if (is_file($contentPath)) {
                                include $contentPath;
                            } else {
                                echo '<p class="tool-workspace__missing">Conteúdo da ferramenta indisponível.</p>';
                            }
                        } elseif ($handler !== 'not-found') {
                            echo '<p class="tool-workspace__missing">Ferramenta sem interface registrada.</p>';
                        }
                        ?>
                        <?php endif; /* tool access */ ?>

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
    
    <?php /*include __DIR__ . '/content/content-login.php';*/ ?>
</body>
</html>
