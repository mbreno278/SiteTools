<?php

declare(strict_types=1);

/**
 * SiteTools — Download de Áudio do YouTube.
 * Contrato JSON preservado: info {action,url}; download {action,url,format_id,output}.
 */

require __DIR__ . '/_csrf_guard.php';

require __DIR__ . '/_module_guard.php';
module_guard_require('video_downloads', 'Downloads de vídeo estão desativados neste ambiente.');

require __DIR__ . '/_tool_access_guard.php';
tool_access_guard_for('download-audio-youtube');

require __DIR__ . '/_process_helpers.php';

use App\Controllers\YoutubeController;
use App\Exceptions\AppException;
use App\Exceptions\ValidationException;

header('X-Content-Type-Options: nosniff');

function youtube_audio_respond_json(array $data, int $status = 200): never
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

function youtube_audio_respond_error(string $message, int $status = 400): never
{
    youtube_audio_respond_json(['error' => $message], $status);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    youtube_audio_respond_error('Método não permitido.', 405);
}

$contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > 16384) {
    youtube_audio_respond_error('Requisição muito grande.', 413);
}

$raw = file_get_contents('php://input');
$input = json_decode($raw ?: '', true);

if (!is_array($input)) {
    youtube_audio_respond_error('Corpo da requisição inválido.');
}

$action = (string) ($input['action'] ?? '');
$url = trim((string) ($input['url'] ?? ''));

if ($url === '') {
    youtube_audio_respond_error('Informe a URL do vídeo.');
}

$controller = new YoutubeController();

try {
    if ($action === 'info') {
        youtube_audio_respond_json($controller->getAudioInfo($url));
    }

    if ($action === 'download') {
        $formatId = trim((string) ($input['format_id'] ?? 'bestaudio'));
        $output = strtolower(trim((string) ($input['output'] ?? 'mp3')));

        $startedAt = hrtime(true);
        $result = $controller->downloadAudio($url, $formatId, $output);
        $processingMs = (int) ((hrtime(true) - $startedAt) / 1_000_000);
        $toolId = null;

        try {
            $toolId = process_tool_id(app_pdo(), 'download-audio-youtube');
        } catch (Throwable) {
            // O arquivo pronto não deve ser descartado por falha no histórico.
        }

        // Mantém o workspace vivo durante a transmissão do arquivo.
        $workspace = $result['workspace'];

        process_send_file(
            $result['path'],
            $result['filename'],
            $result['mime'],
            'download-audio-youtube',
            $toolId,
            $processingMs
        );
    }

    youtube_audio_respond_error('Ação não reconhecida.');
} catch (ValidationException $e) {
    youtube_audio_respond_error($e->getMessage(), 422);
} catch (AppException $e) {
    youtube_audio_respond_error($e->getMessage(), $e->getStatusCode());
} catch (Throwable $e) {
    error_log('[download-audio-youtube] ' . $e->getMessage());
    youtube_audio_respond_error('Não foi possível processar este áudio. Tente novamente.', 500);
}
