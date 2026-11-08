<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

/**
 * Permissões por funcionalidade do painel administrativo.
 * super_admin tem acesso total.
 */
function admin_permission_map(): array
{
    return [
        'admin' => [
            'dashboard.view',
            'tools.view', 'tools.edit',
            'categories.view', 'categories.edit',
            'users.view',
            'analytics.view',
            'files.view', 'files.manage',
            'logs.view',
            'support.view', 'support.manage',
            'settings.view',
            'seo.view',
            'advertisements.view',
            'security.view',
        ],
        'editor' => [
            'dashboard.view',
            'tools.view', 'tools.edit',
            'categories.view', 'categories.edit',
            'seo.view',
        ],
        'support' => [
            'dashboard.view',
            'support.view', 'support.manage',
            'users.view',
        ],
    ];
}

/**
 * Permissão mínima exigida por cada página do painel.
 * maintenance.manage fica restrita ao super_admin.
 */
function admin_page_permission_map(): array
{
    return [
        'dashboard' => 'dashboard.view',
        'tools' => 'tools.view',
        'categories' => 'categories.view',
        'users' => 'users.view',
        'analytics' => 'analytics.view',
        'files' => 'files.view',
        'logs' => 'logs.view',
        'advertisements' => 'advertisements.view',
        'support' => 'support.view',
        'seo' => 'seo.view',
        'security' => 'security.view',
        'settings' => 'settings.view',
        'maintenance' => 'maintenance.manage',
    ];
}

function can(string $permission): bool
{
    $role = (string) (current_admin()['role'] ?? '');

    if ($role === 'super_admin') {
        return true;
    }

    $map = admin_permission_map();
    return in_array($permission, $map[$role] ?? [], true);
}

function require_permission(string $permission): void
{
    require_admin();

    if (!can($permission)) {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $isApi = str_contains($uri, '/api/')
            || (isset($_SERVER['HTTP_ACCEPT']) && str_contains((string) $_SERVER['HTTP_ACCEPT'], 'application/json'));

        if ($isApi) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Permissão negada.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        exit('Você não tem permissão para acessar este recurso.');
    }
}
