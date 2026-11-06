<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    auth_json(['error' => 'Método não permitido.'], 405);
}

csrf_verify();

$name = trim((string) ($_POST['name'] ?? ''));
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');
$password2 = (string) ($_POST['password_confirm'] ?? '');

if ($name === '' || mb_strlen($name) < 2) {
    auth_json(['error' => 'Informe seu nome (mínimo 2 caracteres).'], 422);
}
if (mb_strlen($name) > 120) {
    auth_json(['error' => 'Nome muito longo.'], 422);
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    auth_json(['error' => 'Informe um e-mail válido.'], 422);
}
if (strlen($password) < 8) {
    auth_json(['error' => 'A senha deve ter pelo menos 8 caracteres.'], 422);
}
if ($password !== $password2) {
    auth_json(['error' => 'As senhas não coincidem.'], 422);
}

$pdo = auth_pdo();

$check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$check->execute(['email' => $email]);
if ($check->fetch()) {
    auth_json(['error' => 'Este e-mail já está cadastrado.'], 409);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$token = auth_random_token();
$expires = date('Y-m-d H:i:s', time() + AUTH_TOKEN_TTL);

$stmt = $pdo->prepare(
    'INSERT INTO users (name, email, password_hash, verify_token, verify_token_expires_at)
     VALUES (:name, :email, :hash, :token, :exp)'
);

try {
    $stmt->execute([
        'name'  => $name,
        'email' => $email,
        'hash'  => $hash,
        'token' => auth_hash_token($token),
        'exp'   => $expires,
    ]);
} catch (PDOException) {
    auth_json(['error' => 'Não foi possível criar a conta. Tente novamente.'], 500);
}

$id = (int) $pdo->lastInsertId();

$link = auth_app_base_url() . '/verificar-email.php?token=' . urlencode($token);
auth_send_mail(
    $email,
    'Confirme seu e-mail — SiteTools',
    "Olá, {$name}!\n\nClique no link para confirmar seu e-mail:\n{$link}\n\nO link expira em 1 hora.\n"
);

auth_login($id, $name, $email, false);

auth_json([
    'ok'      => true,
    'message' => 'Conta criada. Verifique seu e-mail para confirmar o cadastro.',
    'user'    => [
        'id'             => $id,
        'name'           => $name,
        'email'          => $email,
        'email_verified' => false,
    ],
]);
