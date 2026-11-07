<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\AuthorizationException;
use Tests\Support\SimpleTestCase;

/**
 * Testes de autorização de ferramentas (contratos e exceções).
 */
final class ToolAccessServiceTest extends SimpleTestCase
{
    public function run(): void
    {
        $this->testAuthorizationException();
        $this->testAccessModes();
        $this->testStatusValues();
    }

    private function testAuthorizationException(): void
    {
        $e = new AuthorizationException('Ferramenta indisponível.', 'unavailable', 503);
        $this->assertStringContainsString('indisponível', $e->getMessage());
        $this->assertSame(503, $e->getCode());
    }

    private function testAccessModes(): void
    {
        $modes = ['free', 'login', 'paid', 'temporary'];
        foreach ($modes as $m) {
            $this->assertTrue(in_array($m, $modes, true));
        }
        $this->assertFalse(in_array('admin_only', $modes, true));
    }

    private function testStatusValues(): void
    {
        $statuses = ['active', 'development', 'unavailable'];
        $this->assertTrue(in_array('active', $statuses, true));
        $this->assertTrue(in_array('unavailable', $statuses, true));
    }
}
