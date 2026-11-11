<?php

declare(strict_types=1);

/**
 * SiteTools — Conversor PDF para Word (DOCX).
 * A conversão e a validação do DOCX ficam na camada de aplicação.
 */

require __DIR__ . '/_csrf_guard.php';

require __DIR__ . '/_tool_access_guard.php';
tool_access_guard_for('pdf-to-word');

require __DIR__ . '/_process_helpers.php';

use App\Controllers\DocumentController;
use App\Exceptions\AppException;
use App\Exceptions\ValidationException;

/**
 * Mantém o contrato JSON legado consumido por assets/js/pdf-to-word.js.
 */
function pdf_to_word_respond_error(string $message, int $status = 400): never
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

const PDF_TO_WORD_MAX_SIZE = 20 * 1024 * 1024;
const PDF_TO_WORD_MIME = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    pdf_to_word_respond_error('Método não permitido.', 405);
}

if (!isset($_FILES['pdf']) || !is_array($_FILES['pdf'])) {
    pdf_to_word_respond_error('Selecione um arquivo PDF.');
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

    pdf_to_word_respond_error(
        $messages[$uploadError] ?? 'Falha no upload do arquivo.',
        422
    );
}

$fileSize = (int) ($file['size'] ?? 0);
if ($fileSize <= 0 || $fileSize > PDF_TO_WORD_MAX_SIZE) {
    pdf_to_word_respond_error('O PDF deve ter até 20 MB.', 422);
}

$temporaryFile = (string) ($file['tmp_name'] ?? '');
if ($temporaryFile === '' || !is_uploaded_file($temporaryFile)) {
    pdf_to_word_respond_error('Arquivo temporário de upload inválido.', 422);
}

$originalName = basename((string) ($file['name'] ?? 'documento.pdf'));
$startedAt = hrtime(true);

try {
    $controller = new DocumentController();
    $result = $controller->pdfToWord(
        $temporaryFile,
        $originalName,
        PDF_TO_WORD_MAX_SIZE
    );

    $processingMs = (int) ((hrtime(true) - $startedAt) / 1_000_000);
    $toolId = null;

    try {
        $toolId = process_tool_id(app_pdo(), 'pdf-to-word');
    } catch (Throwable) {
        // A falha ao consultar o histórico não deve descartar a conversão concluída.
    }

    $baseName = pathinfo($originalName, PATHINFO_FILENAME);
    $safeBaseName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $baseName) ?: 'documento';
    $downloadName = $safeBaseName . '.docx';

    process_send_file(
        $result['path'],
        $downloadName,
        $result['mime'] ?? PDF_TO_WORD_MIME,
        'pdf-to-word',
        $toolId,
        $processingMs
    );
} catch (ValidationException $e) {
    pdf_to_word_respond_error($e->getMessage(), 422);
} catch (AppException $e) {
    pdf_to_word_respond_error($e->getMessage(), $e->getStatusCode());
} catch (Throwable $e) {
    error_log('[pdf-to-word] ' . $e->getMessage());
    pdf_to_word_respond_error('Não foi possível converter este PDF. Tente novamente.', 500);
}
