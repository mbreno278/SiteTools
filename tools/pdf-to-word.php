<?php

/**
 * SiteTools — Conversor PDF para Word (DOCX)
 * Requer LibreOffice com o filtro de importação writer_pdf_import.
 */

const DOCX_MIME = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
const PDF_MAX_SIZE = 20 * 1024 * 1024;

function respondError(string $message, int $status = 400): void
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

        if (
            is_array($config)
            && !empty($config['libreoffice'])
            && is_file((string) $config['libreoffice'])
        ) {
            return (string) $config['libreoffice'];
        }
    }

    $candidates = [
        'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
        'C:\\Program Files\\LibreOffice\\program\\soffice.com',
        'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
        'C:\\Program Files\\LibreOffice 25\\program\\soffice.exe',
        'C:\\Program Files\\LibreOffice 24\\program\\soffice.exe',
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

    foreach ($candidates as $candidate) {
        $candidate = trim((string) $candidate);

        if ($candidate === '') {
            continue;
        }

        $firstLine = preg_split('/\r\n|\r|\n/', $candidate)[0] ?? $candidate;
        $firstLine = trim($firstLine);

        if ($firstLine !== '' && is_file($firstLine)) {
            return $firstLine;
        }
    }

    return null;
}

/**
 * Executa um processo sem shell e preserva o código de saída obtido
 * por proc_get_status(), evitando depender exclusivamente de proc_close().
 */
function runProcess(array $command, string $workingDirectory, int $timeout = 180): array
{
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open(
        $command,
        $descriptors,
        $pipes,
        $workingDirectory,
        null,
        ['bypass_shell' => true]
    );

    if (!is_resource($process)) {
        return [
            'exit' => -1,
            'stdout' => '',
            'stderr' => 'Não foi possível iniciar o LibreOffice.',
        ];
    }

    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    $stdout = '';
    $stderr = '';
    $exitCode = null;
    $startedAt = microtime(true);

    while (true) {
        $status = proc_get_status($process);

        $stdout .= stream_get_contents($pipes[1]) ?: '';
        $stderr .= stream_get_contents($pipes[2]) ?: '';

        if (!$status['running']) {
            $exitCode = (int) $status['exitcode'];
            break;
        }

        if ((microtime(true) - $startedAt) > $timeout) {
            proc_terminate($process);

            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);

            return [
                'exit' => -1,
                'stdout' => $stdout,
                'stderr' => trim($stderr . ' Tempo limite excedido na conversão.'),
            ];
        }

        usleep(150000);
    }

    $stdout .= stream_get_contents($pipes[1]) ?: '';
    $stderr .= stream_get_contents($pipes[2]) ?: '';

    fclose($pipes[1]);
    fclose($pipes[2]);

    $closeCode = proc_close($process);

    if ($exitCode === null || $exitCode < 0) {
        $exitCode = $closeCode;
    }

    return [
        'exit' => $exitCode,
        'stdout' => $stdout,
        'stderr' => $stderr,
    ];
}

function cleanupDir(string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }

    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $directory,
                FilesystemIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
    } catch (Throwable $exception) {
        error_log('SiteTools cleanup: ' . $exception->getMessage());
    }

    @rmdir($directory);
}

/**
 * Verifica a estrutura ZIP e se o documento contém texto ou conteúdo visual.
 */
