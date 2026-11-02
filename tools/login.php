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

$pdo = auth_pdo();

$stmt = $pdo->prepare('SELECT id, name, email, password_hash FROM users WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, (string) $user['password_hash'])) {
    auth_json(['error' => 'E-mail ou senha incorretos.'], 401);
}

auth_login((int) $user['id'], (string) $user['name'], (string) $user['email']);

auth_json([
    'ok'   => true,
    'user' => [
        'id'    => (int) $user['id'],
        'name'  => (string) $user['name'],
        'email' => (string) $user['email'],
    ],
]);
