<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AppException;
use App\Exceptions\ValidationException;
use App\Services\Process\ProcessRunner;
use App\Services\Process\TempWorkspace;

/**
 * Consulta metadados e baixa vídeos públicos do Instagram via yt-dlp.
 */
final class InstagramService
{
    public function __construct(
        private readonly ProcessRunner $runner = new ProcessRunner(),
    ) {
    }

    public function getVideoInfo(string $url): array
    {
        $url = $this->validateInstagramUrl($url);
        $result = $this->runner->run([
            $this->findYtDlp(),
            '--dump-single-json',
            '--no-warnings',
            '--no-playlist',
            '--skip-download',
            $url,
        ], null, 60);

        if ($result['timed_out']) {
            throw new AppException('A consulta do conteúdo excedeu o tempo limite.', 504, 'instagram_info_timeout');
        }
        if ($result['exit'] !== 0) {
            throw new AppException($this->friendlyError($result['stderr']), 422, 'instagram_info_failed');
        }

        $info = json_decode($result['stdout'], true);
        if (!is_array($info)) {
            throw new AppException('Resposta inválida do extrator.', 502, 'instagram_invalid_response');
        }
        if ($this->isImageOnlyPost($info)) {
            throw new ValidationException('Este post contém apenas imagem. O download está disponível somente para vídeos, Reels e IGTV.');
        }

        $formats = $this->buildFormatList($info);
        if ($formats === []) {
            throw new ValidationException('Formato não suportado. Apenas vídeos, Reels e IGTV podem ser baixados.');
        }

        $title = $info['title'] ?? $info['description'] ?? 'Vídeo do Instagram';
        if (is_string($title) && mb_strlen($title) > 120) {
            $title = mb_substr($title, 0, 117) . '...';
        }

        $channel = $info['uploader'] ?? $info['channel'] ?? $info['creator'] ?? null;
        if (is_string($channel)) {
            $channel = ltrim($channel, '@');
        }

        return [
            'id' => $info['id'] ?? null,
            'title' => $title,
            'description' => $info['description'] ?? null,
            'channel' => $channel,
            'thumbnail' => $info['thumbnail'] ?? null,
            'duration' => isset($info['duration']) ? (int) $info['duration'] : null,
            'view_count' => isset($info['view_count']) ? (int) $info['view_count'] : null,
            'like_count' => isset($info['like_count']) ? (int) $info['like_count'] : null,
            'type' => $this->detectInstagramType($url),
            'formats' => $formats,
        ];
    }

