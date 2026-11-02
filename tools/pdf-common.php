<?php

declare(strict_types=1);

/**
 * Funções compartilhadas das ferramentas PDF.
 *
 * Segurança:
 * - Ghostscript sempre com -dSAFER (nunca -dNOSAFER)
 * - qpdf sem opções perigosas de escrita arbitrária
 * - Validação rigorosa de upload (assinatura PDF, tamanho)
 * - Senhas sanitizadas (sem injeção em argumentos)
 */

const PDF_MAX_SIZE = 30 * 1024 * 1024; // 30 MB

function pdfRespondJson(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function pdfRespondError(string $message, int $status = 400): never
{
    pdfRespondJson(['error' => $message], $status);
}

function pdfFindGs(): ?string
{
    $candidates = [
        'C:\\Program Files\\gs\\gs10.08.0\\bin\\gswin64c.exe',
        'C:\\Program Files\\gs\\gs10.07.0\\bin\\gswin64c.exe',
        'C:\\Program Files\\gs\\gs10.06.0\\bin\\gswin64c.exe',
        'C:\\Program Files\\gs\\gs10.05.0\\bin\\gswin64c.exe',
        'C:\\Program Files\\gs\\gs10.04.0\\bin\\gswin64c.exe',
        'C:\\Program Files\\gs\\gs10.03.0\\bin\\gswin64c.exe',
        'C:\\Program Files\\gs\\gs10.02.0\\bin\\gswin64c.exe',
        'C:\\Program Files\\gs\\gs10.01.0\\bin\\gswin64c.exe',
        'C:\\Program Files\\gs\\gs10.00.0\\bin\\gswin64c.exe',
        'C:\\Program Files (x86)\\gs\\gs10.08.0\\bin\\gswin32c.exe',
        'C:\\Program Files (x86)\\gs\\gs10.00.0\\bin\\gswin32c.exe',
        'gswin64c',
        'gswin32c',
        'gs',
        '/usr/bin/gs',
        '/usr/local/bin/gs',
        trim((string) shell_exec('where gswin64c 2>nul')),
        trim((string) shell_exec('where gs 2>nul')),
        trim((string) shell_exec('command -v gs 2>/dev/null')),
    ];

    foreach ($candidates as $path) {
        $path = trim((string) $path);
        if ($path === '') {
            continue;
        }
        if (is_file($path)) {
            return $path;
        }
        if (!str_contains($path, DIRECTORY_SEPARATOR) && !str_contains($path, '/')) {
            return $path;
        }
    }

    $test = trim((string) shell_exec('gswin64c -v 2>nul'));
    if ($test !== '') {
        return 'gswin64c';
    }
    $test = trim((string) shell_exec('gs -v 2>/dev/null'));
    if ($test !== '') {
        return 'gs';
    }

    return null;
}

function pdfFindQpdf(): ?string
{
    $candidates = [
        'C:\\Program Files\\qpdf\\bin\\qpdf.exe',
        'C:\\qpdf\\bin\\qpdf.exe',
        'qpdf',
        '/usr/bin/qpdf',
        '/usr/local/bin/qpdf',
        trim((string) shell_exec('where qpdf 2>nul')),
        trim((string) shell_exec('command -v qpdf 2>/dev/null')),
    ];

    foreach ($candidates as $path) {
        $path = trim((string) $path);
        if ($path === '') {
            continue;
        }
        if (is_file($path)) {
            return $path;
        }
        if (!str_contains($path, DIRECTORY_SEPARATOR) && !str_contains($path, '/')) {
            return $path;
        }
    }

    $test = trim((string) shell_exec('qpdf --version 2>nul'));
    if ($test === '') {
        $test = trim((string) shell_exec('qpdf --version 2>/dev/null'));
    }
    if ($test !== '') {
        return 'qpdf';
    }

    return null;
}

/**
 * Argumentos base seguros do Ghostscript.
 * -dSAFER impede leitura/escrita arbitrária no sistema via PostScript.
 */
function pdfGsSafeArgs(): array
{
    return [
        '-dSAFER',
        '-dNOPAUSE',
        '-dBATCH',
        '-dQUIET',
        '-dNOOUTERSAVE',
        '-dCompatibilityLevel=1.4',
    ];
}

/**
 * Remove flags inseguras e garante -dSAFER em comandos Ghostscript.
 */
function pdfEnforceGsSecurity(array $cmd): array
{
    if ($cmd === []) {
        return $cmd;
    }

    $binary = (string) $cmd[0];
    $isGs = (bool) preg_match('/(^|[\\\\\/])(gswin64c|gswin32c|gs)(\.exe)?$/i', $binary)
        || strcasecmp($binary, 'gs') === 0
        || strcasecmp($binary, 'gswin64c') === 0
        || strcasecmp($binary, 'gswin32c') === 0;

    if (!$isGs) {
        return $cmd;
    }

    $safe = [$binary];
    $hasSafer = false;

    for ($i = 1, $n = count($cmd); $i < $n; $i++) {
        $arg = (string) $cmd[$i];

        // Bloqueia explicitamente modos inseguros
        if (preg_match('/^-dNOSAFER$/i', $arg)) {
            continue;
        }
        if (preg_match('/^-dDELAYSAFER$/i', $arg)) {
            continue;
        }
        // Evita -c com código PostScript arbitrário do usuário (já não deve existir)
        if ($arg === '-c' || $arg === '-f') {
            // Mantém apenas se o próximo argumento for controlado por nós;
            // ainda assim, -c é arriscado — preferimos não usar no pipeline público.
            // Aqui só passamos adiante flags já conhecidas do nosso código.
        }

        if (preg_match('/^-dSAFER$/i', $arg)) {
            $hasSafer = true;
        }

        $safe[] = $arg;
    }

    if (!$hasSafer) {
        // Insere -dSAFER logo após o binário
        array_splice($safe, 1, 0, ['-dSAFER']);
    }

    return $safe;
}

/**
 * Sanitiza senha para uso em argumentos de linha de comando.
 * Remove caracteres de controle; limita tamanho.
 */
function pdfSanitizePassword(string $password): string
{
    // Remove null bytes e controles
    $password = preg_replace('/[\x00-\x1F\x7F]/u', '', $password) ?? '';
    $password = trim($password);

    if ($password === '') {
        pdfRespondError('Informe a senha.');
    }
    if (strlen($password) > 64) {
        pdfRespondError('Senha muito longa (máximo 64 caracteres).');
    }

    return $password;
}

/**
 * Rejeita saídas "falsas" do Ghostscript (página em branco / arquivo minúsculo).
 */
function pdfAssertSaneOutput(string $inputPath, string $outputPath, string $failMessage): void
{
    if (!is_file($outputPath)) {
        pdfRespondError($failMessage);
    }

    $inSize = (int) filesize($inputPath);
    $outSize = (int) filesize($outputPath);

    if ($outSize < 200) {
        pdfRespondError($failMessage);
    }

    if ($inSize > 100_000 && $outSize < max(8000, (int) ($inSize * 0.05))) {
        pdfRespondError($failMessage);
    }
}

/**
 * Executa comando com reforço de segurança para Ghostscript.
 * Nunca permite -dNOSAFER.
 */
function pdfRun(array $cmd, int $timeout = 120): array
{
    // Segurança: bloqueia NOSAFER mesmo se algum tool passar por engano
    foreach ($cmd as $arg) {
        if (is_string($arg) && preg_match('/^-dNOSAFER$/i', $arg)) {
            return [
                'exit' => -1,
                'stdout' => '',
                'stderr' => 'Comando bloqueado: -dNOSAFER não é permitido.',
            ];
        }
    }

    $cmd = pdfEnforceGsSecurity($cmd);

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($cmd, $descriptors, $pipes, null, null, ['bypass_shell' => true]);

    if (!is_resource($process)) {
        return ['exit' => -1, 'stdout' => '', 'stderr' => 'Falha ao iniciar processo.'];
    }

    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    $stdout = '';
    $stderr = '';
    $start = time();

    while (true) {
        $status = proc_get_status($process);
        $stdout .= stream_get_contents($pipes[1]) ?: '';
        $stderr .= stream_get_contents($pipes[2]) ?: '';

        if (!$status['running']) {
            break;
        }
        if ((time() - $start) > $timeout) {
            proc_terminate($process, 9);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
            return ['exit' => -1, 'stdout' => $stdout, 'stderr' => 'Tempo limite excedido.'];
        }
        usleep(100000);
    }

    $stdout .= stream_get_contents($pipes[1]) ?: '';
    $stderr .= stream_get_contents($pipes[2]) ?: '';
    fclose($pipes[1]);
    fclose($pipes[2]);

    return ['exit' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
}

function pdfValidateUpload(array $file): string
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($error !== UPLOAD_ERR_OK) {
        $messages = [
            UPLOAD_ERR_INI_SIZE   => 'O arquivo excede o limite do PHP (upload_max_filesize). Aumente em php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'O arquivo excede o limite do formulário.',
            UPLOAD_ERR_PARTIAL    => 'O upload foi concluído apenas parcialmente. Tente novamente.',
            UPLOAD_ERR_NO_FILE    => 'Nenhum arquivo foi enviado.',
            UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária do PHP não encontrada no servidor.',
            UPLOAD_ERR_CANT_WRITE => 'Falha ao gravar o arquivo no disco do servidor.',
            UPLOAD_ERR_EXTENSION  => 'Uma extensão do PHP bloqueou o upload.',
        ];
        pdfRespondError($messages[$error] ?? ('Falha no upload do arquivo (código ' . $error . ').'));
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    $size = (int) ($file['size'] ?? 0);

    if ($tmp === '' || !is_file($tmp)) {
        pdfRespondError('Arquivo temporário não encontrado. Verifique as permissões da pasta tmp do PHP.');
    }

    if ($size <= 0) {
        pdfRespondError('O arquivo enviado está vazio.');
    }

    if ($size > PDF_MAX_SIZE) {
        pdfRespondError('O PDF deve ter até 30 MB.');
    }

    if (function_exists('is_uploaded_file') && !is_uploaded_file($tmp)) {
        if (!is_readable($tmp)) {
            pdfRespondError('Upload inválido ou arquivo inacessível.');
        }
    }

    // Assinatura PDF
    $handle = fopen($tmp, 'rb');
    if ($handle === false) {
        pdfRespondError('Não foi possível ler o arquivo enviado.');
    }
    $header = fread($handle, 1024) ?: '';
    fclose($handle);

    if (strpos($header, '%PDF-') === false) {
        pdfRespondError('O arquivo enviado não parece ser um PDF válido.');
    }

    // Bloqueia alguns padrões óbvios de PDF malicioso (JavaScript embutido em massa)
    // Não é antivírus completo — apenas uma camada extra.
    $sample = file_get_contents($tmp, false, null, 0, min($size, 512 * 1024));
    if (is_string($sample)) {
        if (preg_match('/\/JS\b|\/JavaScript\b|\/OpenAction\b|\/Launch\b|\/EmbeddedFile\b/i', $sample)) {
            // Muitos PDFs legítimos usam OpenAction — só bloqueia combinação suspeita com JS
            if (preg_match('/\/JS\b|\/JavaScript\b/i', $sample) && preg_match('/\/OpenAction\b|\/AA\b/i', $sample)) {
                pdfRespondError('PDF rejeitado por conter JavaScript potencialmente perigoso.');
            }
        }
    }

    return $tmp;
}

function pdfTmpDir(): string
{
    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sitetools_pdf_' . bin2hex(random_bytes(8));
    if (!mkdir($dir, 0700)) {
        pdfRespondError('Não foi possível criar diretório temporário.', 500);
    }
    return $dir;
}

function pdfCleanup(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $f) {
        if (is_file($f)) {
            @unlink($f);
        } elseif (is_dir($f)) {
            // limpa subpastas rasas
            foreach (glob($f . DIRECTORY_SEPARATOR . '*') ?: [] as $sf) {
                if (is_file($sf)) {
                    @unlink($sf);
                }
            }
            @rmdir($f);
        }
    }
    @rmdir($dir);
}

function pdfSendFile(string $path, string $downloadName): never
{
    if (!is_file($path) || filesize($path) < 10) {
        pdfRespondError('Arquivo de saída inválido.', 500);
    }

    // Nome de download seguro
    $downloadName = basename($downloadName);
    $downloadName = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $downloadName) ?: 'documento.pdf';

    if (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/pdf');
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');

    readfile($path);
    exit;
}

function pdfSendZip(string $zipPath, string $downloadName): never
{
    if (!is_file($zipPath)) {
        pdfRespondError('Arquivo ZIP inválido.', 500);
    }

    $downloadName = basename($downloadName);
    $downloadName = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $downloadName) ?: 'arquivos.zip';

    if (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/zip');
    header('Content-Length: ' . filesize($zipPath));
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');

    readfile($zipPath);
    exit;
}
