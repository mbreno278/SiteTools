<?php

declare(strict_types=1);

/**
 * SiteTools — conexão PDO
 *
 * Produção: exige DB_* no ambiente ou .env
 * Local (APP_ENV=local): permite fallbacks de desenvolvimento
 * Nunca usa usuário root
 */

require_once __DIR__ . '/env.php';

$appEnv = app_env();
$isLocal = app_is_local();

$required = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];
$config = [];

foreach ($required as $name) {
    $value = env($name, null);

    if ($value === null || ($name !== 'DB_PASSWORD' && trim((string) $value) === '')) {
        if (!$isLocal) {
            throw new RuntimeException(
                "Configuração ausente: defina a variável de ambiente {$name} (ou arquivo .env)."
            );
        }
        $value = match ($name) {
            'DB_HOST' => 'localhost',
            'DB_NAME' => 'webtools__sitetools',
            'DB_USER' => 'sitetools',
            'DB_PASSWORD' => 'sitetools',
            default => '',
        };
    }

    $config[$name] = (string) $value;
}

$host = trim($config['DB_HOST']);
$dbname = trim($config['DB_NAME']);
$username = trim($config['DB_USER']);
$password = $config['DB_PASSWORD'];

if ($host === '' || $dbname === '' || $username === '') {
    throw new RuntimeException('DB_HOST, DB_NAME e DB_USER são obrigatórios.');
}

$forbiddenUsers = ['root', 'mysql.sys', 'mysql.session', 'mysql.infoschema', 'debian-sys-maint'];
if (in_array(strtolower($username), $forbiddenUsers, true) && !$isLocal) {
    throw new RuntimeException(
        'A aplicação não pode conectar ao MySQL com usuário administrativo em produção.'
    );
}

// Em local permite root apenas se explicitamente configurado (WAMP comum)
if (in_array(strtolower($username), $forbiddenUsers, true) && $isLocal) {
    // aviso em log, não bloqueia WAMP padrão
    error_log('[database] Aviso: usando usuário administrativo MySQL em ambiente local.');
}

if (!preg_match('/^[a-zA-Z0-9._\-]+$/', $host)) {
    throw new RuntimeException('DB_HOST inválido.');
}
if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbname)) {
    throw new RuntimeException('DB_NAME inválido.');
}

$port = (int) env('DB_PORT', 3306);
if ($port < 1 || $port > 65535) {
    $port = 3306;
}

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ]
    );
    $GLOBALS['pdo'] = $pdo;
} catch (PDOException $e) {
    error_log('[database] conexão falhou: ' . $e->getMessage());
    if ($isLocal) {
        throw new RuntimeException(
            'Falha na conexão com o banco. Verifique DB_* no .env e se o MySQL do WAMP está ativo.',
            0,
            $e
        );
    }
    throw new RuntimeException('Serviço de banco de dados indisponível.', 0, $e);
}
