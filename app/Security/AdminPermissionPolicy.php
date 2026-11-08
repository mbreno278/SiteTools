<?php

declare(strict_types=1);

namespace App\\Security;

/**
 * Política central de autorização do painel administrativo.
 * Mantém a decisão de acesso independente do HTTP e da sessão.
 */
final class AdminPermissionPolicy
{
    public static function rolePermissions(): array
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
                'settings.view', 'settings.edit',
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

    public static function pagePermissions(): array
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

    public static function allows(string $role, string $permission): bool
    {
        if ($role === 'super_admin') {
            return true;
        }

        return in_array($permission, self::rolePermissions()[$role] ?? [], true);
    }
}
