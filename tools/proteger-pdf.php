<?php

declare(strict_types=1);

require __DIR__ . '/_csrf_guard.php';

require __DIR__ . '/_tool_access_guard.php';
tool_access_guard_for('proteger-pdf');



require __DIR__ . '/pdf-common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    pdfRespondError('Método não permitido.', 405);
}

if (!isset($_FILES['pdf'])) {
    pdfRespondError('Selecione um arquivo PDF.');
}

$password = pdfSanitizePassword((string) ($_POST['password'] ?? ''));
if (strlen($password) < 3) {
    pdfRespondError('A senha deve ter pelo menos 3 caracteres.');
}

$tmpPath = pdfValidateUpload($_FILES['pdf']);
$dir = pdfTmpDir();
$input = $dir . DIRECTORY_SEPARATOR . 'input.pdf';
$output = $dir . DIRECTORY_SEPARATOR . 'protected.pdf';

if (!copy($tmpPath, $input)) {
    pdfCleanup($dir);
    pdfRespondError('Falha ao preparar o arquivo.', 500);
}

$qpdf = pdfFindQpdf();
$gs = pdfFindGs();
$ok = false;

// 1) qpdf — proteção padrão (AES), compatível com leitores comuns
if ($qpdf !== null) {
    $cmd = [
        $qpdf,
        '--encrypt',
        $password,  // user password
        $password,  // owner password
        '256',
        '--',
        $input,
        $output,
    ];

    $result = pdfRun($cmd, 120);

    if ($result['exit'] === 0 && is_file($output) && filesize($output) > 200) {
        $ok = true;
    }
}

// 2) Fallback Ghostscript
if (!$ok && $gs !== null) {
    if (is_file($output)) {
        @unlink($output);
    }

    $cmd = [
        $gs,
        '-sDEVICE=pdfwrite',
        '-dSAFER',
        '-dNOPAUSE',
        '-dQUIET',
        '-dBATCH',
        '-dCompatibilityLevel=1.4',
        '-sOwnerPassword=' . $password,
        '-sUserPassword=' . $password,
        '-dEncryptionR=3',
        '-dKeyLength=128',
        '-dPermissions=-4',
        '-sOutputFile=' . $output,
        $input,
    ];

    $result = pdfRun($cmd, 120);

    if ($result['exit'] === 0 && is_file($output) && filesize($output) > 200) {
        // Garante que não gerou PDF “vazio”
        pdfAssertSaneOutput($input, $output, 'Não foi possível proteger o PDF corretamente.');
        $ok = true;
    }
}

if (!$ok) {
    pdfCleanup($dir);
    if ($qpdf === null && $gs === null) {
        pdfRespondError('Nenhuma ferramenta encontrada. Instale o qpdf (recomendado) ou o Ghostscript.', 500);
    }
    pdfRespondError('Não foi possível proteger o PDF. Verifique se o arquivo já não está protegido ou corrompido.');
}

$originalName = pathinfo($_FILES['pdf']['name'] ?? 'documento', PATHINFO_FILENAME);
$safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $originalName) ?: 'documento';

pdfSendFile($output, $safeName . '-protegido.pdf');
pdfCleanup($dir);
