<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AppException;
use App\Exceptions\ValidationException;
use App\Services\Process\ProcessRunner;
use App\Services\Process\TempWorkspace;

/**
 * Consulta e download de vídeos públicos do TikTok usando yt-dlp.
 */
final class TikTokService
{
    public function __construct(
        private readonly ProcessRunner $runner = new ProcessRunner(),
    ) {
    }

    public function getVideoInfo(string $url): array
    {
        $url = $this->validateUrl($url);
        $result = $this->runner->run([
            $this->binary('ytdlp', 'yt-dlp'),
            '--dump-single-json', '--no-warnings', '--no-playlist',
            '--skip-download', $url,
        ], null, 60);

        if ($result['timed_out']) {
            throw new AppException('A consulta do vídeo excedeu o tempo limite.', 504, 'tiktok_info_timeout');
        }
        if ($result['exit'] !== 0) {
            throw new AppException($this->friendlyError($result['stderr']), 422, 'tiktok_info_failed');
        }

        $info = json_decode($result['stdout'], true);
        if (!is_array($info)) {
            throw new AppException('Resposta inválida do extrator.', 502, 'tiktok_invalid_response');
        }

        $formats = $this->buildFormatList($info);
        if ($formats === []) {
            throw new ValidationException('Nenhum formato de vídeo disponível para este link.');
        }

        $title = $info['title'] ?? $info['description'] ?? 'Vídeo do TikTok';
        if (is_string($title) && mb_strlen($title) > 120) {
            $title = mb_substr($title, 0, 117) . '...';
        }
        $channel = $info['uploader'] ?? $info['creator'] ?? $info['channel'] ?? null;

        return [
            'id' => $info['id'] ?? null,
            'title' => $title,
            'channel' => is_string($channel) ? ltrim($channel, '@') : $channel,
            'thumbnail' => $info['thumbnail'] ?? null,
            'duration' => isset($info['duration']) ? (int) $info['duration'] : null,
            'view_count' => isset($info['view_count']) ? (int) $info['view_count'] : null,
            'like_count' => isset($info['like_count']) ? (int) $info['like_count'] : null,
            'comment_count' => isset($info['comment_count']) ? (int) $info['comment_count'] : null,
            'formats' => $formats,
        ];
    }

