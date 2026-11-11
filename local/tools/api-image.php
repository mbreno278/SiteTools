<?php

declare(strict_types=1);

/**
 * Endpoint unificado de imagens (resize | compress | convert | crop).
 * Frontends legados podem apontar action para este arquivo.
 *
 * POST multipart:
 *   action = resize|compress|convert|crop
 *   image/file = arquivo
 *   width, height, keep_ratio, quality, format, x, y, w, h conforme action
 */

require __DIR__ . '/_csrf_guard.php';

$action = strtolower(trim((string) ($_POST['action'] ?? 'resize')));

$slugMap = [
    'resize' => 'redimensionar-imagem',
    'compress' => 'image-compressor',
    'convert' => 'image-converter',
    'crop' => 'image-cropper',
];

// Mantém o comportamento legado: ação ausente ou desconhecida usa resize.
if (!isset($slugMap[$action])) {
    $action = 'resize';
}

$slug = $slugMap[$action];

require __DIR__ . '/_tool_access_guard.php';
tool_access_guard_for($slug);

require __DIR__ . '/_process_helpers.php';

use App\Controllers\ImageController;
use App\Exceptions\AppException;
use App\Exceptions\ValidationException;
use App\Helpers\Response;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    Response::error('Método não permitido.', 405);
}

$file = $_FILES['image'] ?? $_FILES['file'] ?? null;

if (!is_array($file) || empty($file['tmp_name'])) {
    Response::error('Selecione uma imagem.', 422);
}

$started = hrtime(true);

try {
    $controller = new ImageController();

    $result = $controller->process($action, $file, [
        'width' => (int) ($_POST['width'] ?? $_POST['w'] ?? 0),
        'height' => (int) ($_POST['height'] ?? $_POST['h'] ?? 0),
        'keep_ratio' => (($_POST['keep_ratio'] ?? '1') !== '0'),
        'format' => (string) ($_POST['format'] ?? match ($action) {
            'convert' => 'png',
            default => 'jpeg',
        }),
        'quality' => (int) ($_POST['quality'] ?? match ($action) {
            'compress' => 75,
            default => 85,
        }),
        'x' => (int) ($_POST['x'] ?? 0),
        'y' => (int) ($_POST['y'] ?? 0),
    ]);

    $processingMs = (int) ((hrtime(true) - $started) / 1_000_000);

    $toolId = null;
    try {
        $toolId = process_tool_id(app_pdo(), $slug);
    } catch (Throwable) {
        // O processamento não deve falhar apenas porque o histórico não pôde ser consultado.
    }

    $extension = match ($result['mime']) {
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => 'jpg',
    };

    $originalName = pathinfo(
        (string) ($file['name'] ?? 'image'),
        PATHINFO_FILENAME
    );
    $downloadName = ($originalName !== '' ? $originalName : 'image') . '.' . $extension;

    process_send_file(
        $result['path'],
        $downloadName,
        $result['mime'],
        $slug,
        $toolId,
        $processingMs
    );
} catch (ValidationException $e) {
    Response::error($e->getMessage(), 422, null, 'validation_error');
} catch (AppException $e) {
    Response::error($e->getMessage(), $e->getStatusCode(), null, $e->getErrorCode());
} catch (Throwable $e) {
    error_log('[api-image] ' . $e->getMessage());
    Response::error('Falha ao processar a imagem.', 500);
}
