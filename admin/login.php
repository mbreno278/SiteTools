<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (!empty($_SESSION['admin']['id'])) {
    redirect(admin_url('index.php'));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = (string)($_POST['password'] ?? '');

    if (!$email || $password === '') {
        $error = 'Informe um e-mail válido e sua senha.';
    } else {
        try {
            $stmt = db()->prepare('SELECT id, name, email, password_hash, role, is_active FROM admin_users WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && (int)$user['is_active'] === 1 && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['admin'] = [
                    'id' => (int)$user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                ];
                record_admin_activity('login', 'Login administrativo realizado.');
                redirect(admin_url('index.php'));
            }
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
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Entrar · SiteTools Admin</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(admin_url('assets/css/admin.css')) ?>">
</head>
<body class="login-page">
<div class="login-card">
    <div class="login-brand"><span class="brand-mark">S</span><div><strong>SiteTools</strong><small>Administração</small></div></div>
    <h1>Bem-vindo de volta</h1><p class="muted">Entre com sua conta administrativa.</p>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post" autocomplete="on">
        <?= csrf_field() ?>
        <label class="field-label" for="email">E-mail</label>
        <input class="form-control" id="email" name="email" type="email" required autocomplete="username">
        <label class="field-label" for="password">Senha</label>
        <input class="form-control" id="password" name="password" type="password" required autocomplete="current-password">
        <button class="button button-primary button-full" type="submit">Entrar no painel</button>
    </form>
    <p class="login-note">Acesso restrito a administradores autorizados.</p>
</div>
</body>
</html>
