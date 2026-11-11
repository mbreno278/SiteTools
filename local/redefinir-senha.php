<?php

declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';

$token = trim((string) ($_GET['token'] ?? ''));
$valid = $token !== '' && preg_match('/^[a-f0-9]{64}$/', $token);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir senha | SiteTools</title>
    <?= csrf_meta() ?>
    <link rel="stylesheet" href="./assets/css/variables.css">
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/components.css">
    <link rel="stylesheet" href="./assets/css/auth.css">
    <script src="./assets/js/csrf.js" defer></script>
</head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;padding:24px;">
    <div class="auth-modal__dialog" style="position:relative;">
        <h1 class="auth-modal__title">Nova senha</h1>
        <?php if (!$valid): ?>
            <p class="auth-modal__subtitle">Link inválido. Solicite uma nova recuperação de senha.</p>
            <a class="button button--primary auth-modal__submit" href="./index.php" style="display:flex;text-decoration:none;">Voltar</a>
        <?php else: ?>
            <p class="auth-modal__subtitle">Defina uma nova senha para sua conta.</p>
            <form class="auth-modal__form" id="reset-form" method="post" action="./tools/redefinir-senha.php">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                <div class="auth-modal__field">
                    <label for="password">Nova senha</label>
                    <input type="password" id="password" name="password" minlength="8" required autocomplete="new-password">
                </div>
                <div class="auth-modal__field">
                    <label for="password_confirm">Confirmar senha</label>
                    <input type="password" id="password_confirm" name="password_confirm" minlength="8" required autocomplete="new-password">
                </div>
                <p class="auth-modal__error" id="reset-error" hidden></p>
                <p class="auth-modal__subtitle" id="reset-ok" hidden style="color:#059669;"></p>
                <button type="submit" class="button button--primary auth-modal__submit">Salvar senha</button>
            </form>
            <script>
            document.getElementById('reset-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const form = e.target;
                const err = document.getElementById('reset-error');
                const ok = document.getElementById('reset-ok');
                err.hidden = true; ok.hidden = true;
                const fd = new FormData(form);
                try {
                    const res = await fetch(form.action, { method: 'POST', body: fd, credentials: 'same-origin' });
                    const data = await res.json();
                    if (!res.ok || data.error) throw new Error(data.error || 'Falha');
                    ok.textContent = data.message || 'Senha redefinida!';
                    ok.hidden = false;
                    form.hidden = true;
                    setTimeout(() => location.href = './index.php', 2000);
                } catch (ex) {
                    err.textContent = ex.message;
                    err.hidden = false;
                }
            });
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
