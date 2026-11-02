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

// E-mail já existe?
$check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$check->execute(['email' => $email]);
if ($check->fetch()) {
    auth_json(['error' => 'Este e-mail já está cadastrado.'], 409);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    'INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :hash)'
);

try {
    $stmt->execute([
        'name'  => $name,
        'email' => $email,
        'hash'  => $hash,
    ]);
} catch (PDOException $e) {
    auth_json(['error' => 'Não foi possível criar a conta. Tente novamente.'], 500);
}

$id = (int) $pdo->lastInsertId();
auth_login($id, $name, $email);

auth_json([
    'ok'   => true,
    'user' => [
        'id'    => $id,
        'name'  => $name,
        'email' => $email,
    ],
]);
