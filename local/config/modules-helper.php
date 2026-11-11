<?php
declare(strict_types=1);
if (!function_exists('modules_config')) {
    function modules_config(): array {
        static $config = null;
        if ($config !== null) return $config;
        $path = __DIR__ . '/modules.php';
        $raw = is_file($path) ? require $path : [];
        if (!is_array($raw)) $raw = [];
        $modules = is_array($raw['modules'] ?? null) ? $raw['modules'] : [];
        $beta = is_array($raw['beta'] ?? null) ? $raw['beta'] : [];
        $defaultsModules = [
            'admin' => true, 'account' => true, 'authentication' => true,
            'payments' => true, 'premium' => true, 'video_downloads' => true,
        ];
        $defaultsBeta = ['enabled' => false, 'unlimited_usage' => false];
        foreach ($defaultsModules as $k => $d) {
            $modules[$k] = array_key_exists($k, $modules) ? (bool)$modules[$k] : $d;
        }
        foreach ($defaultsBeta as $k => $d) {
            $beta[$k] = array_key_exists($k, $beta) ? (bool)$beta[$k] : $d;
        }
        return $config = ['modules' => $modules, 'beta' => $beta];
    }
}
if (!function_exists('module_enabled')) {
    function module_enabled(string $name): bool {
        return !empty(modules_config()['modules'][$name]);
    }
}
if (!function_exists('beta_enabled')) {
    function beta_enabled(): bool {
        return !empty(modules_config()['beta']['enabled']);
    }
}
if (!function_exists('beta_unlimited_usage')) {
    function beta_unlimited_usage(): bool {
        $b = modules_config()['beta'];
        return !empty($b['enabled']) && !empty($b['unlimited_usage']);
    }
}
if (!function_exists('modules_video_download_handlers')) {
    function modules_video_download_handlers(): array {
        return [
            'download-video-youtube','download-audio-youtube',
            'download-video-instagram','download-video-tiktok',
        ];
    }
}
if (!function_exists('module_allows_handler')) {
    function module_allows_handler(string $handler): bool {
        if (!module_enabled('video_downloads')
            && in_array($handler, modules_video_download_handlers(), true)) {
            return false;
        }
        return true;
    }
}
