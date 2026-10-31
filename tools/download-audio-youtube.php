<?php

declare(strict_types=1);

/**
 * SiteTools — Download de Áudio do YouTube
 * Backend em PHP usando yt-dlp (extrai e converte áudio corretamente).
 *
 * Ações:
 *   POST { "action": "info", "url": "..." }
 *   POST { "action": "download", "url": "...", "format_id": "...", "output": "mp3|m4a|best" }
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
    $formatId = trim((string) ($input['format_id'] ?? 'bestaudio'));
    $output   = strtolower(trim((string) ($input['output'] ?? 'mp3')));

    // Formatos de saída permitidos
    $allowedOutputs = ['mp3', 'm4a', 'opus', 'wav', 'best'];
    if (!in_array($output, $allowedOutputs, true)) {
        $output = 'mp3';
    }

    handleDownload($ytdlp, $url, $formatId, $output);
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

function runCommand(array $cmd, int $timeout = 120): array
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
        $msg = trim($result['stderr']) ?: 'Não foi possível obter dados do áudio.';
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

    respondJson([
        'id'          => $info['id'] ?? null,
        'title'       => $info['title'] ?? 'Sem título',
        'channel'     => $info['channel'] ?? $info['uploader'] ?? null,
        'thumbnail'   => $info['thumbnail'] ?? null,
        'duration'    => isset($info['duration']) ? (int) $info['duration'] : null,
        'view_count'  => isset($info['view_count']) ? (int) $info['view_count'] : null,
        'upload_date' => $info['upload_date'] ?? null,
        'formats'     => buildAudioFormatList(),
    ]);
}

/**
 * Lista fixa e confiável de opções de áudio.
 * Evita formatos nativos quebrados — sempre converte via ffmpeg.
 */
function buildAudioFormatList(): array
{
    return [
        [
            'format_id' => 'bestaudio',
            'output'    => 'mp3',
            'type'      => 'audio',
            'label'     => 'MP3 · Alta qualidade',
            'ext'       => 'mp3',
            'abr'       => 192,
            'acodec'    => 'mp3',
            'filesize'  => null,
        ],
        [
            'format_id' => 'bestaudio',
            'output'    => 'mp3',
            'type'      => 'audio',
            'label'     => 'MP3 · Qualidade máxima',
            'ext'       => 'mp3',
            'abr'       => 320,
            'acodec'    => 'mp3',
            'filesize'  => null,
            'quality'   => '0',
        ],
        [
            'format_id' => 'bestaudio',
            'output'    => 'm4a',
            'type'      => 'audio',
            'label'     => 'M4A (AAC) · Alta qualidade',
            'ext'       => 'm4a',
            'abr'       => 192,
            'acodec'    => 'aac',
            'filesize'  => null,
        ],
        [
            'format_id' => 'bestaudio',
            'output'    => 'opus',
            'type'      => 'audio',
            'label'     => 'OPUS · Compacto e nítido',
            'ext'       => 'opus',
            'abr'       => 160,
            'acodec'    => 'opus',
            'filesize'  => null,
        ],
        [
            'format_id' => 'bestaudio',
            'output'    => 'wav',
            'type'      => 'audio',
            'label'     => 'WAV · Sem compressão',
            'ext'       => 'wav',
            'abr'       => null,
            'acodec'    => 'pcm',
            'filesize'  => null,
        ],
    ];
}

function handleDownload(string $ytdlp, string $url, string $formatId, string $output): never
{
    if (!preg_match('/^[a-zA-Z0-9_+\-\/\.]+$/', $formatId)) {
        respondError('Formato inválido.');
    }

    $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sitetools_yta_' . bin2hex(random_bytes(8));

    if (!mkdir($tmpDir, 0700)) {
        respondError('Não foi possível criar diretório temporário.', 500);
    }

    // Nome de saída previsível
    $outFile = $tmpDir . DIRECTORY_SEPARATOR . 'audio.' . $output;

    // Comando robusto: baixa o melhor áudio e converte com ffmpeg
    // --audio-quality 0 = melhor qualidade na conversão
    $cmd = [
        $ytdlp,
        '--no-warnings',
        '--no-playlist',
        '--extract-audio',
        '--audio-format', $output,
        '--audio-quality', '0',
        '--prefer-ffmpeg',
        '--postprocessor-args', 'ffmpeg:-ar 44100',
        '-o', $tmpDir . DIRECTORY_SEPARATOR . 'audio.%(ext)s',
        '--no-part',
        '--restrict-filenames',
        '--newline',
        $url,
    ];

    $result = runCommand($cmd, 180);

    if ($result['exit'] !== 0) {
        // Fallback mais simples (sem postprocessor-args)
        $cmdFallback = [
            $ytdlp,
            '--no-warnings',
            '--no-playlist',
            '-x',
            '--audio-format', $output,
            '--audio-quality', '0',
            '-o', $tmpDir . DIRECTORY_SEPARATOR . 'audio.%(ext)s',
            '--no-part',
            '--restrict-filenames',
            $url,
        ];

        $result = runCommand($cmdFallback, 180);

        if ($result['exit'] !== 0) {
            cleanupDir($tmpDir);
            $msg = trim($result['stderr']) ?: 'Falha ao extrair o áudio.';
            // Mensagem mais clara se faltar ffmpeg
            if (stripos($msg, 'ffmpeg') !== false || stripos($msg, 'ffprobe') !== false) {
                $msg = 'ffmpeg não encontrado. Instale o ffmpeg e adicione no PATH do sistema.';
            }
            respondError($msg, 422);
        }
    }

    // Localiza o arquivo gerado (yt-dlp pode mudar a extensão)
    $files = array_values(array_filter(
        glob($tmpDir . DIRECTORY_SEPARATOR . '*') ?: [],
        static fn($f) => is_file($f) && !str_ends_with($f, '.part')
    ));

    if (empty($files)) {
        cleanupDir($tmpDir);
        respondError('Arquivo de áudio não encontrado após a conversão.', 500);
    }

    // Prefere o arquivo com a extensão pedida
    $filePath = $files[0];
    foreach ($files as $f) {
        if (str_ends_with(strtolower($f), '.' . $output)) {
            $filePath = $f;
            break;
        }
    }

    $filesize = filesize($filePath);
    if ($filesize === false || $filesize < 1000) {
        cleanupDir($tmpDir);
        respondError('O arquivo de áudio gerado está vazio ou corrompido. Verifique se o ffmpeg está instalado.', 500);
    }

    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION) ?: $output);

    $mimeMap = [
        'mp3'  => 'audio/mpeg',
        'm4a'  => 'audio/mp4',
        'aac'  => 'audio/aac',
        'opus' => 'audio/opus',
        'ogg'  => 'audio/ogg',
        'wav'  => 'audio/wav',
        'flac' => 'audio/flac',
        'webm' => 'audio/webm',
    ];

    $mime = $mimeMap[$ext] ?? 'application/octet-stream';

    // Nome amigável
    $filename = 'youtube-audio.' . $ext;

    // Limpa buffers antes de enviar o arquivo binário
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
