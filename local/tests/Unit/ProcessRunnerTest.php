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
        $script = tempnam(sys_get_temp_dir(), 'st_process_');
        if ($script === false) {
            throw new \RuntimeException('Não foi possível criar script temporário.');
        }

        $scriptPath = $script . '.php';
        @unlink($script);

        try {
            if (file_put_contents($scriptPath, "<?php echo 'runner-ok';") === false) {
                throw new \RuntimeException('Não foi possível gravar script temporário.');
            }

            $result = (new ProcessRunner())->run([PHP_BINARY, $scriptPath], null, 10);

            $this->assertFalse($result['timed_out']);
            $this->assertSame(0, $result['exit'], 'Processo PHP falhou: ' . $result['stderr']);
            $this->assertSame('runner-ok', trim($result['stdout']));
        } finally {
            @unlink($scriptPath);
        }
    }
}
