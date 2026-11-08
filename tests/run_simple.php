<?php

declare(strict_types=1);

/**
 * Runner simples de testes (não requer PHPUnit/extensões XML).
 *
 * Uso no terminal:
 *   php tests/run_simple.php
 *
 * No Windows/WAMP, use o caminho completo do php.exe se "php" não estiver no PATH.
 * Pelo navegador, testes que iniciam subprocessos são ignorados por segurança/
 * compatibilidade com o SAPI Apache; execute-os pelo CLI.
 */

require_once __DIR__ . '/bootstrap.php';

if (function_exists('ob_implicit_flush')) {
    ob_implicit_flush(true);
}
@ini_set('output_buffering', '0');

if (function_exists('set_time_limit')) {
    @set_time_limit(120);
}

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
$skipped = 0;
$totalAssertions = 0;

echo "SiteTools — Testes Automatizados (runner simples)\n";
echo str_repeat('=', 50) . "\n\n";
echo "Ambiente: " . PHP_SAPI . "\n\n";

foreach ($tests as $class) {
    // ProcessRunnerTest inicia outro executável PHP. Sob Apache, PHP_BINARY
    // pode apontar para o binário do servidor; esse teste deve rodar no CLI.
    if ($class === \Tests\Unit\ProcessRunnerTest::class && PHP_SAPI !== 'cli') {
        echo "[SKIP] {$class} (teste de subprocesso requer PHP CLI; execute pelo terminal)\n";
        $skipped++;
        flush();
        continue;
    }

    echo "[RUN ] {$class} ...\n";
    flush();

    if (!class_exists($class)) {
        echo "[SKIP] {$class} (classe não encontrada; verifique namespace e autoloader)\n";
        $skipped++;
        flush();
        continue;
    }

    try {
        /** @var \Tests\Support\SimpleTestCase $instance */
        $instance = new $class();
        $instance->run();
        $result = $instance->getResult();

        $assertions = (int) ($result['assertions'] ?? 0);
        $totalAssertions += $assertions;

        if (!empty($result['passed'])) {
            echo "[PASS] {$class} ({$assertions} assertions)\n";
            $passed++;
        } else {
            echo "[FAIL] {$class}\n";
            foreach (($result['failures'] ?? []) as $failure) {
                echo "       - {$failure}\n";
            }
            $failed++;
        }
    } catch (Throwable $e) {
        echo "[ERROR] {$class}: " . $e->getMessage() . "\n";
        echo "         at " . $e->getFile() . ':' . $e->getLine() . "\n";
        $failed++;
    }

    flush();
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "Resultado: {$passed} passed, {$failed} failed, {$skipped} skipped, {$totalAssertions} assertions\n";
flush();

exit($failed > 0 ? 1 : 0);
