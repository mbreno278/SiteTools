<?php

declare(strict_types=1);

require __DIR__ . '/_csrf_guard.php';

require __DIR__ . '/_tool_access_guard.php';
tool_access_guard_for('separar-pdf');



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

// Separação segura com -dSAFER (sem -dNOSAFER e sem -c PostScript arbitrário)
$pagesToExtract = [];

if ($mode === 'range' && $range !== '') {
    // Parse: 1-3,5,7-9 — apenas números (sem path injection)
    if (!preg_match('/^[0-9,\s\-]+$/', $range)) {
        pdfCleanup($dir);
        pdfRespondError('Intervalo inválido. Use apenas números, vírgulas e hífens (ex: 1-3,5).');
    }
    foreach (explode(',', $range) as $part) {
        $part = trim($part);
        if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $part, $m)) {
            $a = (int) $m[1];
            $b = (int) $m[2];
            if ($a > $b) {
                [$a, $b] = [$b, $a];
            }
            // Limite de páginas por requisição
            if ($b - $a > 100) {
                pdfCleanup($dir);
                pdfRespondError('Intervalo muito grande (máximo 100 páginas por vez).');
            }
            for ($p = $a; $p <= $b; $p++) {
                if ($p >= 1) {
                    $pagesToExtract[] = $p;
                }
            }
        } elseif (preg_match('/^\d+$/', $part)) {
            $p = (int) $part;
            if ($p >= 1) {
                $pagesToExtract[] = $p;
            }
        }
    }
    $pagesToExtract = array_values(array_unique($pagesToExtract));
    sort($pagesToExtract);
} else {
    // Extrai todas as páginas de uma vez (Ghostscript gera page_001.pdf, ...)
    $pattern = $dir . DIRECTORY_SEPARATOR . 'page_%03d.pdf';
    $cmd = array_merge(
        [$gs, '-sDEVICE=pdfwrite'],
        pdfGsSafeArgs(),
        ['-sOutputFile=' . $pattern, $input]
    );
    pdfRun($cmd, 180);

    $generated = glob($dir . DIRECTORY_SEPARATOR . 'page_*.pdf') ?: [];
    sort($generated);
    $pagesToExtract = [];
    foreach ($generated as $i => $f) {
        if (is_file($f) && filesize($f) > 50) {
            $pagesToExtract[] = $i + 1;
        }
    }
}

if (empty($pagesToExtract) && $mode === 'range') {
    pdfCleanup($dir);
    pdfRespondError('Informe um intervalo de páginas válido (ex: 1-3 ou 1,3,5).');
}

$outFiles = [];

if ($mode === 'range') {
    foreach ($pagesToExtract as $p) {
        $out = $dir . DIRECTORY_SEPARATOR . sprintf('page_%03d.pdf', $p);
        $cmd = array_merge(
            [$gs, '-sDEVICE=pdfwrite'],
            pdfGsSafeArgs(),
            [
                '-dFirstPage=' . $p,
                '-dLastPage=' . $p,
                '-sOutputFile=' . $out,
                $input,
            ]
        );
        $result = pdfRun($cmd, 60);
        if ($result['exit'] === 0 && is_file($out) && filesize($out) > 50) {
            $outFiles[] = $out;
        }
    }
} else {
    $generated = glob($dir . DIRECTORY_SEPARATOR . 'page_*.pdf') ?: [];
    sort($generated);
    foreach ($generated as $f) {
        if (is_file($f) && filesize($f) > 50) {
            $outFiles[] = $f;
        }
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
