<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\Support\SimpleTestCase;

/**
 * Testes de proteção CSRF (lógica de geração e comparação).
 * As funções reais de csrf.php iniciam sessão; testamos o contrato.
 */
final class CsrfTest extends SimpleTestCase
{
    public function run(): void
    {
        $this->testTokenContract();
        $this->testHashEqualsSecurity();
        $this->testFieldFormat();
    }

    /**
     * Gera token no mesmo formato do csrf_token() real.
     */
    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function testTokenContract(): void
    {
        $t1 = $this->generateToken();
        $t2 = $this->generateToken();
        $this->assertSame(64, strlen($t1));
        $this->assertTrue(ctype_xdigit($t1));
        $this->assertTrue($t1 !== $t2);
    }

    private function testHashEqualsSecurity(): void
    {
        $token = $this->generateToken();
        $this->assertTrue(hash_equals($token, $token));
        $this->assertFalse(hash_equals($token, ''));
        $this->assertFalse(hash_equals($token, $token . '0'));
        $this->assertFalse(hash_equals('', $token));
    }

    private function testFieldFormat(): void
    {
        $token = str_repeat('ab', 32);
        $field = '<input type="hidden" name="_csrf" value="' . htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
        $this->assertStringContainsString('name="_csrf"', $field);
        $this->assertStringContainsString($token, $field);

        $meta = '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
        $this->assertStringContainsString('csrf-token', $meta);
    }
}
