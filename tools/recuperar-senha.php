<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    auth_json(['error' => 'Método não permitido.'], 405);
}

csrf_verify();

$email = strtolower(trim((string) ($_POST['email'] ?? '')));

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    auth_json(['error' => 'Informe um e-mail válido.'], 422);
}

// Sempre responde sucesso genérico (não revela se o e-mail existe)
$generic = [
    'ok' => true,
    'message' => 'Se o e-mail existir, enviamos um link para redefinir a senha.',
];

$pdo = auth_pdo();
$stmt = $pdo->prepare('SELECT id, name FROM users WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if ($user) {
    $token = auth_random_token();
    $expires = date('Y-m-d H:i:s', time() + AUTH_TOKEN_TTL);

    $pdo->prepare(
        'UPDATE users SET reset_token = :token, reset_token_expires_at = :exp WHERE id = :id'
    )->execute([
        'token' => $token,
        'exp'   => $expires,
        'id'    => (int) $user['id'],
    ]);

    $link = auth_app_base_url() . '/redefinir-senha.php?token=' . urlencode($token);
    auth_send_mail(
        $email,
        'Redefinir senha — SiteTools',
        "Olá, {$user['name']}!\n\nPara redefinir sua senha, acesse:\n{$link}\n\nO link expira em 1 hora. Se você não pediu isso, ignore este e-mail.\n"
    );
}

auth_json($generic);
