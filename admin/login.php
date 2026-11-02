<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';

if (admin_logged_in()) {
    redirect(admin_url('index.php'));
}

$error = '';
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $emailValue = strtolower(trim((string) ($_POST['email'] ?? '')));
    $email = filter_var($emailValue, FILTER_VALIDATE_EMAIL);
    $password = (string) ($_POST['password'] ?? '');

    if (!$email || $password === '') {
        $error = 'Informe um e-mail válido e sua senha.';
    } elseif (admin_is_login_locked($emailValue)) {
        $error = 'Muitas tentativas. Aguarde 15 minutos e tente novamente.';
    } else {
        try {
            $stmt = db()->prepare(
                'SELECT id, name, email, password_hash, role, is_active
                 FROM admin_users WHERE email = :email LIMIT 1'
            );
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if (
                $user
                && (int) $user['is_active'] === 1
                && password_verify($password, (string) $user['password_hash'])
            ) {
                admin_clear_login_failures($emailValue);
                admin_login_user($user);
                record_admin_activity('login', 'Login administrativo realizado.');
                redirect(admin_url('index.php'));
            }

            admin_record_login_failure($emailValue);
            $error = 'Credenciais inválidas ou usuário desativado.';
        } catch (Throwable $exception) {
            error_log('[SiteTools login] ' . $exception->getMessage());
            $error = 'Não foi possível autenticar. Verifique a configuração do banco de dados.';
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar · SiteTools Admin</title>
    <meta name="robots" content="noindex,nofollow">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(admin_url('assets/css/admin.css')) ?>">
    <link rel="stylesheet" href="<?= e(admin_url('assets/css/login.css')) ?>">
</head>
<body class="login-page">
    <div class="login-shell">
        <div class="login-side">
            <div class="login-side__brand">
                <span class="brand-mark">S</span>
                <div>
                    <strong>SiteTools</strong>
                    <small>Painel administrativo</small>
                </div>
            </div>
            <h2>Gerencie ferramentas, usuários e conteúdo em um só lugar.</h2>
            <ul class="login-side__list">
                <li><span class="material-symbols-rounded">shield</span> Acesso restrito e auditado</li>
                <li><span class="material-symbols-rounded">monitoring</span> Indicadores e logs</li>
                <li><span class="material-symbols-rounded">build</span> Controle das ferramentas</li>
            </ul>
        </div>

        <div class="login-card">
            <div class="login-brand mobile-only">
                <span class="brand-mark">S</span>
                <div><strong>SiteTools</strong><small>Administração</small></div>
            </div>
            <h1>Bem-vindo de volta</h1>
            <p class="muted">Entre com sua conta administrativa.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" autocomplete="on" class="login-form">
                <?= csrf_field() ?>

                <label class="field-label" for="email">E-mail</label>
                <input
                    class="form-control"
                    id="email"
                    name="email"
                    type="email"
                    required
                    autocomplete="username"
                    value="<?= e($emailValue) ?>"
                    placeholder="admin@exemplo.com"
                >

                <label class="field-label" for="password">Senha</label>
                <div class="password-field">
                    <input
                        class="form-control"
                        id="password"
                        name="password"
                        type="password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    >
                    <button type="button" class="password-toggle" id="toggle-password" aria-label="Mostrar senha">
                        <span class="material-symbols-rounded">visibility</span>
                    </button>
                </div>

                <button class="button button-primary button-full" type="submit">
                    Entrar no painel
                </button>
            </form>

            <p class="login-note">Acesso restrito a administradores autorizados.</p>
        </div>
    </div>
    <script>
    document.getElementById('toggle-password')?.addEventListener('click', function () {
        const input = document.getElementById('password');
        const icon = this.querySelector('.material-symbols-rounded');
        if (!input) return;
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        if (icon) icon.textContent = show ? 'visibility_off' : 'visibility';
        this.setAttribute('aria-label', show ? 'Ocultar senha' : 'Mostrar senha');
    });
    </script>
</body>
</html>
