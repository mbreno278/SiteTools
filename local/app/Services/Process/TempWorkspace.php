<?php

declare(strict_types=1);

namespace App\Services\Process;

/**
 * Diretório temporário isolado com nomes aleatórios e limpeza garantida.
 */
final class TempWorkspace
{
    private string $path;
    private bool $alive = true;

    public function __construct(?string $baseDir = null)
    {
        $base = $baseDir ?: (dirname(__DIR__, 3) . '/storage/temp');
        if (!is_dir($base) && !@mkdir($base, 0750, true) && !is_dir($base)) {
            $base = sys_get_temp_dir();
        }

        $name = 'st_' . bin2hex(random_bytes(8));
        $this->path = rtrim($base, '/\\') . DIRECTORY_SEPARATOR . $name;
        if (!@mkdir($this->path, 0750, true) && !is_dir($this->path)) {
            throw new \RuntimeException('Não foi possível criar workspace temporário.');
        }
    }

    public function path(): string
    {
        return $this->path;
    }

    public function join(string $filename): string
    {
        $filename = basename(str_replace(['\\', "\0"], ['/', ''], $filename));
        return $this->path . DIRECTORY_SEPARATOR . $filename;
    }

    public function randomFile(string $extension = ''): string
    {
        $ext = $extension !== '' ? '.' . ltrim($extension, '.') : '';
        return $this->join(bin2hex(random_bytes(12)) . $ext);
    }

    public function cleanup(): void
    {
        if (!$this->alive) {
            return;
        }
        $this->alive = false;
        self::removeTree($this->path);
    }

    public function __destruct()
    {
        $this->cleanup();
    }

    public static function removeTree(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        try {
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $item) {
                $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
            }
        } catch (\Throwable) {
        }
        @rmdir($dir);
    }
}
