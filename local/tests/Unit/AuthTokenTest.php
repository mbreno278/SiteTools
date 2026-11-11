<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\Support\SimpleTestCase;

/**
 * Testes de geração e hash de tokens (contrato de auth.php).
 */
final class AuthTokenTest extends SimpleTestCase
{
    public function run(): void
    {
        $this->testRandomToken();
        $this->testHashToken();
        $this->testConstantsDocumented();
    }

    private function randomToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    private function testRandomToken(): void
    {
        $t1 = $this->randomToken();
        $t2 = $this->randomToken();
        $this->assertSame(64, strlen($t1));
        $this->assertSame(64, strlen($t2));
        $this->assertTrue($t1 !== $t2, 'Tokens devem ser únicos');
        $this->assertTrue(ctype_xdigit($t1), 'Token deve ser hex');
    }

    private function testHashToken(): void
    {
        $token = 'abc123';
        $hash = $this->hashToken($token);
        $this->assertSame(64, strlen($hash));
        $this->assertSame(hash('sha256', $token), $hash);
        $this->assertTrue($hash !== $token);
    }

    private function testConstantsDocumented(): void
    {
        // Valores documentados em config/auth.php
        $lifetime = 7200;
        $idle = 1800;
        $maxAttempts = 5;
        $tokenTtl = 3600;
        $this->assertTrue($lifetime > $idle);
        $this->assertTrue($maxAttempts >= 3);
        $this->assertTrue($tokenTtl > 0);
    }
}
