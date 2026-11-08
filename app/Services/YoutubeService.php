<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AppException;
use App\Exceptions\ValidationException;
use App\Services\Process\ProcessRunner;
use App\Services\Process\TempWorkspace;

/**
 * Consulta metadados e baixa mídias públicas acessíveis pelo yt-dlp.
 * Não implementa acesso a vídeos privados, DRM ou autenticação de terceiros.
 */
final class YoutubeService
{
    public function __construct(
        private readonly ProcessRunner $runner = new ProcessRunner(),
    ) {
    }

    public function getVideoInfo(string $url): array
    {
        $url = $this->validateYoutubeUrl($url);
        $ytdlp = $this->findYtDlp();

        $result = $this->runner->run([
            $ytdlp,
            '--dump-single-json',
            '--no-warnings',
            '--no-playlist',
            '--skip-download',
            $url,
        ], null, 45);

        if ($result['timed_out']) {
            throw new AppException('A consulta do vídeo excedeu o tempo limite.', 504, 'youtube_info_timeout');
        }

        if ($result['exit'] !== 0) {
            throw new AppException($this->friendlyError($result['stderr']), 422, 'youtube_info_failed');
        }

        $info = json_decode($result['stdout'], true);
        if (!is_array($info)) {
            throw new AppException('Resposta inválida do extrator.', 502, 'youtube_invalid_response');
        }

        return [
            'id' => $info['id'] ?? null,
            'title' => $info['title'] ?? 'Sem título',
            'channel' => $info['channel'] ?? $info['uploader'] ?? null,
            'thumbnail' => $info['thumbnail'] ?? null,
            'duration' => isset($info['duration']) ? (int) $info['duration'] : null,
            'view_count' => isset($info['view_count']) ? (int) $info['view_count'] : null,
            'upload_date' => $info['upload_date'] ?? null,
            'formats' => $this->buildFormatList($info),
        ];
    }

