<?php

declare(strict_types=1);

/**
 * SiteTools — Compactar PDF.
 * Mantém o contrato multipart (pdf, quality) e o download direto do arquivo.
 */

require __DIR__ . '/_csrf_guard.php';

require __DIR__ . '/_tool_access_guard.php';
tool_access_guard_for('compactar-pdf');

require __DIR__ . '/_process_helpers.php';

use App\Controllers\DocumentController;
use App\Exceptions\AppException;
use App\Exceptions\ValidationException;

function compactar_pdf_respond_error(string $message, int $status = 400): never
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

const COMPACTAR_PDF_MAX_SIZE = 30 * 1024 * 1024;
const COMPACTAR_PDF_MIME = 'application/pdf';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    compactar_pdf_respond_error('Método não permitido.', 405);
}

if (!isset($_FILES['pdf']) || !is_array($_FILES['pdf'])) {
    compactar_pdf_respond_error('Selecione um arquivo PDF.', 422);
}

$quality = (string) ($_POST['quality'] ?? 'ebook');
$startedAt = hrtime(true);

try {
    $controller = new DocumentController();
    $result = $controller->compressPdf(
        $_FILES['pdf'],
        $quality,
        COMPACTAR_PDF_MAX_SIZE
    );

    $processingMs = (int) ((hrtime(true) - $startedAt) / 1_000_000);
    $toolId = null;

    try {
        $toolId = process_tool_id(app_pdo(), 'compactar-pdf');
    } catch (Throwable) {
        // A falha ao consultar o histórico não deve descartar o arquivo pronto.
    }

    $originalName = basename((string) ($_FILES['pdf']['name'] ?? 'documento.pdf'));
    $baseName = pathinfo($originalName, PATHINFO_FILENAME);
    $safeBaseName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $baseName) ?: 'documento';
    $downloadName = $safeBaseName . '-compactado.pdf';

    // Mantém o workspace vivo até o término do envio do arquivo.
    $workspace = $result['workspace'];

    process_send_file(
        $result['path'],
        $downloadName,
        $result['mime'] ?? COMPACTAR_PDF_MIME,
        'compactar-pdf',
        $toolId,
        $processingMs
    );
} catch (ValidationException $e) {
    compactar_pdf_respond_error($e->getMessage(), 422);
} catch (AppException $e) {
    compactar_pdf_respond_error($e->getMessage(), $e->getStatusCode());
} catch (Throwable $e) {
    error_log('[compactar-pdf] ' . $e->getMessage());
    compactar_pdf_respond_error('Não foi possível compactar este PDF. Tente novamente.', 500);
}
