<?php

declare(strict_types=1);

/**
 * Bootstrap para testes automatizados do SiteTools.
 *
 * Carrega o autoloader da App e funções legadas necessárias sem iniciar
 * sessão HTTP real ou conexão de banco (mocks/stubs nos testes).
 */

$root = dirname(__DIR__);

// Autoloader PSR-4 mínimo (sem Composer obrigatório)
spl_autoload_register(static function (string $class) use ($root): void {
    $prefixes = [
        'App\\'   => $root . '/app/',
        'Tests\\' => $root . '/tests/',
    ];
    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }
        $relative = str_replace('\\', '/', substr($class, $len));
        $file = $baseDir . $relative . '.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

// Funções legadas testáveis (sem side-effects de sessão/DB no require)
require_once $root . '/config/upload-security.php';

// csrf.php inicia sessão — só carrega sob demanda nos testes de CSRF
// auth.php e tool-access.php dependem de PDO — mockados nos testes

if (!defined('PHPUNIT_RUNNING')) {
    define('PHPUNIT_RUNNING', true);
}

// Ambiente de teste
putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = 'testing';
