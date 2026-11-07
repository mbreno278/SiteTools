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
        $cmd = [
            $soffice,
            '--headless',
            '--nologo',
            '--nofirststartwizard',
            '--norestore',
            '--convert-to', 'docx',
            '--outdir', $outDir,
            $input,
        ];

        $result = $this->runner->run($cmd, $outDir, $this->timeoutSeconds);
        if ($result['timed_out']) {
            $workspace->cleanup();
            throw new AppException('A conversão excedeu o tempo limite.', 504, 'timeout');
        }

        $base = pathinfo($input, PATHINFO_FILENAME);
        $docx = $outDir . DIRECTORY_SEPARATOR . $base . '.docx';
        if (!is_file($docx)) {
            $found = glob($outDir . DIRECTORY_SEPARATOR . '*.docx') ?: [];
            $docx = $found[0] ?? '';
        }

        if ($docx === '' || !is_file($docx)) {
            $workspace->cleanup();
            throw new AppException('Não foi possível converter o PDF.', 500, 'conversion_failed');
        }

        return [
            'path' => $docx,
            'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'bytes' => (int) filesize($docx),
            'workspace' => $workspace,
        ];
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
