<?php

declare(strict_types=1);

/**
 * SiteTools — PDF para Word (DOCX)
 * Usa LibreOffice/Soffice com filtro writer_pdf_import.
 */

const DOCX_MIME = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
const PDF_MAX_SIZE = 20 * 1024 * 1024;

function respondError(string $message, int $status = 400): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function findLibreOffice(): ?string
{
    $configFile = __DIR__ . '/../config/converters.php';
    if (is_file($configFile)) {
        $config = require $configFile;
        if (is_array($config) && !empty($config['libreoffice']) && is_file((string) $config['libreoffice'])) {
            return (string) $config['libreoffice'];
        }
    }

    $candidates = [
        'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
        'C:\\Program Files\\LibreOffice\\program\\soffice.com',
        'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
        'C:\\Program Files\\LibreOffice 24\\program\\soffice.exe',
        'C:\\Program Files\\LibreOffice 25\\program\\soffice.exe',
        'C:\\Program Files\\LibreOffice 7\\program\\soffice.exe',
        '/usr/bin/soffice',
        '/usr/bin/libreoffice',
        '/usr/lib/libreoffice/program/soffice',
        '/snap/bin/libreoffice',
        trim((string) shell_exec('where soffice 2>nul')),
        trim((string) shell_exec('where libreoffice 2>nul')),
        trim((string) shell_exec('command -v soffice 2>/dev/null')),
        trim((string) shell_exec('command -v libreoffice 2>/dev/null')),
    ];

    foreach ($candidates as $path) {
        $path = trim((string) $path);
        if ($path === '') {
            continue;
        }
        $first = preg_split('/\r\n|\r|\n/', $path)[0] ?? $path;
        $first = trim($first);
        if ($first !== '' && is_file($first)) {
            return $first;
        }
    }

    return null;
}

function runProcess(array $cmd, string $cwd, int $timeout = 180): array
{
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open(
        $cmd,
        $descriptors,
        $pipes,
        $cwd,
        null,
        ['bypass_shell' => true]
    );

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
            return ['exit' => -1, 'stdout' => $stdout, 'stderr' => 'Tempo limite excedido na conversão.'];
        }

        usleep(150000);
    }

    $stdout .= stream_get_contents($pipes[1]) ?: '';
    $stderr .= stream_get_contents($pipes[2]) ?: '';
    fclose($pipes[1]);
    fclose($pipes[2]);

    return ['exit' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
}

function cleanupDir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    try {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $item) {
            $item->isDir()
                ? @rmdir($item->getPathname())
                : @unlink($item->getPathname());
        }
    } catch (Throwable) {
        // ignore
    }

    @rmdir($dir);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respondError('Método não permitido.', 405);
}

if (!isset($_FILES['pdf'])) {
    respondError('Selecione um arquivo PDF.');
}

$file = $_FILES['pdf'];
$error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

if ($error !== UPLOAD_ERR_OK) {
    $messages = [
        UPLOAD_ERR_INI_SIZE   => 'O arquivo excede o limite do PHP (upload_max_filesize).',
        UPLOAD_ERR_FORM_SIZE  => 'O arquivo excede o limite do formulário.',
        UPLOAD_ERR_PARTIAL    => 'Upload incompleto. Tente novamente.',
        UPLOAD_ERR_NO_FILE    => 'Nenhum arquivo foi enviado.',
        UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária do PHP não encontrada.',
        UPLOAD_ERR_CANT_WRITE => 'Falha ao gravar o arquivo no servidor.',
        UPLOAD_ERR_EXTENSION  => 'Uma extensão do PHP bloqueou o upload.',
    ];
    respondError($messages[$error] ?? 'Falha no upload do arquivo.');
}

if (($file['size'] ?? 0) <= 0 || $file['size'] > PDF_MAX_SIZE) {
    respondError('O PDF deve ter até 20 MB.');
}

$tmp = (string) ($file['tmp_name'] ?? '');
if ($tmp === '' || !is_file($tmp)) {
    respondError('Arquivo temporário inválido.');
}

