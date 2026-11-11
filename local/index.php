
<?php

//declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/tool-access.php';

// ===== Sistema de Redirecionamentos =====
require_once __DIR__ . '/config/redirects.php';
handle_redirects();
// ========================================

/* ========================================
   FUNÇÕES AUXILIARES
======================================== */

if (!function_exists('e')) {
    function e(string $value): string
    {
        return htmlspecialchars(
            $value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
}

/* ========================================
   CONFIGURAÇÃO DA PÁGINA
======================================== */

$sections = [
    [
        'id' => 'editor-imagem',
        'title' => 'Remover fundo da Imagem',
        'icon' => [
            'type' => 'image',
            'src' => './assets/img/logo-section-edit-image.png',
            'alt' => '',
        ],
        'tools' => [
            [
                'title' => 'Cortar imagem',
                'description' => 'Corte imagens, personalize o tamanho de suas imagens.',
                'url' => 'page.php?slug=image-cropper',
                'access_type' => 'free',
                'image' => './assets/img/editor-imagem.png',
            ],
            [
                'title' => 'Redimensionar imagem',
                'description' => 'Ajuste a largura e a altura das suas imagens de acordo com sua necessidade.',
                'url' => 'page.php?slug=redimensionar-imagem',
                'access_type' => 'free',
                'image' => './assets/img/redimencionar-imagem.png',
            ],
            [
                'title' => 'Comprimir imagem',
                'description' => 'Reduza o tamanho dos arquivos de imagem para facilitar o armazenamento e o envio.',
                'url' => 'page.php?slug=image-compressor',
                'access_type' => 'free',
                'image' => './assets/img/icon-compress.png',
            ],
            [
                'title' => 'Converter imagem',
                'description' => 'Converta imagens entre formatos compatíveis, conforme suas necessidades.',
                'url' => 'page.php?slug=image-converter',
                'access_type' => 'free',
                'image' => './assets/img/converter-imagem.png',
            ],
        ],
    ],

    [
        'id' => 'conversores',
        'title' => 'Conversores',
        'icon' => [
            'type' => 'material',
            'name' => 'swap_horiz',
        ],
        'tools' => [
            [
                'title' => 'PDF para Word',
                'description' => 'Transforme documentos PDF em arquivos editáveis do Word, quando compatíveis.',
                'url' => 'page.php?slug=pdf-to-word',
                'access_type' => 'login',
                'image' => './assets/img/icon-pdf.png',
            ],
            [
                'title' => 'Word para PDF',
                'description' => 'Converta documentos compatíveis para o formato PDF.',
                'url' => 'page.php?slug=word-to-pdf',
                'access_type' => 'login',
                'image' => './assets/img/icon-word.png',
            ],
            [
                'title' => 'JPG para PNG',
                'description' => 'Converta suas imagens JPG para o formato PNG.',
                'url' => 'page.php?slug=jpg-to-png',
                'access_type' => 'free',
                'image' => './assets/img/icon-jpg.png',
            ],
            [
                'title' => 'PNG para JPG',
                'description' => 'Converta imagens PNG para JPG.',
                'url' => 'page.php?slug=png-to-jpg',
                'access_type' => 'free',
                'image' => './assets/img/icon-png.png',
            ],
        ],
    ],

    [
        'id' => 'downloads',
        'title' => 'Downloads',
        'icon' => [
            'type' => 'material',
            'name' => 'download',
        ],
        'tools' => [
            [
                'title' => 'Vídeos do YouTube',
                'description' => 'Acesse informações e recursos disponíveis para vídeos da plataforma.',
                'url' => 'page.php?slug=download-video-youtube',
                'access_type' => 'paid',
                'image' => './assets/img/icon-youtube.png',
            ],
            [
                'title' => 'Áudio do YouTube',
                'description' => 'Explore recursos para trabalhar com conteúdo de áudio autorizado.',
                'url' => 'page.php?slug=download-audio-youtube',
                'access_type' => 'paid',
                'image' => './assets/img/icon-music.png',
            ],
            [
                'title' => 'Vídeo do Instagram',
                'description' => 'Consulte recursos compatíveis com conteúdo que você tem autorização para utilizar.',
                'url' => 'page.php?slug=download-video-instagram',
                'access_type' => 'paid',
                'image' => './assets/img/icon-instagram.png',
            ],
            [
                'title' => 'Vídeo do TikTok',
                'description' => 'Explore recursos compatíveis com a plataforma e suas permissões de uso.',
                'url' => 'page.php?slug=download-video-tiktok',
                'access_type' => 'paid',
                'image' => './assets/img/icon-tiktok.png',
            ],
        ],
    ],

    [
        'id' => 'pdf',
        'title' => 'Ferramentas PDF',
        'icon' => [
            'type' => 'material',
            'name' => 'picture_as_pdf',
        ],
        'tools' => [
            [
                'title' => 'Compactar PDF',
                'description' => 'Reduza o tamanho de documentos PDF, conforme os recursos disponíveis.',
                'url' => 'page.php?slug=compactar-pdf',
                'access_type' => 'login',
                'image' => './assets/img/icon-download.png',
            ],
            [
                'title' => 'Unir PDF',
                'description' => 'Combine vários documentos PDF em um único arquivo.',
                'url' => 'page.php?slug=unir-pdf',
                'access_type' => 'login',
                'image' => './assets/img/logo.png',
            ],
            [
                'title' => 'Proteger PDF',
                'description' => 'Adicione proteção aos documentos utilizando os recursos de segurança disponíveis.',
                'url' => 'page.php?slug=proteger-pdf',
                'access_type' => 'login',
                'image' => './assets/img/icon-close.png',
            ],
            [
                'title' => 'Desbloquear PDF',
                'description' => 'Trabalhe com documentos que você tem autorização para modificar.',
                'url' => 'page.php?slug=desbloquear-pdf',
                'access_type' => 'login',
                'image' => './assets/img/icon-open.png',
            ],
        ],
    ],

    [
        'id' => 'texto',
        'title' => 'Ferramentas de texto',
        'icon' => [
            'type' => 'material',
            'name' => 'text_fields',
        ],
        'tools' => [
            [
                'title' => 'Contador de palavras',
                'description' => 'Conte palavras, caracteres e outros elementos do seu texto.',
                'url' => 'page.php?slug=contador-de-palavras',
                'access_type' => 'free',
                'image' => './assets/img/icon-t.png',
            ],
            [
                'title' => 'Formatar Texto',
                'description' => 'Converta seu texto para maiúsculas, minúsculas e outros formatos.',
                'url' => 'page.php?slug=formatar-texto',
                'access_type' => 'free',
                'image' => './assets/img/icon-text.png',
            ],
            [
                'title' => 'Remover espaços',
                'description' => 'Remova espaços desnecessários e organize seu texto.',
                'url' => 'page.php?slug=remover-espacos',
                'access_type' => 'free',
                'image' => './assets/img/icon-remove-space.png',
            ],
            [
                'title' => 'Gerador de texto',
                'description' => 'Gere textos de exemplo para testes, demonstrações e protótipos.',
                'url' => 'page.php?slug=gerador-texto',
                'access_type' => 'free',
                'image' => './assets/img/logo.png',
            ],
        ],
    ],
];

/* ========================================
   BENEFÍCIOS
======================================== */

$benefits = [
    [
        'icon' => 'bolt',
        'title' => 'Prático e rápido',
        'description' => 'Ferramentas para facilitar suas tarefas.',
    ],
    [
        'icon' => 'shield',
        'title' => 'Privacidade',
        'description' => 'Consulte como cada ferramenta processa seus arquivos.',
    ],
    [
        'icon' => 'cloud',
        'title' => 'Acesso online',
        'description' => 'Utilize o site diretamente pelo navegador.',
    ],
    [
        'icon' => 'favorite',
        'title' => 'Fácil de usar',
        'description' => 'Uma interface simples e intuitiva.',
    ],
];

?>

<!DOCTYPE html>
<html lang="pt-BR">

<?php require __DIR__ . '/templates/head.php'; ?>
    <link rel="stylesheet" href="./assets/css/tool-access.css">

<body>

    <!-- Cabeçalho -->
    <?php require __DIR__ . '/templates/header.php'; ?>

    <!-- Layout principal -->
    <main class="main grid grid__layout">

        <!-- Navegação lateral -->
        <?php require __DIR__ . '/templates/sidebar.php'; ?>

        <!-- Conteúdo principal -->
        <div class="main__content">

            <!-- ========================================
                 HERO
            ========================================= -->

            <section class="hero">

                <div class="hero__content">

                    <span class="hero__eyebrow">
                        Ferramentas online gratuitas
                    </span>

                    <h1 class="hero__title">
                        Tudo que você precisa,
                        <span>em um só lugar</span>
                    </h1>

                    <p class="hero__description">
                        Ferramentas online gratuitas para facilitar
                        seu dia a dia. Rápido, simples e seguro.
                    </p>

                    <form
                        class="hero__search"
                        action="tools.php"
                        method="get"
                        role="search"
                    >
                        <label
                            class="sr-only"
                            for="hero-search"
                        >
                            Pesquisar ferramentas
                        </label>

                        <input
                            id="hero-search"
                            type="search"
                            name="q"
                            placeholder="Buscar uma ferramenta..."
                            autocomplete="off"
                            required
                        >

                        <button
                            type="submit"
                            aria-label="Pesquisar ferramentas"
                        >
                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            >search</span>
                        </button>
                    </form>

                    <div class="hero__features">

                        <span>
                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            >check_circle</span>
                            Grátis
                        </span>

                        <span>
                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            >bolt</span>
                            Rápido
                        </span>

                        <span>
                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            >shield</span>
                            Fácil de usar
                        </span>

                    </div>

                </div>

                <div class="hero__visual">

                    <img
                        src="./assets/img/image-hero.png"
                        alt="Ilustração de ferramentas online"
                        width="560"
                        height="420"
                        fetchpriority="high"
                    >

                </div>

            </section>

            <!-- ========================================
                 CATEGORIAS DE FERRAMENTAS
            ========================================= -->

            <?php foreach ($sections as $section): ?>

                <section
                    class="section"
                    id="<?= e($section['id']) ?>"
                    aria-labelledby="<?= e($section['id']) ?>-title"
                >

                    <div class="section__header">

                        <div class="section__header__column">

                            <?php if ($section['icon']['type'] === 'image'): ?>

                                <img
                                    src="<?= e($section['icon']['src']) ?>"
                                    alt="<?= e($section['icon']['alt']) ?>"
                                    class="section__header__logo"
                                    width="28"
                                    height="28"
                                    loading="lazy"
                                >

                            <?php else: ?>

                                <span
                                    class="section__header__logo material-symbols-outlined"
                                    aria-hidden="true"
                                ><?= e($section['icon']['name']) ?></span>

                            <?php endif; ?>

                            <h2
                                id="<?= e($section['id']) ?>-title"
                                class="section__header__title"
                            >
                                <?= e($section['title']) ?>
                            </h2>

                        </div>

                    </div>

                    <div class="section__content grid grid__content">

                        <?php foreach ($section['tools'] as $tool): ?>

                            <a
                                href="<?= e($tool['url']) ?>"
                                class="card card--link"
                            >

                                <img
                                    src="<?= e($tool['image']) ?>"
                                    alt=""
                                    class="card__logo"
                                    width="56"
                                    height="56"
                                    loading="lazy"
                                >

                                <h3 class="card__title">
                                    <?= e($tool['title']) ?>
                                </h3>

                                <?php if (!empty($tool['access_type']) && function_exists('tool_access_badge_html')): ?>
                                    <div class="card__badge" style="margin: 6px 0 8px;">
                                        <?= tool_access_badge_html((string)$tool['access_type']) ?>
                                    </div>
                                <?php endif; ?>

                                <p class="card__description">
                                    <?= e($tool['description']) ?>
                                </p>

                            </a>

                        <?php endforeach; ?>

                    </div>

                </section>

            <?php endforeach; ?>

            <!-- ========================================
                 BENEFÍCIOS
            ========================================= -->

            <section
                class="sec"
                aria-label="Benefícios do SiteTools"
            >

                <?php foreach ($benefits as $benefit): ?>

                    <div class="boxinfo">

                        <div class="flx-col">

                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            ><?= e($benefit['icon']) ?></span>

                        </div>

                        <div class="flx-col">

                            <span><?= e($benefit['title']) ?></span>

                            <span><?= e($benefit['description']) ?></span>

                        </div>

                    </div>

                <?php endforeach; ?>

            </section>

        </div>

    </main>

    <!-- Rodapé -->
    <?php require __DIR__ . '/templates/footer.php'; ?>

    <?php include __DIR__ . '/content/content-login.php'; ?>
</body>
</html>