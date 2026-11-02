<?php
/**
 * Modais de Login e Cadastro — incluir no final do body (index.php e page.php)
 * Requer: config/auth.php (ou csrf.php) já carregado para csrf_field()
 */
if (!function_exists('csrf_field')) {
    require_once __DIR__ . '/../config/csrf.php';
}
$user = function_exists('auth_user') ? auth_user() : null;
?>

<!-- Modal Login -->
<div class="auth-modal" id="auth-modal-login" hidden aria-hidden="true">
    <div class="auth-modal__backdrop" data-auth-close></div>
    <div class="auth-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="auth-login-title">
        <button type="button" class="auth-modal__close" data-auth-close aria-label="Fechar">
            <span class="material-symbols-outlined" aria-hidden="true">close</span>
        </button>

        <h2 id="auth-login-title" class="auth-modal__title">Entrar</h2>
        <p class="auth-modal__subtitle">Acesse sua conta SiteTools</p>

        <form class="auth-modal__form" id="auth-login-form" method="post" action="tools/login.php" novalidate>
            <?= csrf_field() ?>

            <div class="auth-modal__field">
                <label for="login-email">E-mail</label>
                <input type="email" id="login-email" name="email" autocomplete="email" required placeholder="seu@email.com">
            </div>

            <div class="auth-modal__field">
                <label for="login-password">Senha</label>
                <input type="password" id="login-password" name="password" autocomplete="current-password" required placeholder="Sua senha" minlength="8">
            </div>

            <p class="auth-modal__error" id="login-error" role="alert" hidden></p>

            <button type="submit" class="button button--primary auth-modal__submit" id="login-submit">
                Entrar
            </button>
        </form>

        <p class="auth-modal__switch">
            Não tem conta?
            <button type="button" class="auth-modal__link" data-auth-open="register">Cadastre-se</button>
        </p>
    </div>
</div>

<!-- Modal Cadastro -->
<div class="auth-modal" id="auth-modal-register" hidden aria-hidden="true">
    <div class="auth-modal__backdrop" data-auth-close></div>
    <div class="auth-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="auth-register-title">
        <button type="button" class="auth-modal__close" data-auth-close aria-label="Fechar">
            <span class="material-symbols-outlined" aria-hidden="true">close</span>
        </button>

        <h2 id="auth-register-title" class="auth-modal__title">Criar conta</h2>
        <p class="auth-modal__subtitle">Cadastre-se gratuitamente no SiteTools</p>

        <form class="auth-modal__form" id="auth-register-form" method="post" action="tools/cadastro.php" novalidate>
            <?= csrf_field() ?>

            <div class="auth-modal__field">
                <label for="register-name">Nome</label>
                <input type="text" id="register-name" name="name" autocomplete="name" required placeholder="Seu nome" minlength="2" maxlength="120">
            </div>

            <div class="auth-modal__field">
                <label for="register-email">E-mail</label>
                <input type="email" id="register-email" name="email" autocomplete="email" required placeholder="seu@email.com">
            </div>

            <div class="auth-modal__field">
                <label for="register-password">Senha</label>
                <input type="password" id="register-password" name="password" autocomplete="new-password" required placeholder="Mínimo 8 caracteres" minlength="8">
            </div>

            <div class="auth-modal__field">
                <label for="register-password-confirm">Confirmar senha</label>
                <input type="password" id="register-password-confirm" name="password_confirm" autocomplete="new-password" required placeholder="Repita a senha" minlength="8">
            </div>

            <p class="auth-modal__error" id="register-error" role="alert" hidden></p>

            <button type="submit" class="button button--primary auth-modal__submit" id="register-submit">
                Cadastrar
            </button>
        </form>

        <p class="auth-modal__switch">
            Já tem conta?
            <button type="button" class="auth-modal__link" data-auth-open="login">Entrar</button>
        </p>
    </div>
</div>
