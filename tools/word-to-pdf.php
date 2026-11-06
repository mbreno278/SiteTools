<?php

declare(strict_types=1);

require __DIR__ . '/_csrf_guard.php';

require __DIR__ . '/_tool_access_guard.php';
tool_access_guard_for('word-to-pdf');



/**
 * SiteTools — Word (DOC/DOCX) para PDF
 * Usa LibreOffice/Soffice em modo headless.
 */

const PDF_MIME = 'application/pdf';
const MAX_SIZE = 20 * 1024 * 1024;

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
        'C:\\Program Files\\LibreOffice\\program\\soffice.com',
        'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
        'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.com',
        'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
        'C:\\Program Files\\LibreOffice 24\\program\\soffice.com',
        'C:\\Program Files\\LibreOffice 25\\program\\soffice.com',
        'C:\\Program Files\\LibreOffice 7\\program\\soffice.exe',
        '/usr/bin/soffice',
        '/usr/bin/libreoffice',
        '/usr/lib/libreoffice/program/soffice',
        '/snap/bin/libreoffice',
        trim((string) shell_exec('where soffice.com 2>nul')),
        trim((string) shell_exec('where soffice 2>nul')),
        trim((string) shell_exec('command -v soffice 2>/dev/null')),
        trim((string) shell_exec('command -v libreoffice 2>/dev/null')),
    ];

    foreach ($candidates as $path) {
        $path = trim((string) $path);
        if ($path === '') {
            continue;
        }
        $first = trim(preg_split('/\r\n|\r|\n/', $path)[0] ?? $path);
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

    $process = @proc_open($cmd, $descriptors, $pipes, $cwd, null, ['bypass_shell' => true]);

    if (!is_resource($process)) {
        $line = implode(' ', array_map('escapeshellarg', $cmd));
        $process = @proc_open($line, $descriptors, $pipes, $cwd);
    }

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
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
    } catch (Throwable) {
    }
    @rmdir($dir);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respondError('Método não permitido.', 405);
}

if (!isset($_FILES['word'])) {
    respondError('Selecione um arquivo Word.');
}

$file = $_FILES['word'];
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

if (($file['size'] ?? 0) <= 0 || $file['size'] > MAX_SIZE) {
    respondError('O arquivo deve ter até 20 MB.');
}

$tmp = (string) ($file['tmp_name'] ?? '');
if ($tmp === '' || !is_file($tmp)) {
    respondError('Arquivo temporário inválido.');
}

$originalName = (string) ($file['name'] ?? 'documento.docx');
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if (!in_array($ext, ['doc', 'docx'], true)) {
    respondError('Envie um arquivo .doc ou .docx.');
}

// Validação básica de assinatura
$handle = fopen($tmp, 'rb');
$sig = $handle ? (fread($handle, 8) ?: '') : '';
if (is_resource($handle)) {
    fclose($handle);
}

// DOCX = ZIP (PK), DOC antigo = D0 CF 11 E0
$isDocx = str_starts_with($sig, 'PK');
$isDoc  = strlen($sig) >= 4 && $sig[0] === "\xD0" && $sig[1] === "\xCF";

if (!$isDocx && !$isDoc) {
    respondError('O arquivo não parece ser um Word válido (.doc/.docx).');
}

if (!function_exists('proc_open')) {
    respondError('proc_open está desabilitado no PHP.', 500);
}

$soffice = findLibreOffice();
if ($soffice === null) {
    respondError(
        'LibreOffice não encontrado. Instale o LibreOffice e reinicie o Wamp. '
        . 'Caminho esperado: C:\\Program Files\\LibreOffice\\program\\soffice.exe',
        500
    );
}

$workDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sitetools_wordpdf_' . bin2hex(random_bytes(8));
if (!mkdir($workDir, 0700)) {
    respondError('Não foi possível criar diretório temporário.', 500);
}

$inputPath  = $workDir . DIRECTORY_SEPARATOR . 'input.' . $ext;
$outputPath = $workDir . DIRECTORY_SEPARATOR . 'input.pdf';
$profileDir = $workDir . DIRECTORY_SEPARATOR . 'lo-profile';

if (!copy($tmp, $inputPath)) {
    cleanupDir($workDir);
    respondError('Falha ao preparar o arquivo.', 500);
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
    '--convert-to', 'pdf',
    '--outdir', $workDir,
    $inputPath,
];

$result = runProcess($cmd, $workDir, 180);

// Aceita qualquer PDF gerado
if (!is_file($outputPath) || filesize($outputPath) < 100) {
    $found = glob($workDir . DIRECTORY_SEPARATOR . '*.pdf') ?: [];
    foreach ($found as $cand) {
        if (is_file($cand) && filesize($cand) >= 100) {
            $outputPath = $cand;
            break;
        }
    }
}

if (!is_file($outputPath) || filesize($outputPath) < 100) {
    $detail = trim(($result['stderr'] ?? '') . ' ' . ($result['stdout'] ?? ''));
    error_log('SiteTools Word->PDF: ' . $detail);
    cleanupDir($workDir);

    if (stripos($detail, 'timeout') !== false || stripos($detail, 'Tempo limite') !== false) {
        respondError('A conversão demorou demais. Tente um arquivo menor.', 500);
    }

    respondError('Não foi possível converter este arquivo. Verifique se o LibreOffice está instalado e se o documento não está corrompido.', 500);
}

// Confirma assinatura PDF
$fh = fopen($outputPath, 'rb');
$pdfSig = $fh ? (fread($fh, 5) ?: '') : '';
if (is_resource($fh)) {
    fclose($fh);
}
if (strpos($pdfSig, '%PDF') === false) {
    cleanupDir($workDir);
    respondError('A conversão não gerou um PDF válido.', 500);
}

$downloadName = pathinfo($originalName, PATHINFO_FILENAME);
$downloadName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $downloadName) ?: 'documento';

$pdf = file_get_contents($outputPath);
cleanupDir($workDir);

if ($pdf === false || strlen($pdf) < 100) {
    respondError('O PDF gerado está vazio.', 500);
}

if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: ' . PDF_MIME);
header('Content-Disposition: attachment; filename="' . $downloadName . '.pdf"');
header('Content-Length: ' . strlen($pdf));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, private');
echo $pdf;
exit;
