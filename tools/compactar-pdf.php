<?php

declare(strict_types=1);

require __DIR__ . '/_csrf_guard.php';

require __DIR__ . '/_tool_access_guard.php';
tool_access_guard_for('compactar-pdf');



require __DIR__ . '/pdf-common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    pdfRespondError('Método não permitido.', 405);
}

if (!isset($_FILES['pdf'])) {
    pdfRespondError('Selecione um arquivo PDF.');
}

$gs = pdfFindGs();
if ($gs === null) {
    pdfRespondError('Ghostscript não encontrado. Instale o Ghostscript e adicione no PATH.', 500);
}

$tmpPath = pdfValidateUpload($_FILES['pdf']);
$quality = (string) ($_POST['quality'] ?? 'ebook');
$allowed = ['screen', 'ebook', 'printer', 'prepress'];
if (!in_array($quality, $allowed, true)) {
    $quality = 'ebook';
}

$dir = pdfTmpDir();
$input = $dir . DIRECTORY_SEPARATOR . 'input.pdf';
$output = $dir . DIRECTORY_SEPARATOR . 'output.pdf';

if (!copy($tmpPath, $input)) {
    pdfCleanup($dir);
    pdfRespondError('Falha ao preparar o arquivo.', 500);
}

$cmd = [
    $gs,
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

$result = pdfRun($cmd, 180);

if ($result['exit'] !== 0 || !is_file($output)) {
    pdfCleanup($dir);
    pdfRespondError('Não foi possível compactar o PDF. Verifique se o arquivo não está corrompido ou protegido.');
}

$originalName = pathinfo($_FILES['pdf']['name'] ?? 'documento', PATHINFO_FILENAME);
$safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $originalName) ?: 'documento';

pdfSendFile($output, $safeName . '-compactado.pdf');
pdfCleanup($dir);
