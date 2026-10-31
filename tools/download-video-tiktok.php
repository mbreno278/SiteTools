<?php

declare(strict_types=1);

/**
 * SiteTools — Download de Vídeo do TikTok
 * Backend em PHP usando yt-dlp.
 *
 * POST { "action": "info", "url": "..." }
 * POST { "action": "download", "url": "...", "format_id": "..." }
 */

header('X-Content-Type-Options: nosniff');

function respondJson(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function respondError(string $message, int $status = 400): never
{
    respondJson(['error' => $message], $status);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respondError('Método não permitido.', 405);
}

$raw = file_get_contents('php://input');
$input = json_decode($raw ?: '', true);

if (!is_array($input)) {
    respondError('Corpo da requisição inválido.');
}

$action = (string) ($input['action'] ?? '');
$url    = trim((string) ($input['url'] ?? ''));

if ($url === '') {
    respondError('Informe a URL do TikTok.');
}

if (!isValidTikTokUrl($url)) {
    respondError('URL do TikTok inválida.');
}

$ytdlp = findYtDlp();

if ($ytdlp === null) {
    respondError(
        'yt-dlp não encontrado no servidor. Instale com: pip install yt-dlp ou baixe o .exe',
        500
    );
}

if ($action === 'info') {
    handleInfo($ytdlp, $url);
}

if ($action === 'download') {
    $formatId = trim((string) ($input['format_id'] ?? 'bv*+ba/b'));
    if ($formatId === '') {
        $formatId = 'bv*+ba/b';
    }
    handleDownload($ytdlp, $url, $formatId);
}

respondError('Ação não reconhecida.');

/* =========================================================
   FUNÇÕES
   ========================================================= */

function isValidTikTokUrl(string $url): bool
{
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }

    $parts = parse_url($url);
    if ($parts === false || empty($parts['host'])) {
        return false;
    }

    $host = strtolower(preg_replace('/^www\./', '', $parts['host']));

    return $host === 'tiktok.com'
        || $host === 'vm.tiktok.com'
        || $host === 'vt.tiktok.com'
        || $host === 'm.tiktok.com'
        || str_ends_with($host, '.tiktok.com');
}

function findYtDlp(): ?string
{
    $candidates = [
        'C:\\yt-dlp\\yt-dlp.exe',
        'C:\\Windows\\System32\\yt-dlp.exe',
        '/usr/local/bin/yt-dlp',
        '/usr/bin/yt-dlp',
        trim((string) shell_exec('where yt-dlp 2>nul')),
        trim((string) shell_exec('command -v yt-dlp 2>/dev/null')),
    ];

    foreach ($candidates as $path) {
        $path = trim((string) $path);
        if ($path !== '' && is_file($path) && is_executable($path)) {
            return $path;
        }
    }

    $test = trim((string) shell_exec('yt-dlp --version 2>nul'));
    if ($test === '') {
        $test = trim((string) shell_exec('yt-dlp --version 2>/dev/null'));
    }
    if ($test !== '') {
        return 'yt-dlp';
    }

    return null;
}

function runCommand(array $cmd, int $timeout = 90): array
{
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open(
        $cmd,
        $descriptors,
        $pipes,
        null,
        null,
        ['bypass_shell' => true]
    );

    if (!is_resource($process)) {
        return ['exit' => -1, 'stdout' => '', 'stderr' => 'Falha ao iniciar processo.'];
    }

    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    $stdout = '';
    $stderr = '';
    $start  = time();

    while (true) {
        $status = proc_get_status($process);
        $stdout .= stream_get_contents($pipes[1]) ?: '';
        $stderr .= stream_get_contents($pipes[2]) ?: '';

        if (!$status['running']) {
            break;
        }

        if ((time() - $start) > $timeout) {
            proc_terminate($process, 9);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
            return ['exit' => -1, 'stdout' => $stdout, 'stderr' => 'Tempo limite excedido.'];
        }

        usleep(100000);
    }

    $stdout .= stream_get_contents($pipes[1]) ?: '';
    $stderr .= stream_get_contents($pipes[2]) ?: '';
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($process);

    return ['exit' => $exit, 'stdout' => $stdout, 'stderr' => $stderr];
}

