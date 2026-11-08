<?php

declare(strict_types=1);

/**
 * Runner simples de testes (não requer PHPUnit/extensões XML).
 * Uso: php tests/run_simple.php
 */

require_once __DIR__ . '/bootstrap.php';

$tests = [
    \Tests\Unit\UploadSecurityTest::class,
    \Tests\Unit\CsrfTest::class,
    \Tests\Unit\AuthTokenTest::class,
    \Tests\Unit\ToolAccessNormalizeTest::class,
    \Tests\Unit\UsageLimitServiceTest::class,
    \Tests\Unit\ToolAccessServiceTest::class,
    \Tests\Unit\FileStorageServiceTest::class,
    \Tests\Unit\ImageControllerTest::class,
    \Tests\Unit\DocumentControllerTest::class,
    \Tests\Unit\PdfServiceTest::class,
    \Tests\Unit\TempWorkspaceTest::class,
    \Tests\Unit\ProcessRunnerTest::class,
];

$passed = 0;
$failed = 0;
$totalAssertions = 0;

echo "SiteTools — Testes Automatizados (runner simples)\n";
echo str_repeat('=', 50) . "\n\n";

foreach ($tests as $class) {
    if (!class_exists($class)) {
        echo "[SKIP] {$class} (classe não encontrada)\n";
        continue;
    }
    try {
        /** @var \Tests\Support\SimpleTestCase $instance */
        $instance = new $class();
        $instance->run();
        $result = $instance->getResult();
        $totalAssertions += $result['assertions'];
        if ($result['passed']) {
            echo "[PASS] {$class} ({$result['assertions']} assertions)\n";
            $passed++;
        } else {
            echo "[FAIL] {$class}\n";
            foreach ($result['failures'] as $f) {
                echo "       - {$f}\n";
            }
            $failed++;
        }
    } catch (Throwable $e) {
        echo "[ERROR] {$class}: " . $e->getMessage() . "\n";
        echo "         at " . $e->getFile() . ':' . $e->getLine() . "\n";
        $failed++;
    }
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "Resultado: {$passed} passed, {$failed} failed, {$totalAssertions} assertions\n";
exit($failed > 0 ? 1 : 0);
