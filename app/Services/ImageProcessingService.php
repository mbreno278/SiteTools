<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AppException;
use App\Exceptions\ValidationException;
use App\Services\Process\TempWorkspace;

/**
 * Processamento de imagens com GD (sem dependência externa).
 * Cobre resize, compress e convert; crop básico.
 */
final class ImageProcessingService
{
    private const MAX_BYTES = 15 * 1024 * 1024;
    private const MAX_PIXELS = 16_000_000;
    private const MAX_OUTPUT_DIMENSION = 10_000;
    private const MAX_OUTPUT_PIXELS = 16_000_000;

    /** @return array{im:\GdImage,w:int,h:int,type:int} */
    public function loadFromUpload(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new ValidationException('Falha no upload da imagem.');
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        if ($tmp === '' || !is_uploaded_file($tmp) || $size <= 0 || $size > self::MAX_BYTES) {
            throw new ValidationException('Imagem inválida ou muito grande.');
        }

        $info = @getimagesize($tmp);
        if ($info === false) {
            throw new ValidationException('Arquivo não é uma imagem válida.');
        }
        [$w, $h, $type] = $info;
        if ($w < 1 || $h < 1 || ($w * $h) > self::MAX_PIXELS) {
            throw new ValidationException('Dimensões de imagem não suportadas.');
        }

        $im = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($tmp),
            IMAGETYPE_PNG => @imagecreatefrompng($tmp),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false,
            IMAGETYPE_GIF => @imagecreatefromgif($tmp),
            default => false,
        };

        if ($im === false) {
            throw new ValidationException('Não foi possível ler a imagem.');
        }

        return ['im' => $im, 'w' => $w, 'h' => $h, 'type' => $type];
    }

    /**
     * @return array{path:string,mime:string,bytes:int,workspace:TempWorkspace}
     */
    public function resize(array $file, int $targetW, int $targetH, bool $keepRatio = true, string $format = 'jpeg', int $quality = 85): array
    {
        $loaded = $this->loadFromUpload($file);
        $src = $loaded['im'];
        $sw = $loaded['w'];
        $sh = $loaded['h'];

        if ($targetW < 1 && $targetH < 1) {
            imagedestroy($src);
            throw new ValidationException('Informe largura ou altura.');
        }

        if ($keepRatio) {
            if ($targetW > 0 && $targetH > 0) {
                $ratio = min($targetW / $sw, $targetH / $sh);
                $tw = max(1, (int) round($sw * $ratio));
                $th = max(1, (int) round($sh * $ratio));
            } elseif ($targetW > 0) {
                $tw = $targetW;
                $th = max(1, (int) round($sh * ($targetW / $sw)));
            } else {
                $th = $targetH;
                $tw = max(1, (int) round($sw * ($targetH / $sh)));
            }
        } else {
            $tw = max(1, $targetW ?: $sw);
            $th = max(1, $targetH ?: $sh);
        }

        if (
            $tw > self::MAX_OUTPUT_DIMENSION
            || $th > self::MAX_OUTPUT_DIMENSION
            || ($tw * $th) > self::MAX_OUTPUT_PIXELS
        ) {
            imagedestroy($src);
            throw new ValidationException('As dimensões solicitadas excedem o limite de saída permitido.');
        }

        $dst = imagecreatetruecolor($tw, $th);
        if ($dst === false) {
            imagedestroy($src);
            throw new AppException('Falha ao redimensionar.', 500);
        }

        $this->preserveAlpha($dst, $format);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $sw, $sh);
        imagedestroy($src);

        return $this->encodeToTemp($dst, $format, max(10, min(95, $quality)));
    }

    /**
     * @return array{path:string,mime:string,bytes:int,workspace:TempWorkspace}
     */
    public function compress(array $file, int $quality = 75, string $format = 'jpeg'): array
    {
        $loaded = $this->loadFromUpload($file);
        $src = $loaded['im'];
        return $this->encodeToTemp($src, $format, max(10, min(95, $quality)));
    }

    /**
     * @return array{path:string,mime:string,bytes:int,workspace:TempWorkspace}
     */
    public function convert(array $file, string $format = 'png', int $quality = 90): array
    {
        $loaded = $this->loadFromUpload($file);
        return $this->encodeToTemp($loaded['im'], $format, max(10, min(95, $quality)));
    }

    /**
     * Crop por coordenadas.
     * @return array{path:string,mime:string,bytes:int,workspace:TempWorkspace}
     */
    public function crop(array $file, int $x, int $y, int $w, int $h, string $format = 'jpeg', int $quality = 90): array
    {
        $loaded = $this->loadFromUpload($file);
        $src = $loaded['im'];
        $sw = $loaded['w'];
        $sh = $loaded['h'];

        $x = max(0, min($x, $sw - 1));
        $y = max(0, min($y, $sh - 1));
        $w = max(1, min($w, $sw - $x));
        $h = max(1, min($h, $sh - $y));

        $dst = imagecreatetruecolor($w, $h);
        if ($dst === false) {
            imagedestroy($src);
            throw new AppException('Falha ao recortar.', 500);
        }
        $this->preserveAlpha($dst, $format);
        imagecopy($dst, $src, 0, 0, $x, $y, $w, $h);
        imagedestroy($src);

        return $this->encodeToTemp($dst, $format, $quality);
    }

    /** @param \GdImage $im */
    private function encodeToTemp($im, string $format, int $quality): array
    {
        $format = strtolower($format);
        $quality = max(10, min(95, $quality));
        if (!in_array($format, ['jpeg', 'jpg', 'png', 'webp'], true)) {
            $format = 'jpeg';
        }
        if ($format === 'jpg') {
            $format = 'jpeg';
        }

        $workspace = new TempWorkspace();
        $path = $workspace->randomFile($format === 'jpeg' ? 'jpg' : $format);

        $ok = match ($format) {
            'png' => imagepng($im, $path, (int) round((100 - $quality) / 11.1)),
            'webp' => function_exists('imagewebp') ? imagewebp($im, $path, $quality) : false,
            default => imagejpeg($im, $path, $quality),
        };
        imagedestroy($im);

        if (!$ok || !is_file($path)) {
            $workspace->cleanup();
            throw new AppException('Falha ao gerar imagem de saída.', 500);
        }

        $mime = match ($format) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        return [
            'path' => $path,
            'mime' => $mime,
            'bytes' => (int) filesize($path),
            'workspace' => $workspace,
        ];
    }

    /** @param \GdImage $im */
    private function preserveAlpha($im, string $format): void
    {
        if (in_array(strtolower($format), ['png', 'webp'], true)) {
            imagealphablending($im, false);
            imagesavealpha($im, true);
            $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
            imagefilledrectangle($im, 0, 0, imagesx($im), imagesy($im), $transparent);
        } else {
            $white = imagecolorallocate($im, 255, 255, 255);
            imagefilledrectangle($im, 0, 0, imagesx($im), imagesy($im), $white);
        }
    }
}
