<?php

declare(strict_types=1);

require __DIR__ . '/_csrf_guard.php';


/**
 * SiteTools — Download de Vídeo / Reels do Instagram
 * Backend em PHP usando yt-dlp.
 *
 * Ações:
 *   POST { "action": "info", "url": "..." }
 *   POST { "action": "download", "url": "...", "format_id": "..." }
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
    respondError('Informe a URL do Instagram.');
}

if (!isValidInstagramUrl($url)) {
    respondError('URL do Instagram inválida. Use links de Reel, Post, IGTV ou Story.');
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
    $formatId = trim((string) ($input['format_id'] ?? 'best'));
    if ($formatId === '') {
        $formatId = 'best';
    }
    handleDownload($ytdlp, $url, $formatId);
}

respondError('Ação não reconhecida.');

/* =========================================================
   FUNÇÕES
   ========================================================= */

function isValidInstagramUrl(string $url): bool
{
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }

    $parts = parse_url($url);
    if ($parts === false || empty($parts['host'])) {
        return false;
    }

    $host = strtolower(preg_replace('/^www\./', '', $parts['host']));
    if ($host !== 'instagram.com' && $host !== 'instagr.am') {
        return false;
    }

    $path = $parts['path'] ?? '';

    return str_starts_with($path, '/p/')
        || str_starts_with($path, '/reel/')
        || str_starts_with($path, '/reels/')
        || str_starts_with($path, '/tv/')
        || str_starts_with($path, '/stories/');
}

function detectInstagramType(string $url): string
{
    if (str_contains($url, '/reel') || str_contains($url, '/reels/')) {
        return 'Reel';
    }
    if (str_contains($url, '/tv/')) {
        return 'IGTV';
    }
    if (str_contains($url, '/stories/')) {
        return 'Story';
    }
    return 'Post';
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
        respondError(friendlyInstagramError($result['stderr']), 422);
    }

    $info = json_decode($result['stdout'], true);

    if (!is_array($info)) {
        respondError('Resposta inválida do extrator.', 500);
    }

    // Post só com imagem (ou carrossel de imagens) — sem vídeo
    if (isImageOnlyPost($info)) {
        respondError(
            'Este post contém apenas imagem. O download está disponível somente para vídeos, Reels e IGTV.',
            422
        );
    }

    $formats = buildFormatList($info);

    if (empty($formats)) {
        respondError(
            'Formato não suportado para download. Apenas vídeo é possível baixar (Reels, posts com vídeo ou IGTV).',
            422
        );
    }

    // Descrição / título
    $title = $info['title'] ?? $info['description'] ?? 'Vídeo do Instagram';
    if (is_string($title) && mb_strlen($title) > 120) {
        $title = mb_substr($title, 0, 117) . '...';
    }

    $channel = $info['uploader'] ?? $info['channel'] ?? $info['creator'] ?? null;
    if (is_string($channel)) {
        $channel = ltrim($channel, '@');
    }

    respondJson([
        'id'          => $info['id'] ?? null,
        'title'       => $title,
        'description' => $info['description'] ?? null,
        'channel'     => $channel,
        'thumbnail'   => $info['thumbnail'] ?? null,
        'duration'    => isset($info['duration']) ? (int) $info['duration'] : null,
        'view_count'  => isset($info['view_count']) ? (int) $info['view_count'] : null,
        'like_count'  => isset($info['like_count']) ? (int) $info['like_count'] : null,
        'type'        => detectInstagramType($url),
        'formats'     => $formats,
    ]);
}

/**
 * Detecta post que só tem imagem (sem vídeo).
 */
function isImageOnlyPost(array $info): bool
{
    // Duração 0 ou ausente + extensões de imagem
    $duration = $info['duration'] ?? null;
    $ext = strtolower((string) ($info['ext'] ?? ''));

    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return true;
    }

    $formats = $info['formats'] ?? [];
    if (!is_array($formats) || $formats === []) {
        // Sem formatos de mídia de vídeo
        if ($duration === null || (int) $duration === 0) {
            return true;
        }
        return false;
    }

    $hasVideo = false;
    foreach ($formats as $f) {
        if (!is_array($f)) {
            continue;
        }
        $vcodec = (string) ($f['vcodec'] ?? 'none');
        $fext   = strtolower((string) ($f['ext'] ?? ''));
        if ($vcodec !== 'none' && $vcodec !== '') {
            $hasVideo = true;
            break;
        }
        if (in_array($fext, ['mp4', 'webm', 'mkv', 'mov'], true)) {
            $hasVideo = true;
            break;
        }
    }

    return !$hasVideo;
}

/**
 * Converte erros técnicos do yt-dlp em mensagens amigáveis.
 */
