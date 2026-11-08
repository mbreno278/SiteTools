<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AppException;
use App\Exceptions\ValidationException;
use App\Services\Process\ProcessRunner;
use App\Services\Process\TempWorkspace;

/**
 * Operações específicas de PDF.
 *
 * A conversão PDF → Word e as operações de manipulação de PDF compartilham
 * esta fachada para manter uma API estável para os Controllers.
 */
final class PdfService
{
    public function __construct(
        private readonly DocumentConverterService $converter = new DocumentConverterService(),
        private readonly ProcessRunner $runner = new ProcessRunner(),
        private readonly int $timeoutSeconds = 180,
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

    /**
     * Une de 2 a 20 PDFs, preservando a ordem recebida pelo formulário.
     *
     * @param array{name:array, tmp_name:array, error:array, size:array} $uploads
     * @return array{path:string,mime:string,bytes:int,workspace:TempWorkspace}
     */
    public function merge(array $uploads, int $maxFiles = 20, int $maxBytesPerFile = 31457280): array
    {
        $names = $uploads['name'] ?? null;
        $tmpNames = $uploads['tmp_name'] ?? null;
        $errors = $uploads['error'] ?? null;

        if (!is_array($names) || !is_array($tmpNames) || !is_array($errors)) {
            throw new ValidationException('Envie os arquivos PDF corretamente.');
        }

        $count = count($names);
        if ($count < 2) {
            throw new ValidationException('Selecione pelo menos 2 arquivos PDF.');
        }
        if ($count > $maxFiles) {
            throw new ValidationException('Máximo de ' . $maxFiles . ' arquivos por vez.');
        }

        $validated = [];
        for ($i = 0; $i < $count; $i++) {
            $file = [
                'name' => $names[$i] ?? '',
                'tmp_name' => $tmpNames[$i] ?? '',
                'error' => $errors[$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $uploads['size'][$i] ?? 0,
            ];
            $validated[] = $this->validateUploadedPdf($file, $maxBytesPerFile);
        }

        $ghostscript = $this->findGhostscript();
        if ($ghostscript === null) {
            throw new AppException(
                'Ghostscript não encontrado. Instale o Ghostscript e configure GHOSTSCRIPT_PATH ou o PATH do servidor.',
                503,
                'ghostscript_missing'
            );
        }

        $workspace = new TempWorkspace();

        try {
            $inputs = [];
            foreach ($validated as $index => $tmpPath) {
                $destination = $workspace->join(sprintf('input_%02d.pdf', $index));
                if (!@copy($tmpPath, $destination)) {
                    throw new AppException('Falha ao preparar os arquivos PDF.', 500, 'upload_store_failed');
                }
                $inputs[] = $destination;
            }

            $output = $workspace->join('merged.pdf');
            $command = array_merge(
                [
                    $ghostscript,
                    '-sDEVICE=pdfwrite',
                    '-dSAFER',
                    '-dNOPAUSE',
                    '-dQUIET',
                    '-dBATCH',
                    '-sOutputFile=' . $output,
                ],
                $inputs
            );

            $result = $this->runner->run($command, $workspace->path(), $this->timeoutSeconds);

            if ($result['timed_out']) {
                throw new AppException('A união dos PDFs excedeu o tempo limite.', 504, 'timeout');
            }

            if ($result['exit'] !== 0 || !is_file($output) || filesize($output) < 200) {
                $detail = trim(($result['stderr'] ?? '') . ' ' . ($result['stdout'] ?? ''));
                if ($detail !== '') {
                    error_log('[pdf-merge] Ghostscript: ' . mb_substr($detail, 0, 500));
                }
                throw new AppException(
                    'Não foi possível unir os PDFs. Verifique se algum arquivo está protegido ou corrompido.',
                    422,
                    'pdf_merge_failed'
                );
            }

            $handle = @fopen($output, 'rb');
            $signature = $handle ? (fread($handle, 5) ?: '') : '';
            if (is_resource($handle)) {
                fclose($handle);
            }
            if ($signature !== '%PDF-') {
                throw new AppException('O arquivo gerado não é um PDF válido.', 500, 'invalid_output');
            }

            return [
                'path' => $output,
                'mime' => 'application/pdf',
                'bytes' => (int) filesize($output),
                'workspace' => $workspace,
            ];
        } catch (\Throwable $e) {
            $workspace->cleanup();
            throw $e;
        }
    }

    private function validateUploadedPdf(array $file, int $maxBytes): string
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            $messages = [
                UPLOAD_ERR_INI_SIZE => 'Um dos arquivos excede o limite de upload configurado no PHP.',
                UPLOAD_ERR_FORM_SIZE => 'Um dos arquivos excede o limite do formulário.',
                UPLOAD_ERR_PARTIAL => 'Um dos uploads foi concluído parcialmente.',
                UPLOAD_ERR_NO_FILE => 'Um dos arquivos PDF não foi enviado.',
                UPLOAD_ERR_NO_TMP_DIR => 'A pasta temporária do PHP não foi encontrada.',
                UPLOAD_ERR_CANT_WRITE => 'Falha ao gravar um dos arquivos no servidor.',
                UPLOAD_ERR_EXTENSION => 'Uma extensão do PHP bloqueou um dos uploads.',
            ];
            throw new ValidationException($messages[$error] ?? 'Falha ao enviar um dos arquivos PDF.');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp) || !is_readable($tmp)) {
            throw new ValidationException('Um dos arquivos enviados é inválido.');
        }

