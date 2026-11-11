<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Base mínima para testes sem depender de PHPUnit completo
 * (útil em ambientes sem extensões dom/mbstring/xmlwriter).
 *
 * Quando PHPUnit estiver disponível, as classes em tests/Unit
 * estendem PHPUnit\Framework\TestCase normalmente.
 */
abstract class SimpleTestCase
{
    private int $assertions = 0;
    private array $failures = [];

    abstract public function run(): void;

    protected function assertTrue(bool $condition, string $message = ''): void
    {
        $this->assertions++;
        if (!$condition) {
            $this->failures[] = $message ?: 'Failed asserting that value is true.';
        }
    }

    protected function assertFalse(bool $condition, string $message = ''): void
    {
        $this->assertTrue(!$condition, $message ?: 'Failed asserting that value is false.');
    }

    protected function assertSame(mixed $expected, mixed $actual, string $message = ''): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            $this->failures[] = $message ?: sprintf(
                'Failed asserting that %s is identical to %s.',
                var_export($actual, true),
                var_export($expected, true)
            );
        }
    }

    protected function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        $this->assertions++;
        if ($expected != $actual) {
            $this->failures[] = $message ?: 'Failed asserting equality.';
        }
    }

    protected function assertNotEmpty(mixed $value, string $message = ''): void
    {
        $this->assertTrue(!empty($value), $message ?: 'Failed asserting that value is not empty.');
    }

    protected function assertArrayHasKey(string|int $key, array $array, string $message = ''): void
    {
        $this->assertTrue(array_key_exists($key, $array), $message ?: "Key {$key} not found.");
    }

    protected function assertStringContainsString(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            $message ?: "Failed asserting that '{$haystack}' contains '{$needle}'."
        );
    }

    public function getResult(): array
    {
        return [
            'class' => static::class,
            'assertions' => $this->assertions,
            'failures' => $this->failures,
            'passed' => count($this->failures) === 0,
        ];
    }
}
