<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    auth_json(['error' => 'Método não permitido.'], 405);
}

csrf_verify();

$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    auth_json(['error' => 'Informe um e-mail válido.'], 422);
}

if ($password === '') {
    auth_json(['error' => 'Informe a senha.'], 422);
}

if (auth_is_locked($email)) {
    auth_json([
        'error' => 'Muitas tentativas. Aguarde 15 minutos e tente novamente.',
    ], 429);
}

$pdo = auth_pdo();

$stmt = $pdo->prepare(
    'SELECT id, name, email, password_hash, email_verified_at
     FROM users WHERE email = :email LIMIT 1'
);
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, (string) $user['password_hash'])) {
    auth_record_attempt($email);
    auth_json(['error' => 'E-mail ou senha incorretos.'], 401);
}

$verified = !empty($user['email_verified_at']);

// Opcional: exigir verificação antes de logar
// if (!$verified) {
//     auth_json(['error' => 'Confirme seu e-mail antes de entrar.'], 403);
// }

auth_clear_attempts($email);
auth_login(
    (int) $user['id'],
    (string) $user['name'],
    (string) $user['email'],
    $verified
);

auth_json([
    'ok'   => true,
    'user' => [
        'id'             => (int) $user['id'],
        'name'           => (string) $user['name'],
        'email'          => (string) $user['email'],
        'email_verified' => $verified,
    ],
]);
