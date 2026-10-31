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

$password = (string) ($_POST['password'] ?? '');
if ($password === '') {
    pdfRespondError('Informe a senha do PDF.');
}

$tmpPath = pdfValidateUpload($_FILES['pdf']);
$dir = pdfTmpDir();
$input = $dir . DIRECTORY_SEPARATOR . 'input.pdf';
$output = $dir . DIRECTORY_SEPARATOR . 'unlocked.pdf';

if (!copy($tmpPath, $input)) {
    pdfCleanup($dir);
    pdfRespondError('Falha ao preparar o arquivo.', 500);
}

$qpdf = pdfFindQpdf();
$gs = pdfFindGs();
$ok = false;

// 1) Preferência: qpdf (validação real de senha)
if ($qpdf !== null) {
    $cmd = [
        $qpdf,
        '--password=' . $password,
        '--decrypt',
        $input,
        $output,
    ];

    $result = pdfRun($cmd, 120);
    $stderr = ($result['stderr'] ?? '') . ($result['stdout'] ?? '');

    if ($result['exit'] === 0 && is_file($output) && filesize($output) > 200) {
        $ok = true;
    } else {
        // Senha errada ou arquivo não criptografado da forma esperada
        if (
            stripos($stderr, 'invalid password') !== false
            || stripos($stderr, 'incorrect password') !== false
            || stripos($stderr, 'password') !== false
        ) {
            pdfCleanup($dir);
            pdfRespondError('Senha incorreta.');
        }
    }
}

// 2) Fallback: Ghostscript (com validação rigorosa da saída)
if (!$ok && $gs !== null) {
    // Remove saída anterior inválida
    if (is_file($output)) {
        @unlink($output);
    }

    $cmd = [
        $gs,
        '-sDEVICE=pdfwrite',
        '-dNOPAUSE',
        '-dQUIET',
        '-dBATCH',
        '-dSAFER',
        '-sPDFPassword=' . $password,
        '-sOutputFile=' . $output,
        $input,
    ];

    $result = pdfRun($cmd, 180);
    $stderr = ($result['stderr'] ?? '') . ($result['stdout'] ?? '');

    if (
        stripos($stderr, 'Password incorrect') !== false
        || stripos($stderr, 'Invalid password') !== false
        || stripos($stderr, 'This file requires a password') !== false
        || stripos($stderr, 'error') !== false && stripos($stderr, 'password') !== false
    ) {
        pdfCleanup($dir);
        pdfRespondError('Senha incorreta.');
    }

    // Ghostscript pode sair com código 0 e gerar 1 página em branco com senha errada.
    // Rejeita se a saída for suspeitamente pequena em relação ao original.
    if ($result['exit'] === 0 && is_file($output)) {
        $inSize = (int) filesize($input);
        $outSize = (int) filesize($output);

        if ($outSize < 200) {
            pdfCleanup($dir);
            pdfRespondError('Senha incorreta ou não foi possível desbloquear o PDF.');
        }

        // Original grande + saída minúscula = falha típica de senha errada no GS
        if ($inSize > 50_000 && $outSize < max(10_000, (int) ($inSize * 0.08))) {
            pdfCleanup($dir);
            pdfRespondError('Senha incorreta ou o PDF não pôde ser desbloqueado corretamente.');
        }

        $ok = true;
    }
}

if (!$ok) {
    pdfCleanup($dir);
    if ($qpdf === null && $gs === null) {
        pdfRespondError('Nenhuma ferramenta encontrada. Instale o qpdf (recomendado) ou o Ghostscript.', 500);
    }
    pdfRespondError('Não foi possível desbloquear o PDF. Verifique a senha.');
}

// Validação final extra
pdfAssertSaneOutput($input, $output, 'Senha incorreta ou o PDF não pôde ser desbloqueado corretamente.');

$originalName = pathinfo($_FILES['pdf']['name'] ?? 'documento', PATHINFO_FILENAME);
$safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $originalName) ?: 'documento';

pdfSendFile($output, $safeName . '-desbloqueado.pdf');
pdfCleanup($dir);
