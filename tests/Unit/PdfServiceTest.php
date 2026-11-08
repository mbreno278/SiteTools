<?php

declare(strict_types=1);

namespace Tests\\Unit;

use App\\Exceptions\\ValidationException;
use App\\Services\\PdfService;
use Tests\\Support\\SimpleTestCase;

final class PdfServiceTest extends SimpleTestCase
{
    public function run(): void
    {
        $this->testRejectsMissingPdf();
        $this->testRejectsInvalidSignature();
        $this->testAcceptsPdfHeader();
        $this->testMergeRequiresAtLeastTwoFiles();
    }

    private function testRejectsMissingPdf(): void
    {
        $service = new PdfService();

        try {
            $service->validate(sys_get_temp_dir() . '/missing-' . bin2hex(random_bytes(4)));
            $this->assertTrue(false, 'Arquivo inexistente deveria ser rejeitado.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
    }

    private function testRejectsInvalidSignature(): void
    {
        $path = $this->temporaryFile('NOTPDF');
        try {
            (new PdfService())->validate($path);
            $this->assertTrue(false, 'Assinatura inválida deveria ser rejeitada.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        } finally {
            @unlink($path);
        }
    }

    private function testAcceptsPdfHeader(): void
    {
        $path = $this->temporaryFile("%PDF-1.7\n");
        try {
            (new PdfService())->validate($path);
            $this->assertTrue(true);
        } finally {
            @unlink($path);
        }
    }

    private function testMergeRequiresAtLeastTwoFiles(): void
    {
        $service = new PdfService();
        $uploads = [
            'name' => ['one.pdf'],
            'tmp_name' => ['/tmp/fake-upload.pdf'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [100],
        ];

        try {
            $service->merge($uploads);
            $this->assertTrue(false, 'A união deve exigir pelo menos dois arquivos.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('pelo menos 2', $exception->getMessage());
        }
    }

    private function temporaryFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'st_pdf_test_');
        if ($path === false || file_put_contents($path, $content) === false) {
            throw new \\RuntimeException('Não foi possível criar arquivo temporário de teste.');
        }
        return $path;
    }
}
