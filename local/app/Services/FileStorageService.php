<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;

/**
 * Fachada sobre config/upload-security.php.
 */
final class FileStorageService
{
    /**
     * @param array $file $_FILES['campo']
     * @param array $opts opções de upload_validate
     * @return array{ext:string,mime:?string,tmp:string,original:string,size:int}
     */
    public function validateUpload(array $file, array $opts = []): array
    {
        if (!function_exists('upload_validate')) {
            $path = dirname(__DIR__, 2) . '/config/upload-security.php';
            if (is_file($path)) {
                require_once $path;
            }
        }

        if (!function_exists('upload_validate')) {
            throw new ValidationException('Módulo de upload não disponível.');
        }

        $result = upload_validate($file, $opts);
        if (empty($result['ok'])) {
            throw new ValidationException((string) ($result['error'] ?? 'Arquivo inválido.'));
        }

        return [
            'ext' => (string) $result['ext'],
            'mime' => $result['mime'] ?? null,
            'tmp' => (string) $result['tmp'],
            'original' => (string) $result['original'],
            'size' => (int) $result['size'],
        ];
    }

    /**
     * @return array{path:string,stored_name:string}
     */
    public function storePrivate(string $tmpPath, string $ext, string $subdir = 'temp'): array
    {
        if (!function_exists('upload_store_private')) {
            require_once dirname(__DIR__, 2) . '/config/upload-security.php';
        }

        $result = upload_store_private($tmpPath, $ext, $subdir);
        if (empty($result['ok'])) {
            throw new ValidationException((string) ($result['error'] ?? 'Falha ao armazenar arquivo.'));
        }

        return [
            'path' => (string) $result['path'],
            'stored_name' => (string) $result['stored_name'],
        ];
    }

    public function assertSafePath(string $root, string $path): bool
    {
        if (!function_exists('upload_assert_safe_path')) {
            require_once dirname(__DIR__, 2) . '/config/upload-security.php';
        }
        return upload_assert_safe_path($root, $path);
    }
}
