<?php

declare(strict_types=1);

/**
 * Caminhos dos conversores externos.
 * Pode ser sobrescrito por variáveis de ambiente:
 *   LIBREOFFICE_PATH, GHOSTSCRIPT_PATH, QPDF_PATH, YTDLP_PATH
 */

require_once __DIR__ . '/env.php';

$isWindows = PHP_OS_FAMILY === 'Windows';

$libreCandidates = array_filter([
    env('LIBREOFFICE_PATH'),
    $isWindows ? 'C:\\Program Files\\LibreOffice\\program\\soffice.exe' : null,
    $isWindows ? 'C:\\Program Files\\LibreOffice\\program\\soffice.com' : null,
    $isWindows ? 'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe' : null,
    '/usr/bin/soffice',
    '/usr/bin/libreoffice',
    '/usr/lib/libreoffice/program/soffice',
    '/snap/bin/libreoffice',
]);

$gsCandidates = array_filter([
    env('GHOSTSCRIPT_PATH'),
    $isWindows ? 'C:\\Program Files\\gs\\gs10.08.0\\bin\\gswin64c.exe' : null,
    $isWindows ? 'C:\\Program Files\\gs\\gs10.05.0\\bin\\gswin64c.exe' : null,
    $isWindows ? 'C:\\Program Files\\gs\\gs10.04.0\\bin\\gswin64c.exe' : null,
    'gswin64c',
    'gs',
    '/usr/bin/gs',
    '/usr/local/bin/gs',
]);

$qpdfCandidates = array_filter([
    env('QPDF_PATH'),
    $isWindows ? 'C:\\Program Files\\qpdf\\bin\\qpdf.exe' : null,
    'qpdf',
    '/usr/bin/qpdf',
    '/usr/local/bin/qpdf',
]);

$ytdlpCandidates = array_filter([
    env('YTDLP_PATH'),
    $isWindows ? 'C:\\yt-dlp\\yt-dlp.exe' : null,
    $isWindows ? 'C:\\Tools\\yt-dlp\\yt-dlp.exe' : null,
    'yt-dlp',
    '/usr/local/bin/yt-dlp',
    '/usr/bin/yt-dlp',
]);

$pick = static function (array $candidates): ?string {
    foreach ($candidates as $path) {
        $path = trim((string) $path);
        if ($path === '') {
            continue;
        }
        if (is_file($path)) {
            return $path;
        }
        // Comando no PATH (nome simples)
        if (!str_contains($path, DIRECTORY_SEPARATOR) && !str_contains($path, '/')) {
            return $path;
        }
    }
    return null;
};

return [
    'libreoffice' => $pick($libreCandidates),
    'ghostscript' => $pick($gsCandidates),
    'qpdf' => $pick($qpdfCandidates),
    'ytdlp' => $pick($ytdlpCandidates),
    'max_file_size' => (int) env('UPLOAD_MAX_BYTES', 20 * 1024 * 1024),
    'process_timeout' => (int) env('PROCESS_TIMEOUT', 180),
];
