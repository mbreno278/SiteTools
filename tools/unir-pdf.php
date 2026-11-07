<?php

declare(strict_types=1);

/**
 * SiteTools — Unir PDF.
 * Mantém o contrato HTTP legado: multipart pdfs[] e download direto do PDF.
 */

require __DIR__ . '/_csrf_guard.php';

require __DIR__ . '/_tool_access_guard.php';
tool_access_guard_for('unir-pdf');

require __DIR__ . '/_process_helpers.php';

use App\Controllers\DocumentController;
use App\Exceptions\AppException;
use App\Exceptions\ValidationException;

function unir_pdf_respond_error(string $message, int $status = 400): never
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

const UNIR_PDF_MAX_FILES = 20;
const UNIR_PDF_MAX_SIZE = 30 * 1024 * 1024;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    unir_pdf_respond_error('Método não permitido.', 405);
}

if (!isset($_FILES['pdfs']) || !is_array($_FILES['pdfs'])) {
    unir_pdf_respond_error('Selecione pelo menos 2 arquivos PDF.', 422);
}

$startedAt = hrtime(true);

try {
    $controller = new DocumentController();
    $result = $controller->mergePdfs(
        $_FILES['pdfs'],
        UNIR_PDF_MAX_FILES,
        UNIR_PDF_MAX_SIZE
    );

    $processingMs = (int) ((hrtime(true) - $startedAt) / 1_000_000);
    $toolId = null;

    try {
        $toolId = process_tool_id(app_pdo(), 'unir-pdf');
    } catch (Throwable) {
        // Não descartar o arquivo pronto por falha na consulta do histórico.
    }

    // A referência mantém o workspace vivo durante o envio.
    $workspace = $result['workspace'];

    process_send_file(
        $result['path'],
        'pdfs-unidos.pdf',
        $result['mime'] ?? 'application/pdf',
        'unir-pdf',
        $toolId,
        $processingMs
    );
} catch (ValidationException $e) {
    unir_pdf_respond_error($e->getMessage(), 422);
} catch (AppException $e) {
    unir_pdf_respond_error($e->getMessage(), $e->getStatusCode());
} catch (Throwable $e) {
    error_log('[unir-pdf] ' . $e->getMessage());
    unir_pdf_respond_error('Não foi possível unir os arquivos PDF.', 500);
}
