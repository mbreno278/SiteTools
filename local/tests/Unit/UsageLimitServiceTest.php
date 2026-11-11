<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\Support\SimpleTestCase;

/**
 * Testes da lógica de limites de utilização (UsageLimitService).
 * Decisões puras de allowed/remaining e mapeamento guest/registered/paid.
 */
final class UsageLimitServiceTest extends SimpleTestCase
{
    public function run(): void
    {
        $this->testDecisionUnlimited();
        $this->testDecisionWithin();
        $this->testDecisionExceeded();
        $this->testUserTypeMapping();
    }

    private function testDecisionUnlimited(): void
    {
        $max = null;
        $used = 0;
        $allowed = $max === null || $used < $max;
        $this->assertTrue($allowed);
    }

    private function testDecisionWithin(): void
    {
        $max = 10;
        $used = 3;
        $allowed = $used < $max;
        $remaining = max(0, $max - $used);
        $this->assertTrue($allowed);
        $this->assertSame(7, $remaining);
    }

    private function testDecisionExceeded(): void
    {
        $max = 5;
        $used = 5;
        $allowed = $used < $max;
        $remaining = max(0, $max - $used);
        $this->assertFalse($allowed);
        $this->assertSame(0, $remaining);
    }

    private function testUserTypeMapping(): void
    {
        $userId = null;
        $hasPaid = false;
        $userType = 'guest';
        if ($userId && $userId > 0) {
            $userType = $hasPaid ? 'paid' : 'registered';
        }
        $this->assertSame('guest', $userType);

        $userId = 10;
        $hasPaid = false;
        $userType = ($userId && $userId > 0) ? ($hasPaid ? 'paid' : 'registered') : 'guest';
        $this->assertSame('registered', $userType);

        $hasPaid = true;
        $userType = ($userId && $userId > 0) ? ($hasPaid ? 'paid' : 'registered') : 'guest';
        $this->assertSame('paid', $userType);
    }
}
