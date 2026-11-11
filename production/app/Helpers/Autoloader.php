<?php

declare(strict_types=1);

/**
 * Autoload simples PSR-4 para o namespace App\
 * Sem Composer obrigatório (compatível com WAMP).
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = dirname(__DIR__) . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require_once $path;
    }
});
