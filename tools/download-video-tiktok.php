<?php

declare(strict_types=1);

/**
 * SiteTools — Download de Vídeo do TikTok.
 * Contrato JSON preservado: info {action,url}; download {action,url,format_id}.
 */

require __DIR__ . '/_csrf_guard.php';

require __DIR__ . '/_tool_access_guard.php';
tool_access_guard_for('download-video-tiktok');

require __DIR__ . '/_process_helpers.php';

use App\Controllers\TikTokController;
use App\Exceptions\AppException;
use App\Exceptions\ValidationException;

header('X-Content-Type-Options: nosniff');

function tiktok_video_respond_json(array $data, int $status = 200): never
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

function tiktok_video_respond_error(string $message, int $status = 400): never
{
    tiktok_video_respond_json(['error' => $message], $status);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    tiktok_video_respond_error('Método não permitido.', 405);
}

if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 16384) {
    tiktok_video_respond_error('Requisição muito grande.', 413);
}

$raw = file_get_contents('php://input');
$input = json_decode($raw ?: '', true);

if (!is_array($input)) {
    tiktok_video_respond_error('Corpo da requisição inválido.');
}

$action = (string) ($input['action'] ?? '');
$url = trim((string) ($input['url'] ?? ''));

if ($url === '') {
    tiktok_video_respond_error('Informe a URL do TikTok.');
}

try {
    $controller = new TikTokController();

    if ($action === 'info') {
        tiktok_video_respond_json($controller->getInfo($url));
    }

    if ($action === 'download') {
        $formatId = trim((string) ($input['format_id'] ?? 'h264'));
        $startedAt = hrtime(true);
        $result = $controller->download($url, $formatId);
        $processingMs = (int) ((hrtime(true) - $startedAt) / 1_000_000);
        $toolId = null;

        try {
            $toolId = process_tool_id(app_pdo(), 'download-video-tiktok');
        } catch (Throwable) {
            // O arquivo pronto não deve ser descartado por falha no histórico.
        }

        // Mantém o workspace vivo durante a transmissão do arquivo.
        $workspace = $result['workspace'];

        process_send_file(
            $result['path'],
            $result['filename'],
            $result['mime'],
            'download-video-tiktok',
            $toolId,
            $processingMs
        );
    }

    tiktok_video_respond_error('Ação não reconhecida.');
} catch (ValidationException $e) {
    tiktok_video_respond_error($e->getMessage(), 422);
} catch (AppException $e) {
    tiktok_video_respond_error($e->getMessage(), $e->getStatusCode());
} catch (Throwable $e) {
    error_log('[download-video-tiktok] ' . $e->getMessage());
    tiktok_video_respond_error('Não foi possível processar este vídeo. Tente novamente.', 500);
}
