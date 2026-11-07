<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AppException;
use App\Exceptions\ValidationException;
use App\Services\Process\ProcessRunner;
use App\Services\Process\TempWorkspace;

/**
 * Conversões de documentos via LibreOffice headless.
 */
final class DocumentConverterService
{
    public function __construct(
        private readonly ProcessRunner $runner = new ProcessRunner(),
        private readonly int $timeoutSeconds = 180,
    ) {
    }

    public function findLibreOffice(): ?string
    {
        $configFile = dirname(__DIR__, 2) . '/config/converters.php';
        if (is_file($configFile)) {
            $config = require $configFile;
            if (is_array($config) && !empty($config['libreoffice']) && is_file((string) $config['libreoffice'])) {
                return (string) $config['libreoffice'];
            }
        }

        $candidates = [
            'C:\\Program Files\\LibreOffice\\program\\soffice.com',
            'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.com',
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files\\LibreOffice 24\\program\\soffice.com',
            'C:\\Program Files\\LibreOffice 25\\program\\soffice.com',
            '/usr/bin/soffice',
            '/usr/bin/libreoffice',
            '/usr/lib/libreoffice/program/soffice',
            '/snap/bin/libreoffice',
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Converte Word (doc/docx) → PDF.
     *
     * @return array{path:string,mime:string,bytes:int,workspace:TempWorkspace}
     *         Caller deve usar o arquivo e depois deixar o workspace destruir/cleanup.
     */
    public function wordToPdf(string $uploadTmp, string $originalName, int $maxBytes = 20971520): array
    {
        $this->assertUpload($uploadTmp, $originalName, $maxBytes, ['doc', 'docx']);

        $soffice = $this->findLibreOffice();
        if ($soffice === null) {
            throw new AppException('LibreOffice não encontrado no servidor.', 503, 'converter_missing');
        }

        $workspace = new TempWorkspace();
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) ?: 'docx';
        $input = $workspace->randomFile($ext);

        if (!@move_uploaded_file($uploadTmp, $input) && !@copy($uploadTmp, $input)) {
            $workspace->cleanup();
            throw new AppException('Falha ao receber o arquivo.', 500, 'upload_store_failed');
        }

        $outDir = $workspace->path();
        $cmd = [
            $soffice,
            '--headless',
            '--nologo',
            '--nofirststartwizard',
            '--norestore',
            '--convert-to', 'pdf',
            '--outdir', $outDir,
            $input,
        ];

        $result = $this->runner->run($cmd, $outDir, $this->timeoutSeconds);

        if ($result['timed_out']) {
            $workspace->cleanup();
            throw new AppException('A conversão excedeu o tempo limite.', 504, 'timeout');
        }

        // LibreOffice gera PDF com mesmo basename
        $base = pathinfo($input, PATHINFO_FILENAME);
        $pdfPath = $outDir . DIRECTORY_SEPARATOR . $base . '.pdf';
        if (!is_file($pdfPath)) {
            // procura qualquer pdf no workspace
            $found = glob($outDir . DIRECTORY_SEPARATOR . '*.pdf') ?: [];
            $pdfPath = $found[0] ?? '';
        }

        if ($pdfPath === '' || !is_file($pdfPath) || filesize($pdfPath) < 5) {
            $workspace->cleanup();
            $detail = trim($result['stderr'] ?: $result['stdout']);
            throw new AppException(
                'Não foi possível converter o documento.' . ($detail !== '' ? ' ' . mb_substr($detail, 0, 180) : ''),
                500,
                'conversion_failed'
            );
        }

        return [
            'path' => $pdfPath,
            'mime' => 'application/pdf',
            'bytes' => (int) filesize($pdfPath),
            'workspace' => $workspace,
        ];
    }

    /**
     * PDF → DOCX via LibreOffice.
     * Mantém o filtro de importação PDF do Writer e a segunda estratégia
     * de exportação usada pelo endpoint legado.
     *
     * @return array{path:string,mime:string,bytes:int,workspace:TempWorkspace}
     */
    public function pdfToWord(string $uploadTmp, string $originalName, int $maxBytes = 20971520): array
    {
        $this->assertUpload($uploadTmp, $originalName, $maxBytes, ['pdf']);

        $soffice = $this->findLibreOffice();
        if ($soffice === null) {
            throw new AppException('LibreOffice não encontrado no servidor.', 503, 'converter_missing');
        }

        $workspace = new TempWorkspace();
        $input = $workspace->randomFile('pdf');

        if (!@move_uploaded_file($uploadTmp, $input) && !@copy($uploadTmp, $input)) {
            $workspace->cleanup();
            throw new AppException('Falha ao receber o arquivo.', 500, 'upload_store_failed');
        }

        $outDir = $workspace->path();
        $baseCommand = [
            $soffice,
            '--headless',
            '--nologo',
            '--nodefault',
            '--nolockcheck',
            '--norestore',
            '--nofirststartwizard',
            '-env:UserInstallation=' . $this->fileUri($workspace->join('lo-profile')),
            '--infilter=writer_pdf_import',
        ];

        $commands = [
            array_merge($baseCommand, [
                '--convert-to', 'docx',
                '--outdir', $outDir,
                $input,
            ]),
            array_merge($baseCommand, [
                '--convert-to', 'docx:Office Open XML Text',
                '--outdir', $outDir,
                $input,
            ]),
        ];

        $docx = $outDir . DIRECTORY_SEPARATOR . $base . '.docx';
        $lastResult = ['exit' => -1, 'stdout' => '', 'stderr' => '', 'timed_out' => false];
        $validationReason = null;
        $valid = false;

        foreach ($commands as $command) {
            if (is_file($docx)) {
                @unlink($docx);
            }

            $lastResult = $this->runner->run($command, $outDir, $this->timeoutSeconds);

            if ($lastResult['timed_out']) {
                $workspace->cleanup();
                throw new AppException('A conversão excedeu o tempo limite.', 504, 'timeout');
            }

            if (is_file($docx) && filesize($docx) > 100 && $this->validateDocx($docx, $validationReason)) {
                $valid = true;
                break;
            }
        }

        if (!$valid) {
            $detail = trim(($lastResult['stderr'] ?? '') . ' ' . ($lastResult['stdout'] ?? ''));
            error_log(
                '[pdf-to-word] DOCX inválido: '
                . ($validationReason ?? 'arquivo DOCX não encontrado.')
                . ' Processo: '
                . $detail
            );
            $workspace->cleanup();

            if ($validationReason !== null) {
                throw new ValidationException($validationReason);
            }

            throw new AppException(
                'Não foi possível converter este PDF. Verifique se ele está protegido por senha e se o LibreOffice consegue abri-lo.',
                500,
                'conversion_failed'
            );
        }

        return [
            'path' => $docx,
            'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'bytes' => (int) filesize($docx),
            'workspace' => $workspace,
        ];
    }

    private function fileUri(string $path): string
    {
        $normalized = str_replace(chr(92), '/', $path);
        return 'file:///' . ltrim($normalized, '/');
    }

    /**
     * Confere a estrutura interna do DOCX e a presença de texto ou conteúdo visual.
     */
    private function validateDocx(string $path, ?string &$reason = null): bool
    {
        $reason = null;

        if (!class_exists(\\ZipArchive::class)) {
            $reason = 'A extensão PHP ZIP não está habilitada no servidor.';
            return false;
        }

        $zip = new \\ZipArchive();
        if ($zip->open($path) !== true) {
            $reason = 'O arquivo gerado não é um pacote DOCX válido.';
            return false;
        }

        foreach (['[Content_Types].xml', '_rels/.rels', 'word/document.xml'] as $entry) {
            if ($zip->locateName($entry) === false) {
                $zip->close();
                $reason = 'O documento Word está incompleto.';
                return false;
            }
        }

        $xml = $zip->getFromName('word/document.xml');
        if (!is_string($xml) || $xml === '') {
            $zip->close();
            $reason = 'O documento Word não possui conteúdo XML.';
            return false;
        }

        $hasText = false;
        $hasVisual = false;

        if (class_exists(\\DOMDocument::class)) {
            $previous = libxml_use_internal_errors(true);
            $document = new \\DOMDocument();
            $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS);
            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            if (!$loaded) {
                $zip->close();
                $reason = 'O XML principal do documento Word está inválido.';
                return false;
            }

            $namespace = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
            foreach ($document->getElementsByTagNameNS($namespace, 't') as $node) {
                if (trim($node->textContent) !== '') {
                    $hasText = true;
                    break;
                }
            }

            $xpath = new \\DOMXPath($document);
            $visualNodes = $xpath->query('//*[local-name()="drawing" or local-name()="pict" or local-name()="object"]');
            $hasVisual = $visualNodes !== false && $visualNodes->length > 0;
        } else {
            $hasText = preg_match('/<w:t(?:\\s[^>]*)?>\\s*[^<\\s][\\s\\S]*?<\\/w:t>/', $xml) === 1;
            $hasVisual = preg_match('/<(?:wp:)?(?:inline|anchor)|<w:pict|<w:object/', $xml) === 1;
        }

        if (!$hasVisual) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                if (is_string($entry) && str_starts_with($entry, 'word/media/') && !str_ends_with($entry, '/')) {
                    $hasVisual = true;
                    break;
                }
            }
        }

        $zip->close();

        if (!$hasText && !$hasVisual) {
            $reason = 'O DOCX foi criado, mas não contém texto nem imagens. O PDF pode ser digitalizado ou o LibreOffice não conseguiu extrair seu conteúdo.';
            return false;
        }

        return true;
    }

    private function assertUpload(string $tmp, string $originalName, int $maxBytes, array $exts): void
    {
        if ($tmp === '' || !is_file($tmp)) {
            throw new ValidationException('Arquivo de upload inválido.');
        }
        $size = (int) filesize($tmp);
        if ($size <= 0 || $size > $maxBytes) {
            throw new ValidationException('Tamanho de arquivo não permitido.');
        }
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, $exts, true)) {
            throw new ValidationException('Extensão não permitida.');
        }

        if (function_exists('upload_validate')) {
            // validação extra se upload-security estiver carregado
        }

        if ($ext === 'pdf') {
            $fh = fopen($tmp, 'rb');
            $sig = $fh ? (fread($fh, 5) ?: '') : '';
            if ($fh) {
                fclose($fh);
            }
            if (!str_starts_with($sig, '%PDF')) {
                throw new ValidationException('Arquivo PDF inválido.');
            }
        }
    }
}
