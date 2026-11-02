<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    auth_json(['error' => 'Método não permitido.'], 405);
}

csrf_verify();

$token = trim((string) ($_POST['token'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$password2 = (string) ($_POST['password_confirm'] ?? '');

if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    auth_json(['error' => 'Token inválido.'], 422);
}

if (strlen($password) < 8) {
    auth_json(['error' => 'A senha deve ter pelo menos 8 caracteres.'], 422);
}

if ($password !== $password2) {
    auth_json(['error' => 'As senhas não coincidem.'], 422);
}

$pdo = auth_pdo();
$stmt = $pdo->prepare(
    'SELECT id FROM users
     WHERE reset_token = :token
       AND reset_token_expires_at IS NOT NULL
       AND reset_token_expires_at >= NOW()
     LIMIT 1'
);
$stmt->execute(['token' => $token]);
$user = $stmt->fetch();

if (!$user) {
    auth_json(['error' => 'Link inválido ou expirado. Solicite uma nova recuperação.'], 400);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$pdo->prepare(
    'UPDATE users
     SET password_hash = :hash,
         reset_token = NULL,
         reset_token_expires_at = NULL
     WHERE id = :id'
)->execute([
    'hash' => $hash,
    'id'   => (int) $user['id'],
]);

// Invalida tentativas antigas
try {
    $pdo->prepare('DELETE FROM login_attempts WHERE email = (SELECT email FROM users WHERE id = :id)')
        ->execute(['id' => (int) $user['id']]);
} catch (Throwable) {
}

auth_json(['ok' => true, 'message' => 'Senha redefinida com sucesso. Você já pode entrar.']);
