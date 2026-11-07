<?php

declare(strict_types=1);

/**
 * Word → PDF usando DocumentConverterService (LibreOffice headless).
 * Mantém contrato HTTP JSON/arquivo dos handlers legados.
 */

require __DIR__ . '/_csrf_guard.php';
require __DIR__ . '/_tool_access_guard.php';
tool_access_guard_for('word-to-pdf');

require __DIR__ . '/_process_helpers.php';

use App\Exceptions\AppException;
use App\Exceptions\ValidationException;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    Response::error('Método não permitido.', 405);
}

if (empty($_FILES['word'])) {
    Response::error('Selecione um arquivo Word.', 422, null, 'validation_error');
}

$started = hrtime(true);

try {
    $service = process_document_service();
    $result = $service->wordToPdf(
        (string) $_FILES['word']['tmp_name'],
        (string) ($_FILES['word']['name'] ?? 'document.docx')
    );

    $ms = (int) ((hrtime(true) - $started) / 1_000_000);
    $toolId = null;
    try {
        $pdo = app_pdo();
        $toolId = process_tool_id($pdo, 'word-to-pdf');
    } catch (Throwable) {
    }

    $downloadName = pathinfo((string) ($_FILES['word']['name'] ?? 'document'), PATHINFO_FILENAME) . '.pdf';

    // Envia e registra; workspace é limpo no destruct após readfile? 
    // Precisamos manter workspace vivo até o fim do request.
    $workspace = $result['workspace'];
    process_send_file($result['path'], $downloadName, $result['mime'], 'word-to-pdf', $toolId, $ms);
} catch (ValidationException $e) {
    Response::error($e->getMessage(), 422, null, 'validation_error');
} catch (AppException $e) {
    Response::error($e->getMessage(), $e->getStatusCode(), null, $e->getErrorCode());
} catch (Throwable $e) {
    error_log('[word-to-pdf] ' . $e->getMessage());
    Response::error('Não foi possível converter o documento.', 500, null, 'internal_error');
}
