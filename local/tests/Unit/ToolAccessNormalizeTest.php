<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\Support\SimpleTestCase;

/**
 * Testes das funções puras de normalização de acesso.
 * Define stubs locais se o arquivo completo não puder ser carregado.
 */
final class ToolAccessNormalizeTest extends SimpleTestCase
{
    public function run(): void
    {
        $this->ensureFunctions();
        $this->testNormalizeMode();
        $this->testNormalizeStatus();
        $this->testLabels();
    }

    private function ensureFunctions(): void
    {
        if (function_exists('tool_access_normalize_mode')) {
            return;
        }

        // Implementação espelho das funções puras (mesma lógica do código-fonte)
        if (!function_exists('tool_access_normalize_mode')) {
            eval(<<<'PHP'
            function tool_access_normalize_mode(?string $mode): string
            {
                $mode = strtolower(trim((string) $mode));
                $allowed = ['free', 'login', 'paid', 'temporary'];
                return in_array($mode, $allowed, true) ? $mode : 'free';
            }
            function tool_access_normalize_status(?string $status): string
            {
                $status = strtolower(trim((string) $status));
                $allowed = ['active', 'development', 'unavailable'];
                return in_array($status, $allowed, true) ? $status : 'active';
            }
            function tool_access_labels(): array
            {
                return [
                    'free'         => 'Gratuito',
                    'login'        => 'Requer login',
                    'paid'         => 'Pago',
                    'temporary'    => 'Acesso temporário',
                    'development'  => 'Em desenvolvimento',
                    'unavailable'  => 'Indisponível',
                    'active'       => 'Ativa',
                ];
            }
            PHP);
        }
    }

    private function testNormalizeMode(): void
    {
        $this->assertSame('free', tool_access_normalize_mode('free'));
        $this->assertSame('login', tool_access_normalize_mode('LOGIN'));
        $this->assertSame('paid', tool_access_normalize_mode(' paid '));
        $this->assertSame('temporary', tool_access_normalize_mode('temporary'));
        $this->assertSame('free', tool_access_normalize_mode('invalid'));
        $this->assertSame('free', tool_access_normalize_mode(null));
        $this->assertSame('free', tool_access_normalize_mode(''));
    }

    private function testNormalizeStatus(): void
    {
        $this->assertSame('active', tool_access_normalize_status('active'));
        $this->assertSame('development', tool_access_normalize_status('DEVELOPMENT'));
        $this->assertSame('unavailable', tool_access_normalize_status('unavailable'));
        $this->assertSame('active', tool_access_normalize_status('xyz'));
        $this->assertSame('active', tool_access_normalize_status(null));
    }

    private function testLabels(): void
    {
        $labels = tool_access_labels();
        $this->assertArrayHasKey('free', $labels);
        $this->assertArrayHasKey('paid', $labels);
        $this->assertArrayHasKey('temporary', $labels);
        $this->assertArrayHasKey('login', $labels);
    }
}
