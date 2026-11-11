<?php

declare(strict_types=1);

/**
 * Helpers de módulos / feature flags.
 * Seguro para incluir várias vezes.
 */

if (!function_exists('modules_config')) {
    /**
     * @return array{modules: array<string,bool>, beta: array<string,bool>}
     */
    function modules_config(): array
    {
        static $config = null;

        if ($config !== null) {
            return $config;
        }

        $path = __DIR__ . '/modules.php';
        $raw = is_file($path) ? require $path : [];

        if (!is_array($raw)) {
            $raw = [];
        }

        $modules = is_array($raw['modules'] ?? null) ? $raw['modules'] : [];
        $beta = is_array($raw['beta'] ?? null) ? $raw['beta'] : [];

        $defaultsModules = [
            'admin' => true,
            'account' => true,
            'authentication' => true,
            'payments' => true,
            'premium' => true,
            'video_downloads' => true,
        ];

        $defaultsBeta = [
            'enabled' => false,
            'unlimited_usage' => false,
        ];

        foreach ($defaultsModules as $key => $default) {
            $modules[$key] = array_key_exists($key, $modules)
                ? (bool) $modules[$key]
                : $default;
        }

        foreach ($defaultsBeta as $key => $default) {
            $beta[$key] = array_key_exists($key, $beta)
                ? (bool) $beta[$key]
                : $default;
        }

        $config = [
            'modules' => $modules,
            'beta' => $beta,
        ];

        return $config;
    }
}

if (!function_exists('module_enabled')) {
    function module_enabled(string $name): bool
    {
        $modules = modules_config()['modules'];
        return !empty($modules[$name]);
    }
}

if (!function_exists('beta_enabled')) {
    function beta_enabled(): bool
    {
        return !empty(modules_config()['beta']['enabled']);
    }
}

if (!function_exists('beta_unlimited_usage')) {
    function beta_unlimited_usage(): bool
    {
        $beta = modules_config()['beta'];
        return !empty($beta['enabled']) && !empty($beta['unlimited_usage']);
    }
}

if (!function_exists('modules_video_download_handlers')) {
    /**
     * Handlers bloqueados quando modules.video_downloads = false
     *
     * @return list<string>
     */
    function modules_video_download_handlers(): array
    {
        return [
            'download-video-youtube',
            'download-audio-youtube',
            'download-video-instagram',
            'download-video-tiktok',
        ];
    }
}

if (!function_exists('module_allows_handler')) {
    function module_allows_handler(string $handler): bool
    {
        if (
            !module_enabled('video_downloads')
            && in_array($handler, modules_video_download_handlers(), true)
        ) {
            return false;
        }

        return true;
    }
}
