<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/user-area.php';

$currentUser = user_area_require_login();
$accountName = trim((string) ($currentUser['name'] ?? '')) ?: 'Usuário';
$accountEmail = (string) ($currentUser['email'] ?? '');
$accountPage = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');

// Fallback caso o projeto ainda não exponha csrf_field()
if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        $token = '';
        if (function_exists('csrf_token')) {
            $token = (string) csrf_token();
        } elseif (isset($_SESSION['csrf_token'])) {
            $token = (string) $_SESSION['csrf_token'];
        } elseif (isset($_SESSION['_csrf_token'])) {
            $token = (string) $_SESSION['_csrf_token'];
        }

        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
    }
}

function account_active(string $file): string
{
    global $accountPage;
    return $accountPage === $file ? ' is-active' : '';
}

function account_header(string $title, string $subtitle = ''): void
{
    global $accountName, $accountEmail;
    ?>
    <header class="ua-topbar">
        <a class="ua-brand" href="../index.php">
            <span class="material-symbols-outlined">grid_view</span> SiteTools
        </a>
        <div class="ua-top-user">
            <span class="ua-avatar"><?= user_area_e(strtoupper(substr($accountName, 0, 1))) ?></span>
            <span>
                <strong><?= user_area_e($accountName) ?></strong>
                <small><?= user_area_e($accountEmail) ?></small>
            </span>
            <a class="ua-logout" href="../tools/logout.php" title="Sair">
                <span class="material-symbols-outlined">logout</span>
            </a>
        </div>
    </header>

    <div class="ua-shell">
        <aside class="ua-sidebar">
            <div class="ua-side-label">CONTA</div>
            <a class="ua-nav<?= account_active('index.php') ?>" href="index.php">
                <span class="material-symbols-outlined">dashboard</span>Painel
            </a>
            <a class="ua-nav<?= account_active('tools.php') ?>" href="tools.php">
                <span class="material-symbols-outlined">handyman</span>Minhas ferramentas
            </a>
            <a class="ua-nav<?= account_active('subscriptions.php') ?>" href="subscriptions.php">
                <span class="material-symbols-outlined">key</span>Meus acessos
            </a>
            <a class="ua-nav<?= account_active('history.php') ?>" href="history.php">
                <span class="material-symbols-outlined">history</span>Histórico
            </a>
            <a class="ua-nav<?= account_active('files.php') ?>" href="files.php">
                <span class="material-symbols-outlined">folder_open</span>Meus arquivos
            </a>
            <a class="ua-nav<?= account_active('favorites.php') ?>" href="favorites.php">
                <span class="material-symbols-outlined">favorite</span>Favoritos
            </a>

            <div class="ua-side-label ua-side-label--spaced">PREFERÊNCIAS</div>
            <a class="ua-nav<?= account_active('profile.php') ?>" href="profile.php">
                <span class="material-symbols-outlined">manage_accounts</span>Minha conta
            </a>
            <a class="ua-nav<?= account_active('security.php') ?>" href="security.php">
                <span class="material-symbols-outlined">shield</span>Segurança
            </a>
            <a class="ua-nav" href="../contact.php">
                <span class="material-symbols-outlined">help</span>Ajuda
            </a>
            <a class="ua-back" href="../index.php">
                <span class="material-symbols-outlined">arrow_back</span>Voltar ao SiteTools
            </a>
        </aside>

        <main class="ua-main">
            <div class="ua-heading">
                <div>
                    <p class="ua-eyebrow">ÁREA DO USUÁRIO</p>
                    <h1><?= user_area_e($title) ?></h1>
                    <?php if ($subtitle): ?>
                        <p><?= user_area_e($subtitle) ?></p>
                    <?php endif; ?>
                </div>
            </div>
    <?php
}

function account_footer(): void
{
    ?>
        </main>
    </div>

    <div id="ua-toast" class="ua-toast" hidden></div>
    <script src="assets/account.js"></script>
    </body>
    </html>
    <?php
}

function account_page_start(string $title): void
{
    ?>
    <!doctype html>
    <html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <?= csrf_meta() ?>
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="theme-color" content="#f8fafc">
        <title><?= user_area_e($title) ?> | SiteTools</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400,0,0" rel="stylesheet">
        <link rel="stylesheet" href="assets/account.css">
    </head>
    <body>
    <?php
}

/** Formata bytes de forma legível */
function account_format_bytes(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1048576) {
        return number_format($bytes / 1024, 1, ',', '.') . ' KB';
    }
    return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
}

/** Retorna badge HTML de status */
function account_status_badge(string $status): string
{
    $map = [
        'completed' => ['Concluído', ''],
        'failed'    => ['Falhou', 'ua-status--failed'],
        'started'   => ['Iniciado', 'ua-status--pending'],
        'active'    => ['Ativo', ''],
        'expired'   => ['Expirado', 'ua-status--expired'],
        'pending'   => ['Pendente', 'ua-status--pending'],
        'paid'      => ['Pago', ''],
        'cancelled' => ['Cancelado', 'ua-status--cancelled'],
        'suspended' => ['Suspenso', 'ua-status--suspended'],
    ];

    $label = $map[$status][0] ?? ucfirst($status);
    $class = $map[$status][1] ?? '';

    return '<span class="ua-status ' . $class . '">' . user_area_e($label) . '</span>';
}
