<?php

declare(strict_types=1);

/**
 * Carrega variáveis de ambiente a partir de arquivo .env (se existir)
 * e expõe helpers app_env / env().
 *
 * Não sobrescreve variáveis já definidas no sistema (Apache/Windows/Linux).
 */

if (!function_exists('sitetools_load_env')) {
    function sitetools_load_env(?string $path = null): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $path = $path ?: dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if ($name === '' || !preg_match('/^[A-Z][A-Z0-9_]*$/', $name)) {
                continue;
            }

            // Remove aspas simples/duplas envolventes
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"'))
                || (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            // Não sobrescreve ambiente real do servidor
            $existing = getenv($name);
            if ($existing !== false && $existing !== '') {
                continue;
            }
            if (array_key_exists($name, $_ENV) && $_ENV[$name] !== '') {
                continue;
            }

            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

if (!function_exists('env')) {
    /**
     * @param mixed $default
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        $lower = strtolower((string) $value);
        return match ($lower) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            default => $value,
        };
    }
}

if (!function_exists('app_env')) {
    function app_env(): string
    {
        return strtolower(trim((string) env('APP_ENV', 'production')));
    }
}

if (!function_exists('app_is_local')) {
    function app_is_local(): bool
    {
        return in_array(app_env(), ['local', 'development', 'dev'], true);
    }
}

sitetools_load_env();
