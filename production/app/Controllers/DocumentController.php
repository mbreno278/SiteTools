<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\DocumentConverterService;
use App\Services\PdfService;

/**
 * Orquestra conversões de documentos.
 * Os endpoints HTTP continuam responsáveis por autenticação, CSRF e resposta.
 */
final class DocumentController
{
    public function __construct(
        private readonly DocumentConverterService $documents = new DocumentConverterService(),
        private readonly PdfService $pdf = new PdfService(),
    ) {
    }

    public function pdfToWord(string $temporaryPath, string $originalName, int $maxBytes = 20971520): array
    {
        return $this->pdf->toWord($temporaryPath, $originalName, $maxBytes);
    }

    public function wordToPdf(string $temporaryPath, string $originalName, int $maxBytes = 20971520): array
    {
        return $this->documents->wordToPdf($temporaryPath, $originalName, $maxBytes);
    }

    public function compressPdf(array $upload, string $quality = 'ebook', int $maxBytes = 31457280): array
    {
        return $this->pdf->compress($upload, $quality, $maxBytes);
    }

    public function splitPdf(array $upload, string $mode = 'each', string $range = '', int $maxBytes = 31457280): array
    {
        return $this->pdf->split($upload, $mode, $range, $maxBytes);
    }

    public function unlockPdf(array $upload, string $password, int $maxBytes = 31457280): array
    {
        return $this->pdf->unlock($upload, $password, $maxBytes);
    }

    public function protectPdf(array $upload, string $password, int $maxBytes = 31457280): array
    {
        return $this->pdf->protect($upload, $password, $maxBytes);
    }

    /**
     * @param array{name:array, tmp_name:array, error:array, size:array} $uploads
     */
    public function mergePdfs(array $uploads, int $maxFiles = 20, int $maxBytesPerFile = 31457280): array
    {
        return $this->pdf->merge($uploads, $maxFiles, $maxBytesPerFile);
    }
}
