<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;

/**
 * Operações específicas de PDF.
 *
 * A conversão propriamente dita permanece em DocumentConverterService;
 * esta classe concentra a validação de entrada e oferece uma API estável.
 */
final class PdfService
{
    public function __construct(
        private readonly DocumentConverterService $converter = new DocumentConverterService(),
    ) {
    }

    public function validate(string $path, int $maxBytes = 20971520): void
    {
        if ($path === '' || !is_file($path) || !is_readable($path)) {
            throw new ValidationException('Arquivo PDF inválido.');
        }

        $size = filesize($path);
        if ($size === false || $size < 5 || $size > $maxBytes) {
            throw new ValidationException('O PDF está vazio ou excede o limite permitido.');
        }

        $handle = @fopen($path, 'rb');
        $signature = $handle ? (fread($handle, 5) ?: '') : '';
        if (is_resource($handle)) {
            fclose($handle);
        }

        if (!str_starts_with($signature, '%PDF-')) {
            throw new ValidationException('O arquivo enviado não possui uma assinatura PDF válida.');
        }
    }

    public function toWord(string $temporaryPath, string $originalName, int $maxBytes = 20971520): array
    {
        $this->validate($temporaryPath, $maxBytes);
        return $this->converter->pdfToWord($temporaryPath, $originalName, $maxBytes);
    }
}
