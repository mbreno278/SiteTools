<?php

declare(strict_types=1);

/**
 * SiteTools — Conversor Word para PDF.
 * O endpoint trata HTTP/segurança; o DocumentController orquestra a conversão.
 */

require __DIR__ . '/_csrf_guard.php';

require __DIR__ . '/_tool_access_guard.php';
tool_access_guard_for('word-to-pdf');

require __DIR__ . '/_process_helpers.php';

/**
 * Preserva o contrato JSON esperado pelo frontend: { "error": "..." }.
 */
function word_to_pdf_respond_error(string $message, int $status = 400): never
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
    }

    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

const WORD_TO_PDF_MAX_SIZE = 20 * 1024 * 1024;
const WORD_TO_PDF_MIME = 'application/pdf';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    word_to_pdf_respond_error('Método não permitido.', 405);
}

if (!isset($_FILES['word']) || !is_array($_FILES['word'])) {
    word_to_pdf_respond_error('Selecione um arquivo Word.', 422);
}

$file = $_FILES['word'];
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

    word_to_pdf_respond_error(
        $messages[$uploadError] ?? 'Falha no upload do arquivo.',
        422
    );
}

$fileSize = (int) ($file['size'] ?? 0);
if ($fileSize <= 0 || $fileSize > WORD_TO_PDF_MAX_SIZE) {
    word_to_pdf_respond_error('O arquivo deve ter até 20 MB.', 422);
}

$temporaryFile = (string) ($file['tmp_name'] ?? '');
if ($temporaryFile === '' || !is_uploaded_file($temporaryFile)) {
    word_to_pdf_respond_error('Arquivo temporário de upload inválido.', 422);
}

$originalName = basename((string) ($file['name'] ?? 'document.docx'));
$startedAt = hrtime(true);

try {
    $controller = new \App\Controllers\DocumentController();
    $result = $controller->wordToPdf(
        $temporaryFile,
        $originalName,
        WORD_TO_PDF_MAX_SIZE
    );

    $processingMs = (int) ((hrtime(true) - $startedAt) / 1_000_000);
    $toolId = null;

    try {
        $toolId = process_tool_id(app_pdo(), 'word-to-pdf');
    } catch (Throwable) {
        // Não descartar um PDF pronto por falha na consulta do histórico.
    }

    $baseName = pathinfo($originalName, PATHINFO_FILENAME);
    $safeBaseName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $baseName) ?: 'documento';
    $downloadName = $safeBaseName . '.pdf';

    // Mantém o workspace vivo durante o envio do arquivo.
    $workspace = $result['workspace'];

    process_send_file(
        $result['path'],
        $downloadName,
        $result['mime'] ?? WORD_TO_PDF_MIME,
        'word-to-pdf',
        $toolId,
        $processingMs
    );
} catch (\App\Exceptions\ValidationException $e) {
    word_to_pdf_respond_error($e->getMessage(), 422);
} catch (\App\Exceptions\AppException $e) {
    word_to_pdf_respond_error($e->getMessage(), $e->getStatusCode());
} catch (Throwable $e) {
    error_log('[word-to-pdf] ' . $e->getMessage());
    word_to_pdf_respond_error('Não foi possível converter o documento.', 500);
}