$handle = fopen($tmp, 'rb');
$header = $handle ? (fread($handle, 1024) ?: '') : '';
if (is_resource($handle)) {
    fclose($handle);
}
if (strpos($header, '%PDF-') === false) {
    respondError('O arquivo enviado não é um PDF válido.');
}

if (!function_exists('proc_open')) {
    respondError('O servidor não permite executar o conversor (proc_open desabilitado).', 500);
}

$soffice = findLibreOffice();
if ($soffice === null) {
    respondError(
        'LibreOffice não encontrado. Instale o LibreOffice e reinicie o Wamp. Caminho esperado: C:\\Program Files\\LibreOffice\\program\\soffice.exe',
        500
    );
}

$workDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sitetools_pdfword_' . bin2hex(random_bytes(8));
if (!mkdir($workDir, 0700)) {
    respondError('Não foi possível criar diretório temporário.', 500);
}

$inputPath  = $workDir . DIRECTORY_SEPARATOR . 'input.pdf';
$outputPath = $workDir . DIRECTORY_SEPARATOR . 'input.docx';
$profileDir = $workDir . DIRECTORY_SEPARATOR . 'lo-profile';

if (!copy($tmp, $inputPath)) {
    cleanupDir($workDir);
    respondError('Falha ao preparar o PDF.', 500);
}

$profileUri = 'file:///' . str_replace('\\', '/', $profileDir);

$cmd = [
    $soffice,
    '--headless',
    '--nologo',
    '--nodefault',
    '--nolockcheck',
    '--norestore',
    '--nofirststartwizard',
    '-env:UserInstallation=' . $profileUri,
    '--infilter=writer_pdf_import',
    '--convert-to', 'docx',
    '--outdir', $workDir,
    $inputPath,
];

$result = runProcess($cmd, $workDir, 180);

if (($result['exit'] !== 0 || !is_file($outputPath) || filesize($outputPath) < 100)) {
    if (is_file($outputPath)) {
        @unlink($outputPath);
    }

    $cmd2 = [
        $soffice,
        '--headless',
        '--nologo',
        '--nodefault',
        '--nolockcheck',
        '--norestore',
        '-env:UserInstallation=' . $profileUri,
        '--convert-to', 'docx:writer_pdf_import',
        '--outdir', $workDir,
        $inputPath,
    ];

    $result = runProcess($cmd2, $workDir, 180);
}

if (!is_file($outputPath) || filesize($outputPath) < 100) {
    $found = glob($workDir . DIRECTORY_SEPARATOR . '*.docx') ?: [];
    foreach ($found as $candidate) {
        if (is_file($candidate) && filesize($candidate) >= 100) {
            $outputPath = $candidate;
            break;
        }
    }
}

if (!is_file($outputPath) || filesize($outputPath) < 100) {
    $detail = trim(($result['stderr'] ?? '') . ' ' . ($result['stdout'] ?? ''));
    error_log('SiteTools PDF->Word falhou: ' . $detail);
    cleanupDir($workDir);

    if (stripos($detail, 'timeout') !== false || stripos($detail, 'Tempo limite') !== false) {
        respondError('A conversão demorou demais. Tente um PDF menor ou com menos páginas.', 500);
    }

    respondError(
        'Não foi possível converter este PDF. Verifique se o LibreOffice está instalado e se o PDF não está protegido por senha.',
        500
    );
}

$downloadName = pathinfo(basename((string) ($file['name'] ?? 'documento')), PATHINFO_FILENAME);
$downloadName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $downloadName) ?: 'documento';

$docx = file_get_contents($outputPath);
cleanupDir($workDir);

if ($docx === false || strlen($docx) < 100) {
    respondError('O arquivo convertido está vazio ou inválido.', 500);
}

if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: ' . DOCX_MIME);
header('Content-Disposition: attachment; filename="' . $downloadName . '.docx"');
header('Content-Length: ' . strlen($docx));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, private');

echo $docx;
exit;
