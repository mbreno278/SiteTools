<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__, 2) . '/app/Helpers/Autoloader.php';

/**
 * Permissões por funcionalidade do painel administrativo.
 * super_admin tem acesso total.
 */
function admin_permission_map(): array
{
    return \\App\\Security\\AdminPermissionPolicy::rolePermissions();
}

function admin_page_permission_map(): array
{
    return \\App\\Security\\AdminPermissionPolicy::pagePermissions();
}

function can(string $permission): bool
{
    $role = (string) (current_admin()['role'] ?? '');
    return \\App\\Security\\AdminPermissionPolicy::allows($role, $permission);
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
