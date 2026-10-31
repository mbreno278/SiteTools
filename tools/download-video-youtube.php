<?php

declare(strict_types=1);

/**
 * SiteTools — Download de Vídeo do YouTube
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

/* ---------- Validação de método e entrada ---------- */

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
    respondError('Informe a URL do vídeo.');
}

if (!isValidYoutubeUrl($url)) {
    respondError('URL do YouTube inválida.');
}

/* ---------- Localiza o yt-dlp ---------- */

$ytdlp = findYtDlp();

if ($ytdlp === null) {
    respondError(
        'yt-dlp não encontrado no servidor. Instale com: pip install yt-dlp',
        500
    );
}

/* ---------- Ações ---------- */

if ($action === 'info') {
    handleInfo($ytdlp, $url);
}

if ($action === 'download') {
    $formatId = trim((string) ($input['format_id'] ?? ''));
    if ($formatId === '') {
        respondError('Selecione um formato para download.');
    }
    handleDownload($ytdlp, $url, $formatId);
}

respondError('Ação não reconhecida.');

/* =========================================================
   FUNÇÕES
   ========================================================= */

function isValidYoutubeUrl(string $url): bool
{
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }

    $parts = parse_url($url);
    if ($parts === false || empty($parts['host'])) {
        return false;
    }

    $host = strtolower(preg_replace('/^www\./', '', $parts['host']));

    if ($host === 'youtu.be') {
        return !empty($parts['path']) && strlen($parts['path']) > 1;
    }

    $allowed = ['youtube.com', 'm.youtube.com', 'music.youtube.com'];
    if (!in_array($host, $allowed, true)) {
        return false;
    }

    $path = $parts['path'] ?? '';
    $query = [];
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
    }

    return isset($query['v'])
        || str_starts_with($path, '/shorts/')
        || str_starts_with($path, '/embed/')
        || str_starts_with($path, '/live/')
        || str_starts_with($path, '/watch');
}

function findYtDlp(): ?string
{
    $candidates = [
        'C:\\yt-dlp\\yt-dlp.exe',          // ← caminho que você criou
        'C:\\Windows\\System32\\yt-dlp.exe',
        '/usr/local/bin/yt-dlp',
        '/usr/bin/yt-dlp',
        trim((string) shell_exec('where yt-dlp 2>nul')),
        trim((string) shell_exec('command -v yt-dlp 2>/dev/null')),
    ];

    foreach ($candidates as $path) {
        $path = trim($path);
        if ($path !== '' && is_file($path) && is_executable($path)) {
            return $path;
        }
    }

    // Última tentativa: só o nome (se estiver no PATH)
    $test = trim((string) shell_exec('yt-dlp --version 2>nul'));
    if ($test !== '') {
        return 'yt-dlp';
    }

    return null;
}

function runCommand(array $cmd, int $timeout = 60): array
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

    $result = runCommand($cmd, 45);

    if ($result['exit'] !== 0) {
        $msg = trim($result['stderr']) ?: 'Não foi possível obter dados do vídeo.';
        // Mensagens mais amigáveis
        if (stripos($msg, 'Private video') !== false) {
            $msg = 'Este vídeo é privado.';
        } elseif (stripos($msg, 'Video unavailable') !== false) {
            $msg = 'Vídeo indisponível.';
        } elseif (stripos($msg, 'Sign in') !== false) {
            $msg = 'Este vídeo requer autenticação.';
        }
        respondError($msg, 422);
    }

    $info = json_decode($result['stdout'], true);

    if (!is_array($info)) {
        respondError('Resposta inválida do extrator.', 500);
    }

    $formats = buildFormatList($info);

    respondJson([
        'id'          => $info['id'] ?? null,
        'title'       => $info['title'] ?? 'Sem título',
        'channel'     => $info['channel'] ?? $info['uploader'] ?? null,
        'thumbnail'   => $info['thumbnail'] ?? null,
        'duration'    => isset($info['duration']) ? (int) $info['duration'] : null,
        'view_count'  => isset($info['view_count']) ? (int) $info['view_count'] : null,
        'upload_date' => $info['upload_date'] ?? null,
        'formats'     => $formats,
    ]);
}

