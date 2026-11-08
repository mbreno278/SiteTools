<?php

declare(strict_types=1);

/**
 * SiteTools — Separar PDF.
 * Mantém o contrato multipart (pdf, mode, range) e o download direto.
 */

require __DIR__ . '/_csrf_guard.php';

require __DIR__ . '/_tool_access_guard.php';
tool_access_guard_for('separar-pdf');

require __DIR__ . '/_process_helpers.php';

use App\Controllers\DocumentController;
use App\Exceptions\AppException;
use App\Exceptions\ValidationException;

function separar_pdf_respond_error(string $message, int $status = 400): never
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

const SEPARAR_PDF_MAX_SIZE = 30 * 1024 * 1024;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    separar_pdf_respond_error('Método não permitido.', 405);
}

if (!isset($_FILES['pdf']) || !is_array($_FILES['pdf'])) {
    separar_pdf_respond_error('Selecione um arquivo PDF.', 422);
}

$mode = (string) ($_POST['mode'] ?? 'each');
$range = trim((string) ($_POST['range'] ?? ''));
$startedAt = hrtime(true);

try {
    $controller = new DocumentController();
    $result = $controller->splitPdf(
        $_FILES['pdf'],
        $mode,
        $range,
        SEPARAR_PDF_MAX_SIZE
    );

    $processingMs = (int) ((hrtime(true) - $startedAt) / 1_000_000);
    $toolId = null;

    try {
        $toolId = process_tool_id(app_pdo(), 'separar-pdf');
    } catch (Throwable) {
        // A falha ao consultar o histórico não deve descartar o arquivo pronto.
    }

    $originalName = basename((string) ($_FILES['pdf']['name'] ?? 'documento.pdf'));
    $baseName = pathinfo($originalName, PATHINFO_FILENAME);
    $safeBaseName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $baseName) ?: 'documento';

    if (($result['mime'] ?? '') === 'application/zip') {
        $downloadName = $safeBaseName . '-paginas.zip';
    } else {
        $downloadName = $safeBaseName . '-pagina.pdf';
    }

    // Mantém o workspace vivo até o término do envio do arquivo.
    $workspace = $result['workspace'];

    process_send_file(
        $result['path'],
        $downloadName,
        $result['mime'] ?? 'application/pdf',
        'separar-pdf',
        $toolId,
        $processingMs
    );
} catch (ValidationException $e) {
    separar_pdf_respond_error($e->getMessage(), 422);
} catch (AppException $e) {
    separar_pdf_respond_error($e->getMessage(), $e->getStatusCode());
} catch (Throwable $e) {
    error_log('[separar-pdf] ' . $e->getMessage());
    separar_pdf_respond_error('Não foi possível separar este PDF. Tente novamente.', 500);
}