function friendlyTikTokError(?string $stderr): string
{
    $msg = trim((string) $stderr);

    if ($msg === '') {
        return 'Não foi possível obter dados do vídeo.';
    }

    if (stripos($msg, 'Private') !== false || stripos($msg, 'login') !== false) {
        return 'Este vídeo é privado ou requer login.';
    }

    if (
        stripos($msg, 'Unavailable') !== false
        || stripos($msg, 'not available') !== false
        || stripos($msg, 'Video not found') !== false
    ) {
        return 'Vídeo indisponível ou removido.';
    }

    if (stripos($msg, 'No video formats found') !== false) {
        return 'Formato não suportado para download. Apenas vídeos do TikTok são aceitos.';
    }

    if (stripos($msg, 'rate-limit') !== false || stripos($msg, '429') !== false) {
        return 'TikTok limitou as requisições. Tente novamente em alguns minutos.';
    }

    if (stripos($msg, 'ffmpeg') !== false) {
        return 'ffmpeg não encontrado. Instale o ffmpeg para mesclar vídeo e áudio.';
    }

    if (preg_match('/^ERROR:\s*/i', $msg)) {
        $lines = preg_split('/\r\n|\r|\n/', $msg) ?: [];
        $first = trim($lines[0] ?? $msg);
        $first = preg_replace('/\s*;?\s*please report this issue.*/i', '', $first) ?? $first;
        $first = preg_replace('/^ERROR:\s*(\[TikTok\]\s*)?/i', '', $first) ?? $first;

        if (mb_strlen($first) > 160 || $first === '') {
            return 'Não foi possível processar este link. Verifique se o vídeo é público.';
        }

        return $first;
    }

    return 'Não foi possível processar este link. Verifique se o vídeo é público.';
}

function handleInfo(string $ytdlp, string $url): never
{
    $cmd = [
        $ytdlp,
        '--dump-single-json',
        '--no-warnings',
        '--no-playlist',
        '--skip-download',
        $url,
    ];

    $result = runCommand($cmd, 60);

    if ($result['exit'] !== 0) {
        respondError(friendlyTikTokError($result['stderr']), 422);
    }

    $info = json_decode($result['stdout'], true);

    if (!is_array($info)) {
        respondError('Resposta inválida do extrator.', 500);
    }

    $formats = buildFormatList($info);

    if (empty($formats)) {
        respondError('Nenhum formato de vídeo disponível para este link.', 422);
    }

    $title = $info['title'] ?? $info['description'] ?? 'Vídeo do TikTok';
    if (is_string($title) && mb_strlen($title) > 120) {
        $title = mb_substr($title, 0, 117) . '...';
    }

    $channel = $info['uploader'] ?? $info['creator'] ?? $info['channel'] ?? null;
    if (is_string($channel)) {
        $channel = ltrim($channel, '@');
    }

    respondJson([
        'id'            => $info['id'] ?? null,
        'title'         => $title,
        'channel'       => $channel,
        'thumbnail'     => $info['thumbnail'] ?? null,
        'duration'      => isset($info['duration']) ? (int) $info['duration'] : null,
        'view_count'    => isset($info['view_count']) ? (int) $info['view_count'] : null,
        'like_count'    => isset($info['like_count']) ? (int) $info['like_count'] : null,
        'comment_count' => isset($info['comment_count']) ? (int) $info['comment_count'] : null,
        'formats'       => $formats,
    ]);
}

function buildFormatList(array $info): array
{
    // Lista fixa e compatível: sempre prioritiza H.264 (avc1) para evitar tela preta
    return [
        [
            'format_id'  => 'h264',
            'type'       => 'video',
            'label'      => 'MP4 · H.264 (compatível)',
            'resolution' => null,
            'ext'        => 'mp4',
            'filesize'   => null,
        ],
        [
            'format_id'  => 'h264_720',
            'type'       => 'video',
            'label'      => '720p · H.264 (compatível)',
            'resolution' => '720p',
            'ext'        => 'mp4',
            'filesize'   => null,
        ],
        [
            'format_id'  => 'h264_480',
            'type'       => 'video',
            'label'      => '480p · H.264 (compatível)',
            'resolution' => '480p',
            'ext'        => 'mp4',
            'filesize'   => null,
        ],
        [
            'format_id'  => 'ba/b',
            'type'       => 'audio',
            'label'      => 'Apenas áudio',
            'resolution' => null,
            'ext'        => 'm4a',
            'filesize'   => null,
        ],
    ];
}

