<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

/**
 * Base inicial de permissões por função.
 * Em uma próxima etapa, esta checagem poderá consultar roles/permissions no banco.
 */
function can(string $permission): bool
{
    $role = current_admin()['role'] ?? '';

    if ($role === 'super_admin') {
        return true;
    }

    $rolePermissions = [
        'admin' => ['dashboard.view', 'tools.view', 'categories.view', 'analytics.view', 'files.view', 'logs.view'],
        'editor' => ['dashboard.view', 'tools.view', 'categories.view'],
        'support' => ['dashboard.view', 'support.view'],
    ];

    return in_array($permission, $rolePermissions[$role] ?? [], true);
}

function require_permission(string $permission): void
{
    require_admin();

    if (!can($permission)) {
        http_response_code(403);
        exit('Você não tem permissão para acessar este recurso.');
    }
}
