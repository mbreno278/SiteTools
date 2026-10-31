<?php

declare(strict_types=1);

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

$mode = (string) ($_POST['mode'] ?? 'each');
$range = trim((string) ($_POST['range'] ?? ''));

$tmpPath = pdfValidateUpload($_FILES['pdf']);
$dir = pdfTmpDir();
$input = $dir . DIRECTORY_SEPARATOR . 'input.pdf';

if (!copy($tmpPath, $input)) {
    pdfCleanup($dir);
    pdfRespondError('Falha ao preparar o arquivo.', 500);
}

// Conta páginas
$probe = pdfRun([
    $gs,
    '-q',
    '-dNODISPLAY',
    '-c',
    '(' . str_replace('\\', '/', $input) . ') (r) file runpdfbegin pdfpagecount = quit',
], 60);

$pageCount = (int) trim($probe['stdout']);
if ($pageCount < 1) {
    // Fallback alternativo
    $probe2 = pdfRun([$gs, '-q', '-dNODISPLAY', '-dNOSAFER', '-c', '<< /Name  /GetPageCount >> setpagedevice', $input], 30);
    $pageCount = max(1, (int) preg_replace('/\D/', '', $probe['stderr'] . $probe['stdout']));
}

if ($pageCount < 1) {
    $pageCount = 50; // tenta até 50 se não conseguir contar
}

$pagesToExtract = [];

if ($mode === 'range' && $range !== '') {
    // Parse: 1-3,5,7-9
    foreach (explode(',', $range) as $part) {
        $part = trim($part);
        if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $part, $m)) {
            $a = (int) $m[1];
            $b = (int) $m[2];
            if ($a > $b) {
                [$a, $b] = [$b, $a];
            }
            for ($p = $a; $p <= $b; $p++) {
                $pagesToExtract[] = $p;
            }
        } elseif (preg_match('/^\d+$/', $part)) {
            $pagesToExtract[] = (int) $part;
        }
    }
    $pagesToExtract = array_values(array_unique(array_filter($pagesToExtract, static fn($p) => $p >= 1)));
} else {
    // Uma página por arquivo
    for ($p = 1; $p <= min($pageCount, 100); $p++) {
        $pagesToExtract[] = $p;
    }
}

if (empty($pagesToExtract)) {
    pdfCleanup($dir);
    pdfRespondError('Informe um intervalo de páginas válido (ex: 1-3 ou 1,3,5).');
}

$outFiles = [];
foreach ($pagesToExtract as $p) {
    $out = $dir . DIRECTORY_SEPARATOR . sprintf('page_%03d.pdf', $p);
    $cmd = [
        $gs,
        '-sDEVICE=pdfwrite',
        '-dNOPAUSE',
        '-dQUIET',
        '-dBATCH',
        '-dFirstPage=' . $p,
        '-dLastPage=' . $p,
        '-sOutputFile=' . $out,
        $input,
    ];
    $result = pdfRun($cmd, 60);
    if ($result['exit'] === 0 && is_file($out) && filesize($out) > 50) {
        $outFiles[] = $out;
    }
}

if (empty($outFiles)) {
    pdfCleanup($dir);
    pdfRespondError('Não foi possível separar o PDF. Verifique se o arquivo não está protegido.');
}

// Se só 1 página, envia PDF; senão ZIP
if (count($outFiles) === 1) {
    pdfSendFile($outFiles[0], 'pagina.pdf');
    pdfCleanup($dir);
}

$zipPath = $dir . DIRECTORY_SEPARATOR . 'paginas.zip';
$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    pdfCleanup($dir);
    pdfRespondError('Não foi possível criar o arquivo ZIP.', 500);
}

foreach ($outFiles as $f) {
    $zip->addFile($f, basename($f));
}
$zip->close();

pdfSendZip($zipPath, 'pdf-separado.zip');
pdfCleanup($dir);
