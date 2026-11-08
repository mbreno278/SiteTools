<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\DocumentController;
use App\Exceptions\ValidationException;
use Tests\Support\SimpleTestCase;

final class DocumentControllerTest extends SimpleTestCase
{
    public function run(): void
    {
        $this->testPdfToWordRejectsMissingFile();
        $this->testWordToPdfRejectsMissingFile();
        $this->testMergeRejectsMalformedUpload();
        $this->testCompressPdfRejectsMissingUpload();
        $this->testSplitPdfRejectsMissingUpload();
    }

    private function testPdfToWordRejectsMissingFile(): void
    {
        $controller = new DocumentController();

        try {
            $controller->pdfToWord(sys_get_temp_dir() . '/missing-' . bin2hex(random_bytes(4)), 'arquivo.pdf');
            $this->assertTrue(false, 'PDF inexistente deveria ser rejeitado.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('inválido', $exception->getMessage());
        }
    }

    private function testWordToPdfRejectsMissingFile(): void
    {
        $controller = new DocumentController();

        try {
            $controller->wordToPdf(sys_get_temp_dir() . '/missing-' . bin2hex(random_bytes(4)), 'arquivo.docx');
            $this->assertTrue(false, 'DOCX inexistente deveria ser rejeitado.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('inválido', $exception->getMessage());
        }
    }

    private function testSplitPdfRejectsMissingUpload(): void
    {
        $controller = new DocumentController();
        $upload = [
            'name' => '',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 0,
        ];

        try {
            $controller->splitPdf($upload);
            $this->assertTrue(false, 'Upload ausente deveria ser rejeitado ao separar PDF.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('não foi enviado', $exception->getMessage());
        }
    }

    private function testCompressPdfRejectsMissingUpload(): void
    {
        $controller = new DocumentController();
        $upload = [
            'name' => '',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 0,
        ];

        try {
            $controller->compressPdf($upload);
            $this->assertTrue(false, 'Upload ausente deveria ser rejeitado.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('não foi enviado', $exception->getMessage());
        }
    }

    private function testMergeRejectsMalformedUpload(): void
    {
        $controller = new DocumentController();

        try {
            $controller->mergePdfs([]);
            $this->assertTrue(false, 'Upload malformado deveria ser rejeitado.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('Envie os arquivos', $exception->getMessage());
        }
    }
}
