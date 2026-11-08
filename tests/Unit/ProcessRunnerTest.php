<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Process\ProcessRunner;
use Tests\Support\SimpleTestCase;

final class ProcessRunnerTest extends SimpleTestCase
{
    public function run(): void
    {
        $this->testRejectsEmptyCommand();
        $this->testRunsPhpProcess();
    }

    private function testRejectsEmptyCommand(): void
    {
        $result = (new ProcessRunner())->run([]);
        $this->assertSame(-1, $result['exit']);
        $this->assertFalse($result['timed_out']);
        $this->assertStringContainsString('vazio', $result['stderr']);
    }

    private function testRunsPhpProcess(): void
    {
        $code = 'echo "runner-ok";';
        $result = (new ProcessRunner())->run([PHP_BINARY, '-r', $code], null, 10);

        $this->assertFalse($result['timed_out']);
        $this->assertSame(0, $result['exit']);
        $this->assertSame('runner-ok', trim($result['stdout']));
    }
}
