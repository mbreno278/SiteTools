<?php

declare(strict_types=1);

/**
 * SiteTools — Download de Vídeo / Reels do Instagram.
 * Contrato JSON preservado: info {action,url}; download {action,url,format_id}.
 */

require __DIR__ . '/_csrf_guard.php';

require __DIR__ . '/_tool_access_guard.php';
tool_access_guard_for('download-video-instagram');

require __DIR__ . '/_process_helpers.php';

use App\Controllers\InstagramController;
use App\Exceptions\AppException;
use App\Exceptions\ValidationException;

header('X-Content-Type-Options: nosniff');

function instagram_video_respond_json(array $data, int $status = 200): never
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function instagram_video_respond_error(string $message, int $status = 400): never
{
    instagram_video_respond_json(['error' => $message], $status);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    instagram_video_respond_error('Método não permitido.', 405);
}

if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 16384) {
    instagram_video_respond_error('Requisição muito grande.', 413);
}

$raw = file_get_contents('php://input');
$input = json_decode($raw ?: '', true);

if (!is_array($input)) {
    instagram_video_respond_error('Corpo da requisição inválido.');
}

$action = (string) ($input['action'] ?? '');
$url = trim((string) ($input['url'] ?? ''));

if ($url === '') {
    instagram_video_respond_error('Informe a URL do Instagram.');
}

$controller = new InstagramController();

try {
    if ($action === 'info') {
        instagram_video_respond_json($controller->getInfo($url));
    }

    if ($action === 'download') {
        $formatId = trim((string) ($input['format_id'] ?? 'best'));
        $result = $controller->download($url, $formatId);
        $processingMs = (int) ((hrtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))) * 1000);
        $toolId = null;

        try {
            $toolId = process_tool_id(app_pdo(), 'download-video-instagram');
        } catch (Throwable) {
            // O arquivo pronto não deve ser descartado por falha no histórico.
        }

        $workspace = $result['workspace'];
        register_shutdown_function(static function () use ($workspace): void {
            $workspace->cleanup();
        });

        process_send_file(
            $result['path'],
            $result['filename'],
            $result['mime'],
            'download-video-instagram',
            $toolId,
            $processingMs
        );
    }

    instagram_video_respond_error('Ação não reconhecida.');
} catch (ValidationException $e) {
    instagram_video_respond_error($e->getMessage(), 422);
} catch (AppException $e) {
    instagram_video_respond_error($e->getMessage(), $e->getStatusCode());
} catch (Throwable $e) {
    error_log('[download-video-instagram] ' . $e->getMessage());
    instagram_video_respond_error('Não foi possível processar este conteúdo. Tente novamente.', 500);
}
