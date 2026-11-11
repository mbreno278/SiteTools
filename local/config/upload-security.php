<?php

declare(strict_types=1);

/**
 * SiteTools — validação e armazenamento seguro de uploads
 *
 * Nunca confiar apenas em $_FILES['type'] ou na extensão informada pelo cliente.
 */

/**
 * @param array $file Elemento de $_FILES['campo']
 * @param array{max_bytes?:int,extensions?:list<string>,mime_map?:array<string,list<string>>} $opts
 * @return array{ok:bool,error?:string,ext?:string,mime?:string,tmp?:string,original?:string,size?:int}
 */
function upload_validate(array $file, array $opts = []): array
{
    $maxBytes = (int) ($opts['max_bytes'] ?? (20 * 1024 * 1024));
    $extensions = array_map('strtolower', $opts['extensions'] ?? []);
    $mimeMap = $opts['mime_map'] ?? upload_default_mime_map();

    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        $messages = [
            UPLOAD_ERR_INI_SIZE   => 'Arquivo excede o limite do servidor.',
            UPLOAD_ERR_FORM_SIZE  => 'Arquivo excede o limite do formulário.',
            UPLOAD_ERR_PARTIAL    => 'Upload incompleto.',
            UPLOAD_ERR_NO_FILE    => 'Nenhum arquivo enviado.',
            UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária ausente.',
            UPLOAD_ERR_CANT_WRITE => 'Falha ao gravar arquivo.',
            UPLOAD_ERR_EXTENSION  => 'Upload bloqueado por extensão do PHP.',
        ];
        return ['ok' => false, 'error' => $messages[$error] ?? 'Falha no upload.'];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    $size = (int) ($file['size'] ?? 0);
    $original = (string) ($file['name'] ?? 'file');

    if ($tmp === '' || !is_uploaded_file($tmp) || !is_file($tmp)) {
        return ['ok' => false, 'error' => 'Arquivo temporário inválido.'];
    }

    if ($size <= 0 || $size > $maxBytes) {
        return ['ok' => false, 'error' => 'Tamanho de arquivo não permitido.'];
    }

    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if ($extensions && !in_array($ext, $extensions, true)) {
        return ['ok' => false, 'error' => 'Extensão de arquivo não permitida.'];
    }

    // Extensões perigosas sempre bloqueadas
    $dangerous = ['php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'cgi', 'exe', 'bat', 'cmd', 'sh', 'js', 'html', 'htm', 'shtml'];
    if (in_array($ext, $dangerous, true)) {
        return ['ok' => false, 'error' => 'Tipo de arquivo não permitido.'];
    }

    $detectedMime = upload_detect_mime($tmp);
    if ($ext !== '' && isset($mimeMap[$ext])) {
        $allowedMimes = $mimeMap[$ext];
        if ($detectedMime !== null && !in_array($detectedMime, $allowedMimes, true)) {
            // Alguns ambientes retornam application/octet-stream — aceita só se assinatura ok
            if (!upload_signature_matches($tmp, $ext)) {
                return ['ok' => false, 'error' => 'Conteúdo do arquivo não confere com a extensão.'];
            }
        }
    } elseif ($ext !== '' && !upload_signature_matches($tmp, $ext)) {
        return ['ok' => false, 'error' => 'Assinatura do arquivo inválida.'];
    }

    return [
        'ok' => true,
        'ext' => $ext,
        'mime' => $detectedMime,
        'tmp' => $tmp,
        'original' => $original,
        'size' => $size,
    ];
}

function upload_default_mime_map(): array
{
    return [
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'webp' => ['image/webp'],
        'gif'  => ['image/gif'],
    ];
}

function upload_detect_mime(string $path): ?string
{
    if (function_exists('finfo_open')) {
        $f = finfo_open(FILEINFO_MIME_TYPE);
        if ($f) {
            $mime = finfo_file($f, $path);
            finfo_close($f);
            if (is_string($mime) && $mime !== '') {
                return strtolower($mime);
            }
        }
    }
    return null;
}

function upload_signature_matches(string $path, string $ext): bool
{
    $fh = fopen($path, 'rb');
    if (!$fh) {
        return false;
    }
    $sig = fread($fh, 16) ?: '';
    fclose($fh);

    return match ($ext) {
        'pdf' => str_starts_with($sig, '%PDF'),
        'png' => str_starts_with($sig, "\x89PNG"),
        'jpg', 'jpeg' => str_starts_with($sig, "\xFF\xD8\xFF"),
        'gif' => str_starts_with($sig, 'GIF8'),
        'webp' => strlen($sig) >= 12 && str_starts_with($sig, 'RIFF') && substr($sig, 8, 4) === 'WEBP',
        'docx' => str_starts_with($sig, 'PK'),
        'doc' => strlen($sig) >= 4 && $sig[0] === "\xD0" && $sig[1] === "\xCF",
        default => true,
    };
}

/**
 * Armazena em diretório privado com nome aleatório.
 *
 * @return array{ok:bool,error?:string,path?:string,stored_name?:string}
 */
function upload_store_private(string $tmpPath, string $ext, string $subdir = 'temp'): array
{
    $root = realpath(dirname(__DIR__) . '/storage');
    if ($root === false) {
        $root = dirname(__DIR__) . '/storage';
        if (!is_dir($root) && !@mkdir($root, 0750, true)) {
            return ['ok' => false, 'error' => 'Storage indisponível.'];
        }
        $root = realpath($root) ?: $root;
    }

    $subdir = preg_replace('/[^a-z0-9_\\-\\/]/i', '', $subdir) ?: 'temp';
    $destDir = $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $subdir);
    if (!is_dir($destDir) && !@mkdir($destDir, 0750, true)) {
        return ['ok' => false, 'error' => 'Não foi possível criar diretório de destino.'];
    }

    $stored = bin2hex(random_bytes(16)) . ($ext !== '' ? '.' . $ext : '');
    $dest = $destDir . DIRECTORY_SEPARATOR . $stored;

    if (!@move_uploaded_file($tmpPath, $dest) && !@rename($tmpPath, $dest) && !@copy($tmpPath, $dest)) {
        return ['ok' => false, 'error' => 'Falha ao armazenar arquivo.'];
    }
    @chmod($dest, 0640);

    return ['ok' => true, 'path' => $dest, 'stored_name' => $stored];
}

/**
 * Garante que $path está dentro de $root (anti path traversal).
 */
function upload_assert_safe_path(string $root, string $path): bool
{
    $rootReal = realpath($root);
    $pathReal = realpath($path);
    if ($rootReal === false || $pathReal === false) {
        return false;
    }
    $rootReal = rtrim($rootReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    return str_starts_with($pathReal, $rootReal);
}
