<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Security\AdminPermissionPolicy;
use Tests\Support\SimpleTestCase;

final class AdminPermissionPolicyTest extends SimpleTestCase
{
    public function run(): void
    {
        $this->assertTrue(AdminPermissionPolicy::allows('super_admin', 'maintenance.manage'));
        $this->assertTrue(AdminPermissionPolicy::allows('admin', 'tools.edit'));
        $this->assertFalse(AdminPermissionPolicy::allows('admin', 'maintenance.manage'));
        $this->assertTrue(AdminPermissionPolicy::allows('editor', 'tools.view'));
        $this->assertFalse(AdminPermissionPolicy::allows('editor', 'users.view'));
        $this->assertTrue(AdminPermissionPolicy::allows('support', 'support.manage'));
        $this->assertFalse(AdminPermissionPolicy::allows('support', 'tools.edit'));
        $this->assertFalse(AdminPermissionPolicy::allows('unknown', 'dashboard.view'));

        $pages = AdminPermissionPolicy::pagePermissions();
        $this->assertSame('maintenance.manage', $pages['maintenance']);
        $this->assertSame('tools.view', $pages['tools']);
    }
}
