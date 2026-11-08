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

    /**
     * Compacta um PDF com Ghostscript e um perfil de qualidade permitido.
     *
     * @return array{path:string,mime:string,bytes:int,original_bytes:int,workspace:TempWorkspace}
     */
    public function compress(array $upload, string $quality = 'ebook', int $maxBytes = 31457280): array
    {
        $allowedQualities = ['screen', 'ebook', 'printer', 'prepress'];
        if (!in_array($quality, $allowedQualities, true)) {
            $quality = 'ebook';
        }

        $inputPath = $this->validateUploadedPdf($upload, $maxBytes);
        $originalBytes = (int) filesize($inputPath);
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
            $input = $workspace->join('input.pdf');
            $output = $workspace->join('compressed.pdf');

            if (!@copy($inputPath, $input)) {
                throw new AppException('Falha ao preparar o arquivo PDF.', 500, 'upload_store_failed');
            }

            $command = [
                $ghostscript,
                '-sDEVICE=pdfwrite',
                '-dCompatibilityLevel=1.4',
                '-dPDFSETTINGS=/' . $quality,
                '-dSAFER',
                '-dNOPAUSE',
                '-dQUIET',
                '-dBATCH',
                '-dDetectDuplicateImages=true',
                '-dCompressFonts=true',
                '-dSubsetFonts=true',
                '-sOutputFile=' . $output,
                $input,
            ];

            $result = $this->runner->run($command, $workspace->path(), $this->timeoutSeconds);

            if ($result['timed_out']) {
                throw new AppException('A compactação do PDF excedeu o tempo limite.', 504, 'timeout');
            }

            if ($result['exit'] !== 0 || !is_file($output) || filesize($output) < 5) {
                $detail = trim(($result['stderr'] ?? '') . ' ' . ($result['stdout'] ?? ''));
                if ($detail !== '') {
                    error_log('[pdf-compress] Ghostscript: ' . mb_substr($detail, 0, 500));
                }
                throw new AppException(
                    'Não foi possível compactar o PDF. Verifique se o arquivo não está corrompido ou protegido.',
                    422,
                    'pdf_compress_failed'
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
                'original_bytes' => $originalBytes,
                'workspace' => $workspace,
            ];
        } catch (\Throwable $e) {
            $workspace->cleanup();
            throw $e;
        }
    }

    /**
     * Separa um PDF em páginas individuais ou extrai um intervalo selecionado.
     *
     * @return array{path:string,mime:string,bytes:int,workspace:TempWorkspace}
     */
    public function split(array $upload, string $mode = 'each', string $range = '', int $maxBytes = 31457280): array
    {
        $inputPath = $this->validateUploadedPdf($upload, $maxBytes);
        $ghostscript = $this->findGhostscript();

        if ($ghostscript === null) {
            throw new AppException(
                'Ghostscript não encontrado. Instale o Ghostscript e configure GHOSTSCRIPT_PATH ou o PATH do servidor.',
                503,
                'ghostscript_missing'
            );
        }

        $mode = $mode === 'range' ? 'range' : 'each';
        $pagesToExtract = [];

        if ($mode === 'range') {
            $range = trim($range);
            if ($range === '' || !preg_match('/^[0-9,\\s\\-]+$/', $range)) {
                throw new ValidationException('Intervalo inválido. Use apenas números, vírgulas e hífens (ex: 1-3,5).');
            }

            foreach (explode(',', $range) as $part) {
                $part = trim($part);
                if (preg_match('/^(\\d+)\\s*-\\s*(\\d+)$/', $part, $matches)) {
                    $first = (int) $matches[1];
                    $last = (int) $matches[2];

                    if ($first > $last) {
                        [$first, $last] = [$last, $first];
                    }
                    if ($last - $first > 100) {
                        throw new ValidationException('Intervalo muito grande (máximo 100 páginas por vez).');
                    }

                    for ($page = $first; $page <= $last; $page++) {
                        if ($page >= 1) {
                            $pagesToExtract[] = $page;
                        }
                    }
                } elseif (preg_match('/^\\d+$/', $part)) {
                    $page = (int) $part;
                    if ($page >= 1) {
                        $pagesToExtract[] = $page;
                    }
                }
            }

            $pagesToExtract = array_values(array_unique($pagesToExtract));
            sort($pagesToExtract);

            if ($pagesToExtract === []) {
                throw new ValidationException('Informe um intervalo de páginas válido (ex: 1-3 ou 1,3,5).');
            }
        }

        $workspace = new TempWorkspace();

        try {
            $input = $workspace->join('input.pdf');
            if (!@copy($inputPath, $input)) {
                throw new AppException('Falha ao preparar o arquivo PDF.', 500, 'upload_store_failed');
            }

            $outputFiles = [];
            $safeArgs = [
                '-dSAFER',
                '-dNOPAUSE',
                '-dBATCH',
                '-dQUIET',
                '-dNOOUTERSAVE',
                '-dCompatibilityLevel=1.4',
            ];

            if ($mode === 'each') {
                $pattern = $workspace->join('page_%03d.pdf');
                $command = array_merge(
                    [$ghostscript, '-sDEVICE=pdfwrite'],
                    $safeArgs,
                    ['-sOutputFile=' . $pattern, $input]
                );

                $result = $this->runner->run($command, $workspace->path(), $this->timeoutSeconds);
                if ($result['timed_out']) {
                    throw new AppException('A separação do PDF excedeu o tempo limite.', 504, 'timeout');
                }
                if ($result['exit'] !== 0) {
                    $detail = trim(($result['stderr'] ?? '') . ' ' . ($result['stdout'] ?? ''));
                    if ($detail !== '') {
                        error_log('[pdf-split] Ghostscript: ' . mb_substr($detail, 0, 500));
                    }
                    throw new AppException('Não foi possível separar o PDF. Verifique se o arquivo não está protegido ou corrompido.', 422, 'pdf_split_failed');
                }

                $generated = glob($workspace->join('page_*.pdf')) ?: [];
                sort($generated);
                foreach ($generated as $file) {
                    if (is_file($file) && filesize($file) > 50) {
                        $outputFiles[] = $file;
                    }
                }
            } else {
                foreach ($pagesToExtract as $page) {
                    $output = $workspace->join(sprintf('page_%03d.pdf', $page));
                    $command = array_merge(
                        [$ghostscript, '-sDEVICE=pdfwrite'],
                        $safeArgs,
                        [
                            '-dFirstPage=' . $page,
                            '-dLastPage=' . $page,
                            '-sOutputFile=' . $output,
                            $input,
                        ]
                    );

                    $result = $this->runner->run($command, $workspace->path(), 60);
                    if ($result['timed_out']) {
                        throw new AppException('A extração das páginas excedeu o tempo limite.', 504, 'timeout');
                    }
                    if ($result['exit'] === 0 && is_file($output) && filesize($output) > 50) {
                        $outputFiles[] = $output;
                    }
                }
            }

            if ($outputFiles === []) {
                throw new AppException(
                    'Não foi possível separar o PDF. Verifique se o arquivo está protegido ou se o intervalo informado existe.',
                    422,
                    'pdf_split_empty'
                );
            }

            foreach ($outputFiles as $file) {
                $handle = @fopen($file, 'rb');
                $signature = $handle ? (fread($handle, 5) ?: '') : '';
                if (is_resource($handle)) {
                    fclose($handle);
                }
                if ($signature !== '%PDF-') {
                    throw new AppException('O Ghostscript gerou uma página PDF inválida.', 500, 'invalid_output');
                }
            }

            if (count($outputFiles) === 1) {
                $finalPath = $outputFiles[0];
                $mime = 'application/pdf';
            } else {
                if (!class_exists(\ZipArchive::class)) {
                    throw new AppException('A extensão ZIP do PHP não está habilitada no servidor.', 503, 'zip_extension_missing');
                }

                $finalPath = $workspace->join('paginas.zip');
                $zip = new \ZipArchive();
                if ($zip->open($finalPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                    throw new AppException('Não foi possível criar o arquivo ZIP.', 500, 'zip_create_failed');
                }

                foreach ($outputFiles as $file) {
                    if (!$zip->addFile($file, basename($file))) {
                        $zip->close();
                        throw new AppException('Não foi possível adicionar uma página ao arquivo ZIP.', 500, 'zip_add_failed');
                    }
                }
                if (!$zip->close() || !is_file($finalPath)) {
                    throw new AppException('Não foi possível finalizar o arquivo ZIP.', 500, 'zip_create_failed');
                }
                $mime = 'application/zip';
            }

            return [
                'path' => $finalPath,
                'mime' => $mime,
                'bytes' => (int) filesize($finalPath),
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
