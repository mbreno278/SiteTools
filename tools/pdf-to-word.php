
<?php

declare(strict_types=1);

$config = require __DIR__ . '/../config/converters.php';

const DOCX_MIME =
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

function respondError(string $message, int $status): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(
        ['error' => $message],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respondError('Método não permitido.', 405);
}

if (!isset($_FILES['pdf'])) {
    respondError('Selecione um arquivo PDF.', 400);
}

$file = $_FILES['pdf'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    respondError('Não foi possível receber o arquivo enviado.', 400);
}

$maxSize = (int) $config['max_file_size'];

if ($file['size'] <= 0 || $file['size'] > $maxSize) {
    respondError('O PDF deve ter até 20 MB.', 413);
}

if (!is_uploaded_file($file['tmp_name'])) {
    respondError('Upload inválido.', 400);
}

$handle = fopen($file['tmp_name'], 'rb');
$signature = $handle ? fread($handle, 5) : false;

if (is_resource($handle)) {
    fclose($handle);
}

if ($signature !== '%PDF-') {
    respondError('O arquivo enviado não é um PDF válido.', 415);
}

$soffice = (string) $config['libreoffice'];

if (!is_file($soffice)) {
    respondError('LibreOffice não encontrado. Verifique a configuração.', 500);
}

if (!function_exists('proc_open')) {
    respondError('O servidor não permite executar o conversor.', 500);
}

$workDir = sys_get_temp_dir()
    . DIRECTORY_SEPARATOR
    . 'sitetools_' . bin2hex(random_bytes(16));

if (!mkdir($workDir, 0700)) {
    respondError('Não foi possível iniciar a conversão.', 500);
}

$inputPath = $workDir . DIRECTORY_SEPARATOR . 'input.pdf';
$outputPath = $workDir . DIRECTORY_SEPARATOR . 'input.docx';
$profilePath = $workDir . DIRECTORY_SEPARATOR . 'lo-profile';

try {
    if (!copy($file['tmp_name'], $inputPath)) {
        throw new RuntimeException('Falha ao preparar o PDF.');
    }

    $profileUri = 'file:///' . str_replace(
        '\\',
        '/',
        $profilePath
    );

    $command = escapeshellarg($soffice)
        . ' --headless'
        . ' --nologo --nodefault --nofirststartwizard'
        . ' ' . escapeshellarg(
            '-env:UserInstallation=' . $profileUri
        )
        . ' --convert-to docx'
        . ' --outdir ' . escapeshellarg($workDir)
        . ' ' . escapeshellarg($inputPath);

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open(
        $command,
        $descriptors,
        $pipes,
        $workDir
    );

    if (!is_resource($process)) {
        throw new RuntimeException('Não foi possível iniciar o LibreOffice.');
    }

    fclose($pipes[0]);

    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);

    if (
        $exitCode !== 0
        || !is_file($outputPath)
        || filesize($outputPath) === 0
    ) {
        error_log('PDF para Word: ' . $stderr . ' ' . $stdout);
        throw new RuntimeException('A conversão não foi concluída.');
    }

    $downloadName = pathinfo(
        basename((string) $file['name']),
        PATHINFO_FILENAME
    );

    $downloadName = preg_replace(
        '/[^a-zA-Z0-9_-]+/',
        '_',
        $downloadName
    ) ?: 'documento';

    $docx = file_get_contents($outputPath);

    if ($docx === false) {
        throw new RuntimeException('Não foi possível ler o DOCX.');
    }

    header('Content-Type: ' . DOCX_MIME);
    header('Content-Disposition: attachment; filename="'
        . $downloadName . '.docx"');
    header('Content-Length: ' . strlen($docx));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store, private');

    echo $docx;
} catch (Throwable $exception) {
    error_log('SiteTools PDF para Word: ' . $exception->getMessage());

    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'error' => 'Não foi possível converter este PDF. '
            . 'Verifique se ele está íntegro e tente outro arquivo.'
    ], JSON_UNESCAPED_UNICODE);
} finally {
    foreach (glob($workDir . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
        if (is_file($path)) {
            @unlink($path);
        } elseif (is_dir($path)) {
            // Remove somente o perfil temporário desta execução.
            foreach (
                new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator(
                        $path,
                        FilesystemIterator::SKIP_DOTS
                    ),
                    RecursiveIteratorIterator::CHILD_FIRST
                ) as $item
            ) {
                $item->isDir()
                    ? @rmdir($item->getPathname())
                    : @unlink($item->getPathname());
            }

            @rmdir($path);
        }
    }

    @rmdir($workDir);
}