        $size = filesize($tmp);
        if ($size === false || $size <= 0) {
            throw new ValidationException('Um dos arquivos PDF está vazio.');
        }
        if ($size > $maxBytes) {
            throw new ValidationException('Cada PDF deve ter até ' . (int) ($maxBytes / 1048576) . ' MB.');
        }

        $handle = @fopen($tmp, 'rb');
        if ($handle === false) {
            throw new ValidationException('Não foi possível ler um dos arquivos enviados.');
        }
        $header = fread($handle, 1024) ?: '';
        fclose($handle);

        // O padrão PDF permite que o cabeçalho apareça nos primeiros 1024 bytes.
        if (strpos($header, '%PDF-') === false) {
            throw new ValidationException('Um dos arquivos enviados não parece ser um PDF válido.');
        }

        $sample = @file_get_contents($tmp, false, null, 0, min($size, 512 * 1024));
        if (is_string($sample)
            && preg_match('/\/JS\b|\/JavaScript\b/i', $sample)
            && preg_match('/\/OpenAction\b|\/AA\b/i', $sample)
        ) {
            throw new ValidationException('Um dos PDFs foi rejeitado por conter JavaScript potencialmente perigoso.');
        }

        return $tmp;
    }

    private function findGhostscript(): ?string
    {
        $configFile = dirname(__DIR__, 2) . '/config/converters.php';
        if (is_file($configFile)) {
            $config = require $configFile;
            if (is_array($config) && !empty($config['ghostscript'])) {
                $configured = (string) $config['ghostscript'];
                if (is_file($configured) || !str_contains($configured, DIRECTORY_SEPARATOR)) {
                    return $configured;
                }
            }
        }

        $candidates = PHP_OS_FAMILY === 'Windows'
            ? ['C:\\Program Files\\gs\\gs10.08.0\\bin\\gswin64c.exe', 'C:\\Program Files\\gs\\gs10.05.0\\bin\\gswin64c.exe', 'gswin64c', 'gs']
            : ['/usr/bin/gs', '/usr/local/bin/gs', 'gs'];

        foreach ($candidates as $candidate) {
            if (is_file($candidate) || !str_contains($candidate, DIRECTORY_SEPARATOR)) {
                return $candidate;
            }
        }

        return null;
    }
}
