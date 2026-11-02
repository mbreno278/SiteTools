<?php

declare(strict_types=1);

require __DIR__ . '/_csrf_guard.php';


require __DIR__ . '/pdf-common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    pdfRespondError('Método não permitido.', 405);
}

if (empty($_FILES['pdfs'])) {
    pdfRespondError('Selecione pelo menos 2 arquivos PDF.');
}

$gs = pdfFindGs();
if ($gs === null) {
    pdfRespondError('Ghostscript não encontrado. Instale o Ghostscript e adicione no PATH.', 500);
}

// Normaliza múltiplos uploads
$files = [];
$names = $_FILES['pdfs']['name'] ?? [];
$tmps  = $_FILES['pdfs']['tmp_name'] ?? [];
$errs  = $_FILES['pdfs']['error'] ?? [];
$sizes = $_FILES['pdfs']['size'] ?? [];

if (!is_array($names)) {
    pdfRespondError('Envie múltiplos arquivos.');
}

$count = count($names);
if ($count < 2) {
    pdfRespondError('Selecione pelo menos 2 arquivos PDF.');
}
if ($count > 20) {
    pdfRespondError('Máximo de 20 arquivos por vez.');
}

$dir = pdfTmpDir();
$inputs = [];

for ($i = 0; $i < $count; $i++) {
    $file = [
        'name' => $names[$i],
        'tmp_name' => $tmps[$i],
        'error' => $errs[$i],
        'size' => $sizes[$i],
    ];
    $tmp = pdfValidateUpload($file);
    $dest = $dir . DIRECTORY_SEPARATOR . sprintf('in_%02d.pdf', $i);
    if (!copy($tmp, $dest)) {
        pdfCleanup($dir);
        pdfRespondError('Falha ao preparar os arquivos.', 500);
    }
    $inputs[] = $dest;
}

$output = $dir . DIRECTORY_SEPARATOR . 'merged.pdf';

$cmd = array_merge(
    [
        $gs,
        '-sDEVICE=pdfwrite',
        '-dSAFER',
        '-dNOPAUSE',
        '-dQUIET',
        '-dBATCH',
        '-sOutputFile=' . $output,
    ],
    $inputs
);

$result = pdfRun($cmd, 180);

if ($result['exit'] !== 0 || !is_file($output)) {
    pdfCleanup($dir);
    pdfRespondError('Não foi possível unir os PDFs. Verifique se algum arquivo está protegido ou corrompido.');
}

pdfSendFile($output, 'pdfs-unidos.pdf');
pdfCleanup($dir);
