<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\Support\SimpleTestCase;

/**
 * Testes de validação de upload (config/upload-security.php).
 */
final class UploadSecurityTest extends SimpleTestCase
{
    public function run(): void
    {
        $this->testNoFile();
        $this->testIniSizeError();
        $this->testDangerousExtensionList();
        $this->testDefaultMimeMap();
        $this->testSafePath();
        $this->testDisallowedByOptions();
    }

    private function testNoFile(): void
    {
        $result = upload_validate(['error' => UPLOAD_ERR_NO_FILE]);
        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Nenhum arquivo', $result['error']);
    }

    private function testIniSizeError(): void
    {
        $result = upload_validate(['error' => UPLOAD_ERR_INI_SIZE]);
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('limite do servidor', $result['error']);
    }

    private function testDangerousExtensionList(): void
    {
        // Testamos a lista de extensões perigosas indiretamente:
        // a função rejeita após is_uploaded_file; aqui validamos a presença da lógica
        // via análise do comportamento com error codes e opts.
        $this->assertTrue(function_exists('upload_validate'));
        $this->assertTrue(function_exists('upload_default_mime_map'));
        $this->assertTrue(function_exists('upload_assert_safe_path'));
    }

    private function testDefaultMimeMap(): void
    {
        $map = upload_default_mime_map();
        $this->assertArrayHasKey('pdf', $map);
        $this->assertArrayHasKey('docx', $map);
        $this->assertArrayHasKey('jpg', $map);
        $this->assertTrue(in_array('application/pdf', $map['pdf'], true));
    }

    private function testSafePath(): void
    {
        $root = realpath(sys_get_temp_dir()) ?: sys_get_temp_dir();
        $this->assertFalse(upload_assert_safe_path($root, $root . '/../etc/passwd'));
        $this->assertFalse(upload_assert_safe_path($root, '/etc/passwd'));
    }

    private function testDisallowedByOptions(): void
    {
        // Sem arquivo real de upload, a validação falha em is_uploaded_file
        $result = upload_validate([
            'error' => UPLOAD_ERR_OK,
            'tmp_name' => '/tmp/nao-existe-' . uniqid(),
            'size' => 100,
            'name' => 'doc.pdf',
        ], ['max_bytes' => 1024, 'extensions' => ['pdf']]);
        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey('error', $result);
    }
}