    /**
     * @return array{path:string,mime:string,filename:string,bytes:int,workspace:TempWorkspace}
     */
    public function downloadVideo(string $url, string $formatId = 'best'): array
    {
        $url = $this->validateInstagramUrl($url);
        $formatId = trim($formatId) !== '' ? trim($formatId) : 'best';

        if (!preg_match('/^[a-zA-Z0-9_+\-\/\.\[\]=\*]+$/', $formatId)) {
            throw new ValidationException('Formato inválido.');
        }

        $ytdlp = $this->findYtDlp();
        $workspace = new TempWorkspace();

        try {
            $template = $workspace->join('media.%(ext)s');
            $isAudioOnly = in_array($formatId, ['ba/b', 'bestaudio'], true)
                || str_starts_with($formatId, 'ba');

            if ($isAudioOnly) {
                $commands = [[
                    $ytdlp, '--no-warnings', '--no-playlist',
                    '--extract-audio', '--audio-format', 'm4a',
                    '--audio-quality', '0', '-o', $template,
                    '--no-part', '--restrict-filenames', $url,
                ]];
            } else {
                $commands = [
                    [
                        $ytdlp, '--no-warnings', '--no-playlist',
                        '--format', $formatId,
                        '--merge-output-format', 'mp4',
                        '--postprocessor-args', 'ffmpeg:-c:v copy -c:a aac -b:a 192k',
                        '-o', $template, '--no-part', '--restrict-filenames', $url,
                    ],
                    [
                        $ytdlp, '--no-warnings', '--no-playlist',
                        '-f', 'bv*+ba/b', '--merge-output-format', 'mp4',
                        '-o', $template, '--no-part', '--restrict-filenames', $url,
                    ],
                    [
                        $ytdlp, '--no-warnings', '--no-playlist',
                        '-f', 'best', '-o', $template,
                        '--no-part', '--restrict-filenames', $url,
                    ],
                ];
            }

            $result = null;
            foreach ($commands as $command) {
                $result = $this->runner->run($command, $workspace->path(), 180);
                if ($result['timed_out']) {
                    throw new AppException('O download excedeu o tempo limite.', 504, 'instagram_download_timeout');
                }
                if ($result['exit'] === 0) {
                    break;
                }
            }

            if (!is_array($result) || $result['exit'] !== 0) {
                throw new AppException(
                    $this->friendlyError($result['stderr'] ?? '', 'Falha ao baixar o conteúdo.'),
                    422,
                    'instagram_download_failed'
                );
            }

            $files = array_values(array_filter(
                glob($workspace->path() . DIRECTORY_SEPARATOR . 'media.*') ?: [],
                static fn(string $file): bool => is_file($file) && !str_ends_with($file, '.part')
            ));

            if ($files === []) {
                throw new AppException('Arquivo de saída não encontrado.', 500, 'instagram_output_missing');
            }

            $path = $files[0];
            $bytes = filesize($path);
            if ($bytes === false || $bytes < 500) {
                throw new AppException('O arquivo gerado está vazio ou corrompido.', 500, 'instagram_output_invalid');
            }

            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: 'mp4');
            $mimeMap = [
                'mp4' => 'video/mp4', 'webm' => 'video/webm',
                'mkv' => 'video/x-matroska', 'mov' => 'video/quicktime',
                'm4a' => 'audio/mp4', 'mp3' => 'audio/mpeg',
                'aac' => 'audio/aac', 'opus' => 'audio/opus',
                'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png', 'webp' => 'image/webp',
            ];

            return [
                'path' => $path,
                'mime' => $mimeMap[$ext] ?? 'application/octet-stream',
                'filename' => 'instagram-media.' . $ext,
                'bytes' => (int) $bytes,
                'workspace' => $workspace,
            ];
        } catch (\Throwable $e) {
            $workspace->cleanup();
            throw $e;
        }
    }

    private function validateInstagramUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            throw new ValidationException('URL do Instagram inválida. Use links de Reel, Post, IGTV ou Story.');
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['host'])) {
            throw new ValidationException('URL do Instagram inválida.');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) preg_replace('/^www\./', '', $parts['host']));
        $path = $parts['path'] ?? '';

        if (!in_array($scheme, ['http', 'https'], true)
            || !in_array($host, ['instagram.com', 'instagr.am'], true)
            || !(str_starts_with($path, '/p/')
                || str_starts_with($path, '/reel/')
                || str_starts_with($path, '/reels/')
                || str_starts_with($path, '/tv/')
                || str_starts_with($path, '/stories/'))) {
            throw new ValidationException('URL do Instagram inválida. Use links de Reel, Post, IGTV ou Story.');
        }

        return $url;
    }

    private function findYtDlp(): string
    {
        $configFile = dirname(__DIR__, 2) . '/config/converters.php';
        $config = is_file($configFile) ? require $configFile : [];
        $binary = is_array($config) ? ($config['ytdlp'] ?? null) : null;

        if (!is_string($binary) || trim($binary) === '') {
            throw new AppException('yt-dlp não encontrado no servidor. Configure YTDLP_PATH.', 503, 'ytdlp_missing');
        }

        return $binary;
    }

    private function detectInstagramType(string $url): string
    {
        if (str_contains($url, '/reel') || str_contains($url, '/reels/')) return 'Reel';
        if (str_contains($url, '/tv/')) return 'IGTV';
        if (str_contains($url, '/stories/')) return 'Story';
        return 'Post';
    }

    private function isImageOnlyPost(array $info): bool
    {
        $duration = $info['duration'] ?? null;
        $ext = strtolower((string) ($info['ext'] ?? ''));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return true;
        }

        $formats = $info['formats'] ?? [];
        if (!is_array($formats) || $formats === []) {
            return $duration === null || (int) $duration === 0;
        }

        foreach ($formats as $format) {
            if (!is_array($format)) continue;
            $vcodec = (string) ($format['vcodec'] ?? 'none');
            $fext = strtolower((string) ($format['ext'] ?? ''));
            if (($vcodec !== 'none' && $vcodec !== '')
                || in_array($fext, ['mp4', 'webm', 'mkv', 'mov'], true)) {
                return false;
            }
        }

        return true;
    }

    private function friendlyError(string $error, string $fallback = 'Não foi possível processar este link. Use apenas links de vídeo, Reel ou IGTV.'): string
    {
        $message = trim($error);
        if ($message === '') return $fallback;

        if (stripos($message, 'No video formats found') !== false
            || stripos($message, 'no video') !== false
            || stripos($message, 'There is no video') !== false) {
            return 'Formato não suportado. Apenas vídeos, Reels e IGTV podem ser baixados.';
        }
        if (stripos($message, 'Private') !== false || stripos($message, 'login') !== false || stripos($message, 'Sign in') !== false) {
            return 'Este conteúdo é privado ou requer login.';
        }
        if (stripos($message, 'Unavailable') !== false || stripos($message, 'not available') !== false) {
            return 'Conteúdo indisponível ou removido.';
        }
        if (stripos($message, 'rate-limit') !== false || stripos($message, '429') !== false) {
            return 'Instagram limitou as requisições. Tente novamente em alguns minutos.';
        }
        if (stripos($message, 'ffmpeg') !== false) {
            return 'FFmpeg não encontrado. Instale o FFmpeg para mesclar vídeo e áudio.';
        }

        error_log('[instagram] ' . mb_substr($message, 0, 500));
        return $fallback;
    }

    private function buildFormatList(array $info): array
    {
        $raw = $info['formats'] ?? [];
        if (!is_array($raw)) return [];

        $heights = [];
        foreach ($raw as $format) {
            if (!is_array($format)) continue;
            $vcodec = (string) ($format['vcodec'] ?? 'none');
            $height = isset($format['height']) ? (int) $format['height'] : 0;
            if ($vcodec !== 'none' && $vcodec !== '' && $height > 0) {
                $heights[$height] = true;
            }
        }

        krsort($heights, SORT_NUMERIC);
        $formats = [[
            'format_id' => 'bv*+ba/b', 'type' => 'video',
            'label' => 'Melhor qualidade · com áudio',
            'resolution' => null, 'ext' => 'mp4', 'fps' => null, 'filesize' => null,
        ]];

        foreach (array_keys($heights) as $height) {
            if (count($formats) >= 6) break;
            $formats[] = [
                'format_id' => "bv*[height={$height}]+ba/b",
                'type' => 'video', 'label' => "{$height}p · com áudio",
                'resolution' => "{$height}p", 'ext' => 'mp4',
                'fps' => null, 'filesize' => null,
            ];
        }

        $formats[] = [
            'format_id' => 'ba/b', 'type' => 'audio',
            'label' => 'Apenas áudio', 'resolution' => null,
            'ext' => 'm4a', 'abr' => null, 'filesize' => null,
        ];

        $unique = [];
        $seen = [];
        foreach ($formats as $format) {
            if (isset($seen[$format['format_id']])) continue;
            $seen[$format['format_id']] = true;
            $unique[] = $format;
        }
        return $unique;
    }
}