function buildFormatList(array $info): array
{
    $raw = $info['formats'] ?? [];
    if (!is_array($raw)) {
        $raw = [];
    }

    $videoFormats = [];
    $audioFormats = [];
    $seen = [];

    foreach ($raw as $f) {
        if (!is_array($f)) {
            continue;
        }

        $formatId = (string) ($f['format_id'] ?? '');
        $ext      = strtolower((string) ($f['ext'] ?? ''));
        $vcodec   = (string) ($f['vcodec'] ?? 'none');
        $acodec   = (string) ($f['acodec'] ?? 'none');
        $height   = isset($f['height']) ? (int) $f['height'] : 0;
        $fps      = isset($f['fps']) ? (int) $f['fps'] : null;
        $abr      = isset($f['abr']) ? (int) round((float) $f['abr']) : null;
        $filesize = $f['filesize'] ?? $f['filesize_approx'] ?? null;

        // Apenas formatos baixáveis
        if ($formatId === '' || isset($f['url']) === false && empty($f['manifest_url'])) {
            // yt-dlp ainda pode baixar mesmo sem url direta
        }

        $hasVideo = $vcodec !== 'none' && $vcodec !== '';
        $hasAudio = $acodec !== 'none' && $acodec !== '';

        // Preferimos formatos combinados (vídeo+áudio) e áudio puro
        if ($hasVideo && $hasAudio) {
            $key = "v{$height}_{$ext}";
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $label = $height > 0 ? "{$height}p" : 'Vídeo';
            if ($ext) {
                $label .= " · " . strtoupper($ext);
            }

            $videoFormats[] = [
                'format_id'  => $formatId,
                'type'       => 'video',
                'label'      => $label,
                'resolution' => $height > 0 ? "{$height}p" : null,
                'ext'        => $ext,
                'fps'        => $fps,
                'filesize'   => $filesize ? (int) $filesize : null,
            ];
        } elseif (!$hasVideo && $hasAudio) {
            $key = "a{$abr}_{$ext}";
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $label = $abr ? "Áudio {$abr} kbps" : 'Áudio';
            if ($ext) {
                $label .= " · " . strtoupper($ext);
            }

            $audioFormats[] = [
                'format_id'  => $formatId,
                'type'       => 'audio',
                'label'      => $label,
                'resolution' => null,
                'ext'        => $ext,
                'abr'        => $abr,
                'filesize'   => $filesize ? (int) $filesize : null,
            ];
        }
    }

    // Ordena vídeos do maior para o menor
    usort($videoFormats, static function ($a, $b) {
        $ha = (int) filter_var($a['resolution'] ?? '0', FILTER_SANITIZE_NUMBER_INT);
        $hb = (int) filter_var($b['resolution'] ?? '0', FILTER_SANITIZE_NUMBER_INT);
        return $hb <=> $ha;
    });

    // Ordena áudios pelo bitrate
    usort($audioFormats, static function ($a, $b) {
        return ($b['abr'] ?? 0) <=> ($a['abr'] ?? 0);
    });

    // Se não houver formatos combinados, oferece os melhores "best" via yt-dlp
    if (empty($videoFormats)) {
        $videoFormats[] = [
            'format_id'  => 'bestvideo+bestaudio/best',
            'type'       => 'video',
            'label'      => 'Melhor qualidade disponível',
            'resolution' => null,
            'ext'        => 'mp4',
            'fps'        => null,
            'filesize'   => null,
        ];
    }

    // Garante pelo menos uma opção de áudio
    if (empty($audioFormats)) {
        $audioFormats[] = [
            'format_id'  => 'bestaudio/best',
            'type'       => 'audio',
            'label'      => 'Melhor áudio disponível',
            'resolution' => null,
            'ext'        => 'm4a',
            'abr'        => null,
            'filesize'   => null,
        ];
    }

    // Limita quantidade para não poluir a UI
    $videoFormats = array_slice($videoFormats, 0, 8);
    $audioFormats = array_slice($audioFormats, 0, 4);

    return array_merge($videoFormats, $audioFormats);
}

function handleDownload(string $ytdlp, string $url, string $formatId): never
{
    // Sanitiza format_id (só caracteres seguros)
    if (!preg_match('/^[a-zA-Z0-9_+\-\/\.]+$/', $formatId)) {
        respondError('Formato inválido.');
    }

    $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sitetools_yt_' . bin2hex(random_bytes(8));

    if (!mkdir($tmpDir, 0700)) {
        respondError('Não foi possível criar diretório temporário.', 500);
    }

    $outputTemplate = $tmpDir . DIRECTORY_SEPARATOR . 'video.%(ext)s';

    $cmd = [
        $ytdlp,
        '--no-warnings',
        '--no-playlist',
        '--format', $formatId,
        '--merge-output-format', 'mp4',
        '-o', $outputTemplate,
        '--no-part',
        '--restrict-filenames',
        $url,
    ];

    $result = runCommand($cmd, 180);

    if ($result['exit'] !== 0) {
        cleanupDir($tmpDir);
        $msg = trim($result['stderr']) ?: 'Falha ao baixar o vídeo.';
        respondError($msg, 422);
    }

    // Localiza o arquivo gerado
    $files = glob($tmpDir . DIRECTORY_SEPARATOR . 'video.*') ?: [];

    if (empty($files)) {
        // Tenta qualquer arquivo no diretório
        $files = array_values(array_filter(
            glob($tmpDir . DIRECTORY_SEPARATOR . '*') ?: [],
            'is_file'
        ));
    }

    if (empty($files)) {
        cleanupDir($tmpDir);
        respondError('Arquivo de saída não encontrado.', 500);
    }

    $filePath = $files[0];
    $filesize = filesize($filePath);
    $ext      = strtolower(pathinfo($filePath, PATHINFO_EXTENSION) ?: 'mp4');

    $mimeMap = [
        'mp4'  => 'video/mp4',
        'webm' => 'video/webm',
        'mkv'  => 'video/x-matroska',
        'm4a'  => 'audio/mp4',
        'mp3'  => 'audio/mpeg',
        'opus' => 'audio/opus',
        'ogg'  => 'audio/ogg',
        'wav'  => 'audio/wav',
    ];

    $mime = $mimeMap[$ext] ?? 'application/octet-stream';
    $filename = 'youtube-video.' . $ext;

    // Tenta obter título limpo para o nome do arquivo
    // (já baixamos, então usamos o nome gerado pelo yt-dlp se possível)
    $originalName = basename($filePath);
    if ($originalName !== '' && $originalName !== 'video.' . $ext) {
        $filename = $originalName;
    }

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . $filesize);
    header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
    header('Cache-Control: no-store');

    // Envia o arquivo e limpa
    $handle = fopen($filePath, 'rb');
    if ($handle) {
        while (!feof($handle)) {
            echo fread($handle, 8192);
            flush();
        }
        fclose($handle);
    }

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