function validateDocx(string $filePath, ?string &$reason = null): bool
{
    $reason = null;

    if (!class_exists(ZipArchive::class)) {
        $reason = 'A extensão PHP ZIP não está habilitada.';
        return false;
    }

    $zip = new ZipArchive();
    $opened = $zip->open($filePath);

    if ($opened !== true) {
        $reason = 'O arquivo gerado não é um pacote DOCX/ZIP válido.';
        return false;
    }

    $requiredEntries = [
        '[Content_Types].xml',
        '_rels/.rels',
        'word/document.xml',
    ];

    foreach ($requiredEntries as $entry) {
        if ($zip->locateName($entry) === false) {
            $zip->close();
            $reason = 'O documento Word está incompleto: falta ' . $entry . '.';
            return false;
        }
    }

    $documentXml = $zip->getFromName('word/document.xml');

    if ($documentXml === false || trim($documentXml) === '') {
        $zip->close();
        $reason = 'O documento Word não possui conteúdo XML.';
        return false;
    }

    $hasText = false;
    $hasVisualContent = false;

    if (class_exists(DOMDocument::class)) {
        $previous = libxml_use_internal_errors(true);

        $document = new DOMDocument();
        $xmlLoaded = $document->loadXML($documentXml, LIBXML_NONET | LIBXML_NOBLANKS);

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$xmlLoaded) {
            $zip->close();
            $reason = 'O XML principal do documento Word está inválido.';
            return false;
        }

        $wordNamespace = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
        $textNodes = $document->getElementsByTagNameNS($wordNamespace, 't');

        foreach ($textNodes as $textNode) {
            if (trim($textNode->textContent) !== '') {
                $hasText = true;
                break;
            }
        }

        $xpath = new DOMXPath($document);
        $visualNodes = $xpath->query(
            '//*[local-name()="drawing" or local-name()="pict" or local-name()="object"]'
        );

        $hasVisualContent = $visualNodes !== false && $visualNodes->length > 0;
    } else {
        $hasText = preg_match('/<w:t(?:\s[^>]*)?>\s*[^<\s][\s\S]*?<\/w:t>/', $documentXml) === 1;
        $hasVisualContent = preg_match('/<(?:wp:)?(?:inline|anchor)|<w:pict|<w:object/', $documentXml) === 1;
    }

    if (!$hasVisualContent) {
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entryName = $zip->getNameIndex($index);

            if (
                is_string($entryName)
                && str_starts_with($entryName, 'word/media/')
                && !str_ends_with($entryName, '/')
            ) {
                $hasVisualContent = true;
                break;
            }
        }
    }

    $zip->close();

    if (!$hasText && !$hasVisualContent) {
        $reason = 'O DOCX foi criado, mas não contém texto nem imagens. O PDF pode ser digitalizado ou o filtro de importação não conseguiu extrair seu conteúdo.';
        return false;
    }

    return true;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    respondError('Método não permitido.', 405);
}

if (!isset($_FILES['pdf'])) {
    respondError('Selecione um arquivo PDF.');
}

$file = $_FILES['pdf'];
$uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

if ($uploadError !== UPLOAD_ERR_OK) {
    $messages = [
        UPLOAD_ERR_INI_SIZE => 'O arquivo excede o limite do PHP (upload_max_filesize).',
        UPLOAD_ERR_FORM_SIZE => 'O arquivo excede o limite do formulário.',
        UPLOAD_ERR_PARTIAL => 'Upload incompleto. Tente novamente.',
        UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi enviado.',
        UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária do PHP não encontrada.',
        UPLOAD_ERR_CANT_WRITE => 'Falha ao gravar o arquivo no servidor.',
        UPLOAD_ERR_EXTENSION => 'Uma extensão do PHP bloqueou o upload.',
    ];

    respondError($messages[$uploadError] ?? 'Falha no upload do arquivo.');
}

$fileSize = (int) ($file['size'] ?? 0);

if ($fileSize <= 0 || $fileSize > PDF_MAX_SIZE) {
    respondError('O PDF deve ter até 20 MB.');
}

$temporaryFile = (string) ($file['tmp_name'] ?? '');

if ($temporaryFile === '' || !is_uploaded_file($temporaryFile)) {
    respondError('Arquivo temporário de upload inválido.');
}

$handle = fopen($temporaryFile, 'rb');
$pdfHeader = $handle ? (fread($handle, 1024) ?: '') : '';

if (is_resource($handle)) {
    fclose($handle);
}

if (strpos($pdfHeader, '%PDF-') === false) {
    respondError('O arquivo enviado não possui uma assinatura PDF válida.');
}

