<?php
if (!function_exists('auth_user')) {
    $authFile = __DIR__ . '/../config/auth.php';
    if (is_file($authFile)) {
        require_once $authFile;
    }
}
$authUser = function_exists('auth_user') ? auth_user() : null;
?>
<header class="header">

    <div class="header__column">
        <a class="header__logo" href="index.php" aria-label="SiteTools — Página inicial">

            <img
                class="page__logo"
                src="./assets/img/logo.png"
                alt=""
                width="48"
                height="48"
            >

            <div class="header__title">
                <span class="page__title">SiteTools</span>
                <span class="page__subtitle">
                    Ferramentas online para o seu dia a dia
                </span>
            </div>

        </a>
    </div>

    <div class="header__column">

        <form
            class="form__search"
            action="tools.php"
            method="get"
            role="search"
        >

            <label for="search">Buscar ferramentas</label>

            <input
                type="search"
                id="search"
                name="search"
                placeholder="Buscar ferramentas..."
                autocomplete="off"
                required
            >

            <button type="submit" aria-label="Pesquisar">
                <span
                    class="material-symbols-outlined"
                    aria-hidden="true"
                >search</span>
            </button>

        </form>

    </div>

    <div class="header__column header__column--auth">

        <button
            class="button button-mode-light"
            id="theme-toggle"
            type="button"
            aria-label="Ativar tema escuro"
            aria-pressed="false"
            title="Ativar tema escuro">
            <span
                class="material-symbols-outlined"
                aria-hidden="true">dark_mode</span>
        </button>

        <?php if ($authUser): ?>
            <div class="header__user" id="header-user">
                <a class="button" href="./account/">
                    <span class="header__user-name" title="<?= htmlspecialchars($authUser['email'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($authUser['name'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </a>
                <form method="post" action="./tools/logout.php" class="header__logout-form" id="auth-logout-form">
                    <?php if (function_exists('csrf_field')) { echo csrf_field(); } ?>
                    <button type="submit" class="button header__btn-logout" id="auth-logout-btn">
                        Sair
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="header__auth" id="header-auth">
                <button
                    type="button"
                    class="button header__btn-login"
                    data-auth-open="login"
                >
                    Login
                </button>
                <button
                    type="button"
                    class="button header__btn-register"
                    data-auth-open="register"
                >
                    Cadastro
                </button>
            </div>
        <?php endif; ?>

    </div>

</header>
