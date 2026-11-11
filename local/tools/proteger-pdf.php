<?php

declare(strict_types=1);

/**
 * SiteTools — Proteger PDF.
 * Mantém o contrato multipart (pdf, password) e o download direto.
 */

require __DIR__ . '/_csrf_guard.php';

require __DIR__ . '/_tool_access_guard.php';
tool_access_guard_for('proteger-pdf');

require __DIR__ . '/_process_helpers.php';

use App\Controllers\DocumentController;
use App\Exceptions\AppException;
use App\Exceptions\ValidationException;

function proteger_pdf_respond_error(string $message, int $status = 400): never
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

const PROTEGER_PDF_MAX_SIZE = 30 * 1024 * 1024;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    proteger_pdf_respond_error('Método não permitido.', 405);
}

if (!isset($_FILES['pdf']) || !is_array($_FILES['pdf'])) {
    proteger_pdf_respond_error('Selecione um arquivo PDF.', 422);
}

$password = (string) ($_POST['password'] ?? '');
$startedAt = hrtime(true);

try {
    $controller = new DocumentController();
    $result = $controller->protectPdf(
        $_FILES['pdf'],
        $password,
        PROTEGER_PDF_MAX_SIZE
    );

    $processingMs = (int) ((hrtime(true) - $startedAt) / 1_000_000);
    $toolId = null;

    try {
        $toolId = process_tool_id(app_pdo(), 'proteger-pdf');
    } catch (Throwable) {
        // A falha ao consultar o histórico não deve descartar o arquivo pronto.
    }

    $originalName = basename((string) ($_FILES['pdf']['name'] ?? 'documento.pdf'));
    $baseName = pathinfo($originalName, PATHINFO_FILENAME);
    $safeBaseName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $baseName) ?: 'documento';
    $downloadName = $safeBaseName . '-protegido.pdf';

    // Mantém o workspace vivo até o término do envio do arquivo.
    $workspace = $result['workspace'];

    process_send_file(
        $result['path'],
        $downloadName,
        $result['mime'] ?? 'application/pdf',
        'proteger-pdf',
        $toolId,
        $processingMs
    );
} catch (ValidationException $e) {
    proteger_pdf_respond_error($e->getMessage(), 422);
} catch (AppException $e) {
    proteger_pdf_respond_error($e->getMessage(), $e->getStatusCode());
} catch (Throwable $e) {
    error_log('[proteger-pdf] ' . $e->getMessage());
    proteger_pdf_respond_error('Não foi possível proteger este PDF. Tente novamente.', 500);
}
