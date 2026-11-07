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
}
