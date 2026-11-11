<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiteTools — Ferramentas online gratuitas</title>
    <meta
        name="description"
        content="Acesse ferramentas online para editar imagens, converter arquivos, trabalhar com PDFs e manipular textos de forma simples e rápida."
    >
    <meta name="theme-color" content="#F8FAFC">
    <link rel="icon" href="./assets/img/logo.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700&display=swap" preconnect="true">
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400,0..1,0" preconnect="true">
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400,0..1,0"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="<?= __DIR__ ?>/assets/css/variables.css">
    <link rel="stylesheet" href="<?= __DIR__ ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= __DIR__ ?>/assets/css/layout.css">
    <link rel="stylesheet" href="<?= __DIR__ ?>/assets/css/components.css">
    <link rel="stylesheet" href="<?= __DIR__ ?>/assets/css/modo.css">
    <link rel="stylesheet" href="<?= __DIR__ ?>/assets/css/tools-page.css">

    <link rel="stylesheet" href="<?= __DIR__ ?>/assets/css/auth.css">
    <link rel="stylesheet" href="<?= __DIR__ ?>/assets/css/auth-extra.css">
    
    <script src="<?= __DIR__ ?>/assets/js/csrf.js" defer></script>
    <script src="<?= __DIR__ ?>/assets/js/auth.js" defer></script>

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