    /**
     * @return array{path:string,mime:string,filename:string,bytes:int,workspace:TempWorkspace}
     */
    public function downloadVideo(string $url, string $formatId): array
    {
        $url = $this->validateYoutubeUrl($url);
        if (!preg_match('/^[a-zA-Z0-9_+\-\/.]+$/', $formatId)) {
            throw new ValidationException('Formato inválido.');
        }

        $ytdlp = $this->findYtDlp();
        $workspace = new TempWorkspace();

        try {
            $template = $workspace->join('video.%(ext)s');
            $result = $this->runner->run([
                $ytdlp,
                '--no-warnings',
                '--no-playlist',
                '--format',
                $formatId,
                '--merge-output-format',
                'mp4',
                '-o',
                $template,
                '--no-part',
                '--restrict-filenames',
                $url,
            ], $workspace->path(), 300);

            if ($result['timed_out']) {
                throw new AppException('O download excedeu o tempo limite.', 504, 'youtube_download_timeout');
            }

            if ($result['exit'] !== 0) {
                throw new AppException($this->friendlyError($result['stderr'], 'Falha ao baixar o vídeo.'), 422, 'youtube_download_failed');
            }

            $files = array_values(array_filter(
                glob($workspace->path() . DIRECTORY_SEPARATOR . 'video.*') ?: [],
                static fn(string $file): bool => is_file($file) && !str_ends_with($file, '.part')
            ));

            if ($files === []) {
                throw new AppException('Arquivo de saída não encontrado.', 500, 'youtube_output_missing');
            }

            $path = $files[0];
            $bytes = filesize($path);
            if ($bytes === false || $bytes < 1) {
                throw new AppException('O arquivo baixado está vazio.', 500, 'youtube_output_invalid');
            }

            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: 'mp4');
            $mimeMap = [
                'mp4' => 'video/mp4',
                'webm' => 'video/webm',
                'mkv' => 'video/x-matroska',
                'm4a' => 'audio/mp4',
                'mp3' => 'audio/mpeg',
                'opus' => 'audio/opus',
                'ogg' => 'audio/ogg',
                'wav' => 'audio/wav',
            ];

            return [
                'path' => $path,
                'mime' => $mimeMap[$ext] ?? 'application/octet-stream',
                'filename' => 'youtube-video.' . $ext,
                'bytes' => (int) $bytes,
                'workspace' => $workspace,
            ];
        } catch (\Throwable $e) {
            $workspace->cleanup();
            throw $e;
        }
    }

    public function getAudioInfo(string $url): array
    {
        $info = $this->getVideoInfo($url);
        $info['formats'] = $this->buildAudioFormatList();
        return $info;
    }

    /**
     * @return array{path:string,mime:string,filename:string,bytes:int,workspace:TempWorkspace}
     */
    public function downloadAudio(string $url, string $formatId, string $output): array
    {
        $url = $this->validateYoutubeUrl($url);

        if (!preg_match('/^[a-zA-Z0-9_+\\-\\/.]+$/', $formatId)) {
            throw new ValidationException('Formato inválido.');
        }

        $allowedOutputs = ['mp3', 'm4a', 'opus', 'wav', 'best'];
        if (!in_array($output, $allowedOutputs, true)) {
            $output = 'mp3';
        }
        if ($output === 'best') {
            $output = 'm4a';
        }

        $ytdlp = $this->findYtDlp();
        $workspace = new TempWorkspace();

        try {
            $template = $workspace->join('audio.%(ext)s');
            $baseCommand = [
                $ytdlp,
                '--no-warnings',
                '--no-playlist',
                '--extract-audio',
                '--audio-format',
                $output,
                '--audio-quality',
                '0',
                '-f',
                $formatId,
                '-o',
                $template,
                '--no-part',
                '--restrict-filenames',
                $url,
            ];

            $command = $baseCommand;
            array_splice($command, 9, 0, [
                '--prefer-ffmpeg',
                '--postprocessor-args',
                'ffmpeg:-ar 44100',
            ]);

            $result = $this->runner->run($command, $workspace->path(), 240);
            if ($result['timed_out']) {
                throw new AppException('A extração do áudio excedeu o tempo limite.', 504, 'youtube_audio_timeout');
            }

            if ($result['exit'] !== 0) {
                $result = $this->runner->run($baseCommand, $workspace->path(), 240);
                if ($result['timed_out']) {
                    throw new AppException('A extração do áudio excedeu o tempo limite.', 504, 'youtube_audio_timeout');
                }
            }

            if ($result['exit'] !== 0) {
                $error = trim((string) ($result['stderr'] ?? ''));
                if (stripos($error, 'ffmpeg') !== false || stripos($error, 'ffprobe') !== false) {
                    throw new AppException('FFmpeg não encontrado no servidor. Instale o FFmpeg e configure o PATH.', 503, 'ffmpeg_missing');
                }
                throw new AppException($this->friendlyError($error, 'Falha ao extrair o áudio.'), 422, 'youtube_audio_failed');
            }

            $files = array_values(array_filter(
                glob($workspace->path() . DIRECTORY_SEPARATOR . 'audio.*') ?: [],
                static fn(string $file): bool => is_file($file) && !str_ends_with($file, '.part')
            ));

            if ($files === []) {
                throw new AppException('Arquivo de áudio não encontrado após a conversão.', 500, 'youtube_audio_missing');
            }

            $path = $files[0];
            foreach ($files as $file) {
                if (str_ends_with(strtolower($file), '.' . $output)) {
                    $path = $file;
                    break;
                }
            }

            $bytes = filesize($path);
            if ($bytes === false || $bytes < 1000) {
                throw new AppException('O arquivo de áudio gerado está vazio ou corrompido.', 500, 'youtube_audio_invalid');
            }

            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: $output);
            $mimeMap = [
                'mp3' => 'audio/mpeg',
                'm4a' => 'audio/mp4',
                'aac' => 'audio/aac',
                'opus' => 'audio/opus',
                'ogg' => 'audio/ogg',
                'wav' => 'audio/wav',
                'flac' => 'audio/flac',
                'webm' => 'audio/webm',
            ];

            return [
                'path' => $path,
                'mime' => $mimeMap[$ext] ?? 'application/octet-stream',
                'filename' => 'youtube-audio.' . $ext,
                'bytes' => (int) $bytes,
                'workspace' => $workspace,
            ];
        } catch (\\Throwable $e) {
            $workspace->cleanup();
            throw $e;
        }
    }

    private function buildAudioFormatList(): array
    {
        return [
            ['format_id' => 'bestaudio', 'output' => 'mp3', 'type' => 'audio', 'label' => 'MP3 · Alta qualidade', 'ext' => 'mp3', 'abr' => 192, 'acodec' => 'mp3', 'filesize' => null],
            ['format_id' => 'bestaudio', 'output' => 'mp3', 'type' => 'audio', 'label' => 'MP3 · Qualidade máxima', 'ext' => 'mp3', 'abr' => 320, 'acodec' => 'mp3', 'filesize' => null, 'quality' => '0'],
            ['format_id' => 'bestaudio', 'output' => 'm4a', 'type' => 'audio', 'label' => 'M4A (AAC) · Alta qualidade', 'ext' => 'm4a', 'abr' => 192, 'acodec' => 'aac', 'filesize' => null],
            ['format_id' => 'bestaudio', 'output' => 'opus', 'type' => 'audio', 'label' => 'OPUS · Compacto e nítido', 'ext' => 'opus', 'abr' => 160, 'acodec' => 'opus', 'filesize' => null],
            ['format_id' => 'bestaudio', 'output' => 'wav', 'type' => 'audio', 'label' => 'WAV · Sem compressão', 'ext' => 'wav', 'abr' => null, 'acodec' => 'pcm', 'filesize' => null],
        ];
    }

    private function validateYoutubeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            throw new ValidationException('URL do YouTube inválida.');
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['host'])) {
            throw new ValidationException('URL do YouTube inválida.');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if ($scheme !== 'https' && $scheme !== 'http') {
            throw new ValidationException('URL do YouTube inválida.');
        }

        $host = strtolower((string) preg_replace('/^www\./', '', $parts['host']));
        if ($host === 'youtu.be') {
            if (empty($parts['path']) || strlen($parts['path']) < 2) {
                throw new ValidationException('URL do YouTube inválida.');
            }
            return $url;
        }

        if (!in_array($host, ['youtube.com', 'm.youtube.com', 'music.youtube.com'], true)) {
            throw new ValidationException('URL do YouTube inválida.');
        }

        $path = $parts['path'] ?? '';
        $query = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        if (!isset($query['v'])
            && !str_starts_with($path, '/shorts/')
            && !str_starts_with($path, '/embed/')
            && !str_starts_with($path, '/live/')
            && !str_starts_with($path, '/watch')) {
            throw new ValidationException('URL do YouTube inválida.');
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

    private function friendlyError(string $error, string $fallback = 'Não foi possível obter dados do vídeo.'): string
    {
        $message = trim($error);
        if ($message === '') {
            return $fallback;
        }

        if (stripos($message, 'Private video') !== false) {
            return 'Este vídeo é privado.';
        }
        if (stripos($message, 'Video unavailable') !== false) {
            return 'Vídeo indisponível.';
        }
        if (stripos($message, 'Sign in') !== false) {
            return 'Este vídeo requer autenticação.';
        }
        if (stripos($message, 'Unsupported URL') !== false) {
            return 'URL do YouTube não suportada.';
        }

        error_log('[youtube] ' . mb_substr($message, 0, 500));
        return $fallback;
    }

    private function buildFormatList(array $info): array
    {
        $raw = $info['formats'] ?? [];
        if (!is_array($raw)) {
            $raw = [];
        }

        $videoFormats = [];
        $audioFormats = [];
        $seen = [];

        foreach ($raw as $format) {
            if (!is_array($format)) {
                continue;
            }

            $formatId = (string) ($format['format_id'] ?? '');
            if ($formatId === '') {
                continue;
            }

            $ext = strtolower((string) ($format['ext'] ?? ''));
            $vcodec = (string) ($format['vcodec'] ?? 'none');
            $acodec = (string) ($format['acodec'] ?? 'none');
            $height = isset($format['height']) ? (int) $format['height'] : 0;
            $fps = isset($format['fps']) ? (int) $format['fps'] : null;
            $abr = isset($format['abr']) ? (int) round((float) $format['abr']) : null;
            $filesize = $format['filesize'] ?? $format['filesize_approx'] ?? null;
            $hasVideo = $vcodec !== 'none' && $vcodec !== '';
            $hasAudio = $acodec !== 'none' && $acodec !== '';

            if ($hasVideo && $hasAudio) {
                $key = "v{$height}_{$ext}";
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $label = $height > 0 ? "{$height}p" : 'Vídeo';
                if ($ext !== '') {
                    $label .= ' · ' . strtoupper($ext);
                }
                $videoFormats[] = [
                    'format_id' => $formatId,
                    'type' => 'video',
                    'label' => $label,
                    'resolution' => $height > 0 ? "{$height}p" : null,
                    'ext' => $ext,
                    'fps' => $fps,
                    'filesize' => $filesize ? (int) $filesize : null,
                ];
            } elseif (!$hasVideo && $hasAudio) {
                $key = "a{$abr}_{$ext}";
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $label = $abr ? "Áudio {$abr} kbps" : 'Áudio';
                if ($ext !== '') {
                    $label .= ' · ' . strtoupper($ext);
                }
                $audioFormats[] = [
                    'format_id' => $formatId,
                    'type' => 'audio',
                    'label' => $label,
                    'resolution' => null,
                    'ext' => $ext,
                    'abr' => $abr,
                    'filesize' => $filesize ? (int) $filesize : null,
                ];
            }
        }

        usort($videoFormats, static function (array $a, array $b): int {
            $ha = (int) filter_var($a['resolution'] ?? '0', FILTER_SANITIZE_NUMBER_INT);
            $hb = (int) filter_var($b['resolution'] ?? '0', FILTER_SANITIZE_NUMBER_INT);
            return $hb <=> $ha;
        });
        usort($audioFormats, static fn(array $a, array $b): int => ($b['abr'] ?? 0) <=> ($a['abr'] ?? 0));

        if ($videoFormats === []) {
            $videoFormats[] = [
                'format_id' => 'bestvideo+bestaudio/best',
                'type' => 'video',
                'label' => 'Melhor qualidade disponível',
                'resolution' => null,
                'ext' => 'mp4',
                'fps' => null,
                'filesize' => null,
            ];
        }

        if ($audioFormats === []) {
            $audioFormats[] = [
                'format_id' => 'bestaudio/best',
                'type' => 'audio',
                'label' => 'Melhor áudio disponível',
                'resolution' => null,
                'ext' => 'm4a',
                'abr' => null,
                'filesize' => null,
            ];
        }

        return array_merge(array_slice($videoFormats, 0, 8), array_slice($audioFormats, 0, 4));
    }
}