/**
 * Resolve o seletor real do yt-dlp a partir do format_id da UI.
 * Prioriza H.264 (avc1) para máxima compatibilidade de players.
 */
function resolveFormatSelector(string $formatId): string
{
    return match ($formatId) {
        'h264'     => 'bv*[vcodec^=avc1]+ba[ext=m4a]/bv*[vcodec^=avc1]+ba/b[ext=mp4]/b',
        'h264_720' => 'bv*[vcodec^=avc1][height<=720]+ba[ext=m4a]/bv*[height<=720]+ba/b',
        'h264_480' => 'bv*[vcodec^=avc1][height<=480]+ba[ext=m4a]/bv*[height<=480]+ba/b',
        'ba/b'     => 'ba/b',
        default    => $formatId,
    };
}

function handleDownload(string $ytdlp, string $url, string $formatId): never
{
    if (!preg_match('/^[a-zA-Z0-9_+\-\/\.\[\]=\*]+$/', $formatId)) {
        respondError('Formato inválido.');
    }

    $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sitetools_tt_' . bin2hex(random_bytes(8));

    if (!mkdir($tmpDir, 0700)) {
        respondError('Não foi possível criar diretório temporário.', 500);
    }

    $outputTemplate = $tmpDir . DIRECTORY_SEPARATOR . 'media.%(ext)s';
    $isAudioOnly = ($formatId === 'ba/b' || $formatId === 'bestaudio' || str_starts_with($formatId, 'ba'));

    if ($isAudioOnly) {
        $cmd = [
            $ytdlp,
            '--no-warnings',
            '--no-playlist',
            '--extract-audio',
            '--audio-format', 'm4a',
            '--audio-quality', '0',
            '-o', $outputTemplate,
            '--no-part',
            '--restrict-filenames',
            $url,
        ];

        $result = runCommand($cmd, 180);

        if ($result['exit'] !== 0) {
            cleanupDir($tmpDir);
            respondError(friendlyTikTokError($result['stderr'] ?: 'Falha ao baixar o áudio.'), 422);
        }
    } else {
        // Baixa preferindo H.264 nativo; se não houver, pega o melhor e converte depois
        $selector = resolveFormatSelector($formatId);

        $cmd = [
            $ytdlp,
            '--no-warnings',
            '--no-playlist',
            '--format', $selector,
            '--merge-output-format', 'mp4',
            '-o', $outputTemplate,
            '--no-part',
            '--restrict-filenames',
            $url,
        ];

        $result = runCommand($cmd, 180);

        if ($result['exit'] !== 0) {
            $cmdFallback = [
                $ytdlp,
                '--no-warnings',
                '--no-playlist',
                '-f', 'bv*+ba/b',
                '--merge-output-format', 'mp4',
                '-o', $outputTemplate,
                '--no-part',
                '--restrict-filenames',
                $url,
            ];

            $result = runCommand($cmdFallback, 180);

            if ($result['exit'] !== 0) {
                $cmdLast = [
                    $ytdlp,
                    '--no-warnings',
                    '--no-playlist',
                    '-f', 'best',
                    '--merge-output-format', 'mp4',
                    '-o', $outputTemplate,
                    '--no-part',
                    '--restrict-filenames',
                    $url,
                ];

                $result = runCommand($cmdLast, 180);

                if ($result['exit'] !== 0) {
                    cleanupDir($tmpDir);
                    respondError(friendlyTikTokError($result['stderr'] ?: 'Falha ao baixar o vídeo.'), 422);
                }
            }
        }

        // Garante H.264 + AAC (evita tela preta em players comuns)
        $downloaded = findOutputFile($tmpDir);
        if ($downloaded !== null) {
            ensureCompatibleMp4($downloaded, $tmpDir);
        }
    }

    $files = array_values(array_filter(
        glob($tmpDir . DIRECTORY_SEPARATOR . '*') ?: [],
        static fn($f) => is_file($f) && !str_ends_with($f, '.part')
    ));

    if (empty($files)) {
        cleanupDir($tmpDir);
        respondError('Arquivo de saída não encontrado.', 500);
    }

    // Prefere media.mp4 (após conversão H.264)
    $filePath = $files[0];
    foreach ($files as $f) {
        if (str_ends_with(strtolower($f), '.mp4')) {
            $filePath = $f;
            break;
        }
    }
    $filesize = filesize($filePath);

    if ($filesize === false || $filesize < 500) {
        cleanupDir($tmpDir);
        respondError('O arquivo gerado está vazio ou corrompido.', 500);
    }

    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION) ?: 'mp4');

    $mimeMap = [
        'mp4'  => 'video/mp4',
        'webm' => 'video/webm',
        'mkv'  => 'video/x-matroska',
        'm4a'  => 'audio/mp4',
        'mp3'  => 'audio/mpeg',
        'aac'  => 'audio/aac',
    ];

    $mime = $mimeMap[$ext] ?? 'application/octet-stream';
    $filename = 'tiktok-video.' . $ext;

    $originalName = basename($filePath);
    if ($originalName !== '' && $originalName !== 'media.' . $ext) {
        $filename = $originalName;
    }

    if (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . $filesize);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');

    $handle = fopen($filePath, 'rb');
    if ($handle === false) {
        cleanupDir($tmpDir);
        respondError('Não foi possível ler o arquivo gerado.', 500);
    }

    while (!feof($handle)) {
        echo fread($handle, 8192);
        flush();
    }
    fclose($handle);

    cleanupDir($tmpDir);
    exit;
}

