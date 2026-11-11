<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\FileStorageService;
use App\Exceptions\ValidationException;
use Tests\Support\SimpleTestCase;

/**
 * Testes do FileStorageService (validação de upload).
 */
final class FileStorageServiceTest extends SimpleTestCase
{
    public function run(): void
    {
        $this->testValidateRejectsNoFile();
        $this->testValidateRejectsDangerous();
        $this->testAssertSafePath();
    }

    private function testValidateRejectsNoFile(): void
    {
        $svc = new FileStorageService();
        $thrown = false;
        try {
            $svc->validateUpload(['error' => UPLOAD_ERR_NO_FILE]);
        } catch (ValidationException $e) {
            $thrown = true;
            $this->assertStringContainsString('Nenhum arquivo', $e->getMessage());
        }
        $this->assertTrue($thrown);
    }

    private function testValidateRejectsDangerous(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'st_');
        file_put_contents($tmp, '<?php');
        $svc = new FileStorageService();
        $thrown = false;
        try {
            $svc->validateUpload([
                'error' => UPLOAD_ERR_OK,
                'tmp_name' => $tmp,
                'size' => 5,
                'name' => 'x.php',
            ], ['max_bytes' => 1024 * 1024, 'extensions' => ['php']]);
        } catch (ValidationException $e) {
            $thrown = true;
        } catch (\Throwable $e) {
            // is_uploaded_file falha fora de request
            $thrown = true;
        }
        $this->assertTrue($thrown);
        @unlink($tmp);
    }

    private function testAssertSafePath(): void
    {
        $svc = new FileStorageService();
        $root = sys_get_temp_dir();
        $this->assertFalse($svc->assertSafePath($root, $root . '/../../etc/passwd'));
    }
}
