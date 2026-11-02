<?php

declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';

$token = trim((string) ($_GET['token'] ?? ''));
$message = 'Link inválido ou expirado.';
$ok = false;

if ($token !== '' && preg_match('/^[a-f0-9]{64}$/', $token)) {
    try {
        $pdo = auth_pdo();
        $stmt = $pdo->prepare(
            'SELECT id FROM users
             WHERE verify_token = :token
               AND verify_token_expires_at IS NOT NULL
               AND verify_token_expires_at >= NOW()
             LIMIT 1'
        );
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();

        if ($user) {
            $pdo->prepare(
                'UPDATE users
                 SET email_verified_at = NOW(),
                     verify_token = NULL,
                     verify_token_expires_at = NULL
                 WHERE id = :id'
            )->execute(['id' => (int) $user['id']]);

            if (!empty($_SESSION['user_id']) && (int) $_SESSION['user_id'] === (int) $user['id']) {
                $_SESSION['user_email_verified'] = true;
            }

            $ok = true;
            $message = 'E-mail confirmado com sucesso! Você já pode usar o SiteTools.';
        }
    } catch (Throwable) {
        $message = 'Não foi possível confirmar o e-mail. Tente novamente.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar e-mail | SiteTools</title>
    <link rel="stylesheet" href="./assets/css/variables.css">
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/auth.css">
</head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;padding:24px;">
    <div class="auth-modal__dialog" style="position:relative;">
        <h1 class="auth-modal__title"><?= $ok ? 'E-mail confirmado' : 'Verificação' ?></h1>
        <p class="auth-modal__subtitle"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <a class="button button--primary auth-modal__submit" href="./index.php" style="display:flex;text-decoration:none;">
            Ir para o início
        </a>
    </div>
</body>
</html>