function findOutputFile(string $tmpDir): ?string
{
    $files = array_values(array_filter(
        glob($tmpDir . DIRECTORY_SEPARATOR . '*') ?: [],
        static fn($f) => is_file($f) && !str_ends_with($f, '.part')
    ));

    return $files[0] ?? null;
}

/**
 * Se o vídeo não for H.264, reconverte com ffmpeg para H.264 + AAC.
 * Isso evita tela preta em players que não suportam HEVC/H.265 ou AV1.
 */
function ensureCompatibleMp4(string $inputPath, string $tmpDir): void
{
    $ffmpeg = findFfmpeg();
    if ($ffmpeg === null) {
        return; // sem ffmpeg, entrega o arquivo original
    }

    // Verifica o codec de vídeo
    $probe = runCommand([
        $ffmpeg === 'ffmpeg' ? 'ffprobe' : str_replace('ffmpeg', 'ffprobe', $ffmpeg),
        '-v', 'error',
        '-select_streams', 'v:0',
        '-show_entries', 'stream=codec_name',
        '-of', 'default=noprint_wrappers=1:nokey=1',
        $inputPath,
    ], 30);

    $codec = strtolower(trim($probe['stdout'] ?? ''));

    // Já é H.264 → não precisa reconverter
    if ($codec === 'h264' || $codec === 'avc1' || $codec === 'avc') {
        return;
    }

    $outputPath = $tmpDir . DIRECTORY_SEPARATOR . 'compatible.mp4';

    $cmd = [
        $ffmpeg,
        '-y',
        '-i', $inputPath,
        '-c:v', 'libx264',
        '-preset', 'fast',
        '-crf', '23',
        '-c:a', 'aac',
        '-b:a', '192k',
        '-movflags', '+faststart',
        '-pix_fmt', 'yuv420p',
        $outputPath,
    ];

    $result = runCommand($cmd, 300);

    if ($result['exit'] === 0 && is_file($outputPath) && filesize($outputPath) > 500) {
        @unlink($inputPath);
        // Mantém o nome final como media.mp4
        $finalPath = $tmpDir . DIRECTORY_SEPARATOR . 'media.mp4';
        @rename($outputPath, $finalPath);
    }
}

function findFfmpeg(): ?string
{
    $candidates = [
        'C:\\ffmpeg\\bin\\ffmpeg.exe',
        'C:\\Windows\\System32\\ffmpeg.exe',
        '/usr/bin/ffmpeg',
        '/usr/local/bin/ffmpeg',
        trim((string) shell_exec('where ffmpeg 2>nul')),
        trim((string) shell_exec('command -v ffmpeg 2>/dev/null')),
    ];

    foreach ($candidates as $path) {
        $path = trim((string) $path);
        if ($path !== '' && is_file($path) && is_executable($path)) {
            return $path;
        }
    }

    $test = trim((string) shell_exec('ffmpeg -version 2>nul'));
    if ($test === '') {
        $test = trim((string) shell_exec('ffmpeg -version 2>/dev/null'));
    }
    if ($test !== '') {
        return 'ffmpeg';
    }

    return null;
}

function cleanupDir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }

    @rmdir($dir);
}
