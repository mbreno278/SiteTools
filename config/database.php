<?php

declare(strict_types=1);

/**
 * SiteTools — conexão PDO segura
 *
 * Produção: exige DB_HOST, DB_NAME, DB_USER, DB_PASSWORD no ambiente.
 * Desenvolvimento local: permite fallback apenas se APP_ENV=local.
 * Nunca usa o usuário root.
 */

$appEnv = strtolower(trim((string) (getenv('APP_ENV') ?: 'production')));
$isLocal = in_array($appEnv, ['local', 'development', 'dev'], true);

$required = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];
$config = [];

foreach ($required as $name) {
    $value = getenv($name);

    // Em produção, tudo obrigatório (senha pode ser string vazia só em local)
    if ($value === false || ($name !== 'DB_PASSWORD' && trim((string) $value) === '')) {
        if (!$isLocal) {
            throw new RuntimeException(
                "Configuração ausente: defina a variável de ambiente {$name}."
            );
        }
        // Fallbacks só para desenvolvimento local
        $value = match ($name) {
            'DB_HOST' => 'localhost',
            'DB_NAME' => 'webtools__sitetools',
            'DB_USER' => 'sitetools',
            'DB_PASSWORD' => 'sitetools',
            default => '',
        };
    }

    $config[$name] = is_string($value) ? $value : (string) $value;
}

$host = trim($config['DB_HOST']);
$dbname = trim($config['DB_NAME']);
$username = trim($config['DB_USER']);
$password = $config['DB_PASSWORD'];

if ($host === '' || $dbname === '' || $username === '') {
    throw new RuntimeException('DB_HOST, DB_NAME e DB_USER são obrigatórios.');
}

// Bloqueia usuários administrativos do MySQL
$forbiddenUsers = ['root', 'mysql.sys', 'mysql.session', 'mysql.infoschema', 'debian-sys-maint'];
if (in_array(strtolower($username), $forbiddenUsers, true)) {
    throw new RuntimeException(
        'A aplicação não pode conectar ao MySQL com usuário administrativo (' . $username . '). '
        . 'Use um usuário exclusivo com privilégios mínimos.'
    );
}

// Validação básica anti-injeção no DSN (valores vêm do ambiente, mas reforça)
if (!preg_match('/^[a-zA-Z0-9._\-]+$/', $host)) {
    throw new RuntimeException('DB_HOST inválido.');
}
if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbname)) {
    throw new RuntimeException('DB_NAME inválido.');
}

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ]
    );
} catch (PDOException $e) {
    // Não expõe detalhes da conexão ao usuário final
    throw new RuntimeException(
        'Falha na conexão com o banco de dados. Verifique as credenciais e se o MySQL está ativo.'
    );
}