    /** @return array{path:string,mime:string,filename:string,bytes:int,workspace:TempWorkspace} */
    public function downloadVideo(string $url, string $formatId = 'h264'): array
    {
        $url = $this->validateUrl($url);
        $formatId = trim($formatId) !== '' ? trim($formatId) : 'h264';
        if (!preg_match('/^[a-zA-Z0-9_+\-\/\.\[\]=\*]+$/', $formatId)) {
            throw new ValidationException('Formato inválido.');
        }

        $ytdlp = $this->binary('ytdlp', 'yt-dlp');
        $workspace = new TempWorkspace();

        try {
            $template = $workspace->join('media.%(ext)s');
            $audioOnly = in_array($formatId, ['ba/b', 'bestaudio'], true)
                || str_starts_with($formatId, 'ba');

            if ($audioOnly) {
                $commands = [[
                    $ytdlp, '--no-warnings', '--no-playlist', '--extract-audio',
                    '--audio-format', 'm4a', '--audio-quality', '0',
                    '-o', $template, '--no-part', '--restrict-filenames', $url,
                ]];
            } else {
                $selector = $this->resolveFormatSelector($formatId);
                $commands = [
                    [$ytdlp, '--no-warnings', '--no-playlist', '--format', $selector,
                        '--merge-output-format', 'mp4', '-o', $template,
                        '--no-part', '--restrict-filenames', $url],
                    [$ytdlp, '--no-warnings', '--no-playlist', '-f', 'bv*+ba/b',
                        '--merge-output-format', 'mp4', '-o', $template,
                        '--no-part', '--restrict-filenames', $url],
                    [$ytdlp, '--no-warnings', '--no-playlist', '-f', 'best',
                        '--merge-output-format', 'mp4', '-o', $template,
                        '--no-part', '--restrict-filenames', $url],
                ];
            }

            $result = null;
            foreach ($commands as $command) {
                $result = $this->runner->run($command, $workspace->path(), 180);
                if ($result['timed_out']) {
                    throw new AppException('O download excedeu o tempo limite.', 504, 'tiktok_download_timeout');
                }
                if ($result['exit'] === 0) break;
            }

            if (!is_array($result) || $result['exit'] !== 0) {
                throw new AppException(
                    $this->friendlyError($result['stderr'] ?? 'Falha ao baixar o vídeo.'),
                    422,
                    'tiktok_download_failed'
                );
            }

            $files = $this->outputFiles($workspace);
            if ($files === []) {
                throw new AppException('Arquivo de saída não encontrado.', 500, 'tiktok_output_missing');
            }

            if (!$audioOnly) {
                $this->ensureCompatibleMp4($files[0], $workspace);
                $files = $this->outputFiles($workspace);
            }

            $path = $files[0];
            foreach ($files as $file) {
                if (str_ends_with(strtolower($file), $audioOnly ? '.m4a' : '.mp4')) {
                    $path = $file;
                    break;
                }
            }

            $bytes = filesize($path);
            if ($bytes === false || $bytes < 500) {
                throw new AppException('O arquivo gerado está vazio ou corrompido.', 500, 'tiktok_output_invalid');
            }

            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: 'mp4');
            $mime = [
                'mp4' => 'video/mp4', 'webm' => 'video/webm',
                'mkv' => 'video/x-matroska', 'm4a' => 'audio/mp4',
                'mp3' => 'audio/mpeg', 'aac' => 'audio/aac',
            ][$ext] ?? 'application/octet-stream';

            return [
                'path' => $path,
                'mime' => $mime,
                'filename' => 'tiktok-video.' . $ext,
                'bytes' => (int) $bytes,
                'workspace' => $workspace,
            ];
        } catch (\Throwable $e) {
            $workspace->cleanup();
            throw $e;
        }
    }

    private function validateUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            throw new ValidationException('URL do TikTok inválida.');
        }
        $parts = parse_url($url);
        if ($parts === false || empty($parts['host'])) {
            throw new ValidationException('URL do TikTok inválida.');
        }
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) preg_replace('/^www\./', '', $parts['host']));
        $allowed = ['tiktok.com', 'vm.tiktok.com', 'vt.tiktok.com', 'm.tiktok.com'];
        $validHost = in_array($host, $allowed, true) || str_ends_with($host, '.tiktok.com');
        if (!in_array($scheme, ['http', 'https'], true) || !$validHost) {
            throw new ValidationException('URL do TikTok inválida.');
        }
        return $url;
    }

    private function binary(string $key, string $label): string
    {
        $config = require dirname(__DIR__, 2) . '/config/converters.php';
        $path = is_array($config) ? ($config[$key] ?? null) : null;
        if (!is_string($path) || trim($path) === '') {
            throw new AppException($label . ' não encontrado no servidor. Configure o caminho do executável.', 503, $key . '_missing');
        }
        return $path;
    }

    private function friendlyError(string $error): string
    {
        $message = trim($error);
        if ($message === '') return 'Não foi possível obter dados do vídeo.';
        if (stripos($message, 'Private') !== false || stripos($message, 'login') !== false || stripos($message, 'Sign in') !== false) {
            return 'Este vídeo é privado ou requer login.';
        }
        if (stripos($message, 'Unavailable') !== false || stripos($message, 'not available') !== false || stripos($message, 'Video not found') !== false) {
            return 'Vídeo indisponível ou removido.';
        }
        if (stripos($message, 'No video formats found') !== false) {
            return 'Formato não suportado para download. Apenas vídeos do TikTok são aceitos.';
        }
        if (stripos($message, 'rate-limit') !== false || stripos($message, '429') !== false) {
            return 'TikTok limitou as requisições. Tente novamente em alguns minutos.';
        }
        if (stripos($message, 'ffmpeg') !== false) {
            return 'FFmpeg não encontrado. Instale o FFmpeg para mesclar ou converter vídeo e áudio.';
        }
        error_log('[tiktok] ' . mb_substr($message, 0, 500));
        return 'Não foi possível processar este link. Verifique se o vídeo é público.';
    }

    private function buildFormatList(array $info): array
    {
        // Identificadores estáveis usados pela interface atual.
        return [
            ['format_id' => 'h264', 'type' => 'video', 'label' => 'MP4 · H.264 (compatível)', 'resolution' => null, 'ext' => 'mp4', 'filesize' => null],
            ['format_id' => 'h264_720', 'type' => 'video', 'label' => '720p · H.264 (compatível)', 'resolution' => '720p', 'ext' => 'mp4', 'filesize' => null],
            ['format_id' => 'h264_480', 'type' => 'video', 'label' => '480p · H.264 (compatível)', 'resolution' => '480p', 'ext' => 'mp4', 'filesize' => null],
            ['format_id' => 'ba/b', 'type' => 'audio', 'label' => 'Apenas áudio', 'resolution' => null, 'ext' => 'm4a', 'filesize' => null],
        ];
    }

    private function resolveFormatSelector(string $formatId): string
    {
        return match ($formatId) {
            'h264' => 'bv*[vcodec^=avc1]+ba[ext=m4a]/bv*[vcodec^=avc1]+ba/b[ext=mp4]/b',
            'h264_720' => 'bv*[vcodec^=avc1][height<=720]+ba[ext=m4a]/bv*[height<=720]+ba/b',
            'h264_480' => 'bv*[vcodec^=avc1][height<=480]+ba[ext=m4a]/bv*[height<=480]+ba/b',
            'ba/b' => 'ba/b',
            default => $formatId,
        };
    }

    private function outputFiles(TempWorkspace $workspace): array
    {
        return array_values(array_filter(
            glob($workspace->path() . DIRECTORY_SEPARATOR . 'media.*') ?: [],
            static fn(string $file): bool => is_file($file) && !str_ends_with($file, '.part')
        ));
    }

    private function ensureCompatibleMp4(string $input, TempWorkspace $workspace): void
    {
        $ffmpeg = $this->configuredFfmpeg();
        if ($ffmpeg === null) return;

        $ffprobe = $ffmpeg === 'ffmpeg'
            ? 'ffprobe'
            : preg_replace('/ffmpeg(?:\.exe)?$/i', PHP_OS_FAMILY === 'Windows' ? 'ffprobe.exe' : 'ffprobe', $ffmpeg);

        $probe = $this->runner->run([
            (string) $ffprobe, '-v', 'error', '-select_streams', 'v:0',
            '-show_entries', 'stream=codec_name',
            '-of', 'default=noprint_wrappers=1:nokey=1', $input,
        ], $workspace->path(), 30);
        $codec = strtolower(trim($probe['stdout']));
        if (in_array($codec, ['h264', 'avc1', 'avc'], true)) return;

        $output = $workspace->join('compatible.mp4');
        $result = $this->runner->run([
            $ffmpeg, '-y', '-i', $input, '-c:v', 'libx264',
            '-preset', 'fast', '-crf', '23', '-c:a', 'aac',
            '-b:a', '192k', '-movflags', '+faststart',
            '-pix_fmt', 'yuv420p', $output,
        ], $workspace->path(), 300);

        if (!$result['timed_out'] && $result['exit'] === 0 && is_file($output) && filesize($output) > 500) {
            @unlink($input);
            @rename($output, $workspace->join('media.mp4'));
        }
    }

    private function configuredFfmpeg(): ?string
    {
        $config = require dirname(__DIR__, 2) . '/config/converters.php';
        $path = is_array($config) ? ($config['ffmpeg'] ?? null) : null;
        return is_string($path) && trim($path) !== '' ? $path : null;
    }
}
