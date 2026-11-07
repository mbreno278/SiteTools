<?php

declare(strict_types=1);

/**
 * Endpoint unificado de imagens (resize | compress | convert | crop).
 * Frontends legados podem apontar action para este arquivo.
 *
 * POST multipart:
 *   action = resize|compress|convert|crop
 *   image  = arquivo
 *   width, height, quality, format, x, y, w, h conforme action
 */

require __DIR__ . '/_csrf_guard.php';

$action = strtolower(trim((string) ($_POST['action'] ?? 'resize')));
$slugMap = [
    'resize' => 'redimensionar-imagem',
    'compress' => 'image-compressor',
    'convert' => 'image-converter',
    'crop' => 'image-cropper',
];
$slug = $slugMap[$action] ?? 'redimensionar-imagem';

require __DIR__ . '/_tool_access_guard.php';
tool_access_guard_for($slug);

require __DIR__ . '/_process_helpers.php';

use App\Exceptions\AppException;
use App\Exceptions\ValidationException;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    Response::error('Método não permitido.', 405);
}

if (empty($_FILES['image']) && empty($_FILES['file'])) {
    Response::error('Selecione uma imagem.', 422);
}

$file = $_FILES['image'] ?? $_FILES['file'];
$started = hrtime(true);
$svc = process_image_service();

try {
    $result = match ($action) {
        'compress' => $svc->compress(
            $file,
            (int) ($_POST['quality'] ?? 75),
            (string) ($_POST['format'] ?? 'jpeg')
        ),
        'convert' => $svc->convert(
            $file,
            (string) ($_POST['format'] ?? 'png'),
            (int) ($_POST['quality'] ?? 90)
        ),
        'crop' => $svc->crop(
            $file,
            (int) ($_POST['x'] ?? 0),
            (int) ($_POST['y'] ?? 0),
            (int) ($_POST['w'] ?? 0),
            (int) ($_POST['h'] ?? 0),
            (string) ($_POST['format'] ?? 'jpeg'),
            (int) ($_POST['quality'] ?? 90)
        ),
        default => $svc->resize(
            $file,
            (int) ($_POST['width'] ?? 0),
            (int) ($_POST['height'] ?? 0),
            (($_POST['keep_ratio'] ?? '1') !== '0'),
            (string) ($_POST['format'] ?? 'jpeg'),
            (int) ($_POST['quality'] ?? 85)
        ),
    };

    $ms = (int) ((hrtime(true) - $started) / 1_000_000);
    $toolId = null;
    try {
        $toolId = process_tool_id(app_pdo(), $slug);
    } catch (Throwable) {
    }

    $ext = match (true) {
        str_contains($result['mime'], 'png') => 'png',
        str_contains($result['mime'], 'webp') => 'webp',
        default => 'jpg',
    };
    $name = pathinfo((string) ($file['name'] ?? 'image'), PATHINFO_FILENAME) . '.' . $ext;

    process_send_file($result['path'], $name, $result['mime'], $slug, $toolId, $ms);
} catch (ValidationException $e) {
    Response::error($e->getMessage(), 422, null, 'validation_error');
} catch (AppException $e) {
    Response::error($e->getMessage(), $e->getStatusCode(), null, $e->getErrorCode());
} catch (Throwable $e) {
    error_log('[api-image] ' . $e->getMessage());
    Response::error('Falha ao processar a imagem.', 500);
}