function friendlyInstagramError(?string $stderr): string
{
    $msg = trim((string) $stderr);

    if ($msg === '') {
        return 'Não foi possível obter dados do conteúdo.';
    }

    // Post / carrossel só com imagem
    if (
        stripos($msg, 'No video formats found') !== false
        || stripos($msg, 'no video') !== false
        || stripos($msg, 'There is no video') !== false
    ) {
        return 'Formato não suportado para download. Apenas vídeo é possível baixar (Reels, posts com vídeo ou IGTV).';
    }

    if (stripos($msg, 'Private') !== false || stripos($msg, 'login') !== false) {
        return 'Este conteúdo é privado ou requer login.';
    }

    if (stripos($msg, 'Unavailable') !== false || stripos($msg, 'not available') !== false) {
        return 'Conteúdo indisponível ou removido.';
    }

    if (stripos($msg, 'rate-limit') !== false || stripos($msg, '429') !== false) {
        return 'Instagram limitou as requisições. Tente novamente em alguns minutos.';
    }

    if (stripos($msg, 'ffmpeg') !== false) {
        return 'ffmpeg não encontrado. Instale o ffmpeg para mesclar vídeo e áudio.';
    }

    // Remove o spam de "please report this issue on github..."
    if (preg_match('/^ERROR:\s*/i', $msg)) {
        // Pega só a primeira linha útil
        $lines = preg_split('/\r\n|\r|\n/', $msg) ?: [];
        $first = trim($lines[0] ?? $msg);
        $first = preg_replace('/\s*;?\s*please report this issue.*/i', '', $first) ?? $first;
        $first = preg_replace('/^ERROR:\s*(\[Instagram\]\s*)?/i', '', $first) ?? $first;

        if (stripos($first, 'No video formats found') !== false) {
            return 'Formato não suportado para download. Apenas vídeo é possível baixar (Reels, posts com vídeo ou IGTV).';
        }

        // Mensagem curta, sem o texto técnico longo
        if (mb_strlen($first) > 160) {
            return 'Não foi possível processar este link. Use apenas links de vídeo, Reel ou IGTV.';
        }

        return $first !== ''
            ? $first
            : 'Não foi possível processar este link. Use apenas links de vídeo, Reel ou IGTV.';
    }

    return 'Não foi possível processar este link. Use apenas links de vídeo, Reel ou IGTV.';
}

function buildFormatList(array $info): array
{
    $raw = $info['formats'] ?? [];
    if (!is_array($raw)) {
        $raw = [];
    }

    $heights = [];

    foreach ($raw as $f) {
        if (!is_array($f)) {
            continue;
        }

        $vcodec = (string) ($f['vcodec'] ?? 'none');
        $height = isset($f['height']) ? (int) $f['height'] : 0;

        // Só considera streams de vídeo (com ou sem áudio)
        if ($vcodec === 'none' || $vcodec === '' || $height <= 0) {
            continue;
        }

        $heights[$height] = true;
    }

    krsort($heights, SORT_NUMERIC);

    $formats = [];

    // Opções por resolução — SEMPRE mesclando com o melhor áudio
    // Sintaxe yt-dlp: "bv*[height=720]+ba/b" = melhor vídeo 720p + melhor áudio
    foreach (array_keys($heights) as $height) {
        if (count($formats) >= 5) {
            break;
        }

        $formats[] = [
            'format_id'  => "bv*[height={$height}]+ba/b",
            'type'       => 'video',
            'label'      => "{$height}p · com áudio",
            'resolution' => "{$height}p",
            'ext'        => 'mp4',
            'fps'        => null,
            'filesize'   => null,
        ];
    }

    // Sempre no topo: melhor qualidade possível com áudio
    array_unshift($formats, [
        'format_id'  => 'bv*+ba/b',
        'type'       => 'video',
        'label'      => 'Melhor qualidade · com áudio',
        'resolution' => null,
        'ext'        => 'mp4',
        'fps'        => null,
        'filesize'   => null,
    ]);

    // Opção só de áudio
    $formats[] = [
        'format_id'  => 'ba/b',
        'type'       => 'audio',
        'label'      => 'Apenas áudio',
        'resolution' => null,
        'ext'        => 'm4a',
        'abr'        => null,
        'filesize'   => null,
    ];

    // Remove duplicatas de format_id
    $unique = [];
    $seen = [];
    foreach ($formats as $fmt) {
        if (isset($seen[$fmt['format_id']])) {
            continue;
        }
        $seen[$fmt['format_id']] = true;
        $unique[] = $fmt;
    }

    return $unique;
}

function handleDownload(string $ytdlp, string $url, string $formatId): never
{
    // Permite sintaxe yt-dlp: bv*+ba/b, bv*[height=720]+ba/b, etc.
    if (!preg_match('/^[a-zA-Z0-9_+\-\/\.\[\]=\*]+$/', $formatId)) {
        respondError('Formato inválido.');
    }

    $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sitetools_ig_' . bin2hex(random_bytes(8));

    if (!mkdir($tmpDir, 0700)) {
        respondError('Não foi possível criar diretório temporário.', 500);
    }

    $outputTemplate = $tmpDir . DIRECTORY_SEPARATOR . 'media.%(ext)s';

    // Instagram separa vídeo e áudio → sempre força merge com ffmpeg
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
    } else {
        // bv*+ba/b = melhor vídeo + melhor áudio, depois merge em mp4
        $cmd = [
            $ytdlp,
            '--no-warnings',
            '--no-playlist',
            '--format', $formatId,
            '--merge-output-format', 'mp4',
            '--postprocessor-args', 'ffmpeg:-c:v copy -c:a aac -b:a 192k',
            '-o', $outputTemplate,
            '--no-part',
            '--restrict-filenames',
            $url,
        ];
    }

    $result = runCommand($cmd, 180);

    if ($result['exit'] !== 0) {
        // Fallback: força merge vídeo+áudio na melhor qualidade
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
            // Último recurso
            $cmdLast = [
                $ytdlp,
                '--no-warnings',
                '--no-playlist',
                '-f', 'best',
                '-o', $outputTemplate,
                '--no-part',
                '--restrict-filenames',
                $url,
            ];

            $result = runCommand($cmdLast, 180);

            if ($result['exit'] !== 0) {
                cleanupDir($tmpDir);
                respondError(friendlyInstagramError($result['stderr'] ?: 'Falha ao baixar o vídeo.'), 422);
            }
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

    $filePath = $files[0];
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
        'mov'  => 'video/quicktime',
        'm4a'  => 'audio/mp4',
        'mp3'  => 'audio/mpeg',
        'aac'  => 'audio/aac',
        'opus' => 'audio/opus',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
    ];

    $mime = $mimeMap[$ext] ?? 'application/octet-stream';
    $filename = 'instagram-media.' . $ext;

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
