<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

$uid = (int) $currentUser['id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $current = (string) ($_POST['current_password'] ?? '');
    $new = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    if (strlen($new) < 8) {
        $error = 'A nova senha deve ter no mínimo 8 caracteres.';
    } elseif ($new !== $confirm) {
        $error = 'A confirmação da senha não confere.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $uid]);
            $hash = (string) ($stmt->fetchColumn() ?: '');
            if ($hash === '' || !password_verify($current, $hash)) {
                $error = 'Senha atual incorreta.';
            } else {
                $pdo->prepare(
                    'UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id'
                )->execute([
                    'hash' => password_hash($new, PASSWORD_DEFAULT),
                    'id' => $uid,
                ]);
                $message = 'Senha alterada com sucesso.';
            }
        } catch (Throwable $e) {
            error_log('[account/security] ' . $e->getMessage());
            $error = 'Não foi possível alterar a senha.';
        }
    }
}

$csrf = function_exists('csrf_token') ? csrf_token() : '';

account_page_start('Segurança');
account_header('Segurança', 'Altere sua senha e proteja a conta.');
?>
<section class="ua-panel">
    <?php if ($message): ?><p class="ua-note" style="background:#ecfdf5;color:#065f46"><?= user_area_e($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="ua-note" style="background:#fff1f2;color:#9f1239"><?= user_area_e($error) ?></p><?php endif; ?>

    <form method="post" style="max-width:420px;display:grid;gap:14px">
        <input type="hidden" name="_csrf" value="<?= user_area_e($csrf) ?>">
        <div class="ua-field">
            <label for="current_password">Senha atual</label>
            <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
        </div>
        <div class="ua-field">
            <label for="new_password">Nova senha</label>
            <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
        </div>
        <div class="ua-field">
            <label for="confirm_password">Confirmar nova senha</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password">
        </div>
        <button type="submit" class="ua-button">Atualizar senha</button>
    </form>

    <p class="ua-note" style="margin-top:18px">
        O logout da conta é feito pelo ícone no topo (requisição POST com proteção CSRF).
        Use senha forte e exclusiva para o SiteTools.
    </p>
</section>
<?php account_footer(); ?>