if (!function_exists('proc_open')) {
    respondError('O servidor não permite executar o conversor (proc_open desabilitado).', 500);
}

$soffice = findLibreOffice();

if ($soffice === null) {
    respondError(
        'LibreOffice não encontrado. Verifique o caminho configurado em config/converters.php.',
        500
    );
}

$workDirectory = sys_get_temp_dir()
    . DIRECTORY_SEPARATOR
    . 'sitetools_pdfword_'
    . bin2hex(random_bytes(8));

if (!mkdir($workDirectory, 0700)) {
    respondError('Não foi possível criar o diretório temporário.', 500);
}

$inputPath = $workDirectory . DIRECTORY_SEPARATOR . 'input.pdf';
$outputPath = $workDirectory . DIRECTORY_SEPARATOR . 'input.docx';

if (!copy($temporaryFile, $inputPath)) {
    cleanupDir($workDirectory);
    respondError('Falha ao preparar o PDF para conversão.', 500);
}

$profilePath = $workDirectory . DIRECTORY_SEPARATOR . 'lo-profile';
$profileUri = 'file:///' . str_replace('\\', '/', $profilePath);

$baseCommand = [
    $soffice,
    '--headless',
    '--nologo',
    '--nodefault',
    '--nolockcheck',
    '--norestore',
    '--nofirststartwizard',
    '-env:UserInstallation=' . $profileUri,
    '--infilter=writer_pdf_import',
];

$attempts = [
    array_merge($baseCommand, [
        '--convert-to',
        'docx',
        '--outdir',
        $workDirectory,
        $inputPath,
    ]),
    array_merge($baseCommand, [
        '--convert-to',
        'docx:Office Open XML Text',
        '--outdir',
        $workDirectory,
        $inputPath,
    ]),
];

$result = null;
$validationReason = null;
$validDocx = false;

foreach ($attempts as $attemptIndex => $command) {
    if (is_file($outputPath)) {
        @unlink($outputPath);
    }

    $result = runProcess($command, $workDirectory, 180);

    if (is_file($outputPath) && filesize($outputPath) > 100) {
        if (validateDocx($outputPath, $validationReason)) {
            $validDocx = true;
            break;
        }

        error_log(
            'SiteTools PDF->Word tentativa '
            . ($attemptIndex + 1)
            . ': '
            . ($validationReason ?? 'DOCX inválido.')
        );
    }
}

if (!$validDocx) {
    $details = trim(
        ($result['stderr'] ?? '')
        . ' '
        . ($result['stdout'] ?? '')
    );

    error_log(
        'SiteTools PDF->Word falhou. '
        . 'Validação: '
        . ($validationReason ?? 'arquivo DOCX não encontrado.')
        . ' Processo: '
        . $details
    );

    cleanupDir($workDirectory);

    if (stripos($details, 'timeout') !== false || stripos($details, 'Tempo limite') !== false) {
        respondError('A conversão demorou demais. Tente um PDF menor ou com menos páginas.', 500);
    }

    if ($validationReason !== null) {
        respondError($validationReason, 422);
    }

    respondError(
        'Não foi possível converter este PDF. Verifique se ele está protegido por senha e se o LibreOffice consegue abri-lo.',
        500
    );
}

$originalName = pathinfo(
    basename((string) ($file['name'] ?? 'documento')),
    PATHINFO_FILENAME
);

$downloadName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $originalName) ?: 'documento';

$docxContent = file_get_contents($outputPath);
cleanupDir($workDirectory);

if ($docxContent === false || strlen($docxContent) < 100) {
    respondError('Falha ao ler o documento Word convertido.', 500);
}

/* Remove qualquer saída anterior para não corromper o arquivo baixado. */
while (ob_get_level() > 0) {
    if (!@ob_end_clean()) {
        break;
    }
}

header('Content-Type: ' . DOCX_MIME);
header('Content-Disposition: attachment; filename="' . $downloadName . '.docx"');
header('Content-Length: ' . strlen($docxContent));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, private');

echo $docxContent;
exit;
