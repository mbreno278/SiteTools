<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Process\TempWorkspace;

/**
 * Fachada de compatibilidade para operações de imagem.
 *
 * Mantém a implementação GD existente em ImageProcessingService,
 * permitindo migrar endpoints para ImageService sem duplicar regras.
 */
final class ImageService
{
    public function __construct(
        private readonly ImageProcessingService $processor = new ImageProcessingService(),
    ) {
    }

    public function resize(array $file, int $width, int $height, bool $keepRatio = true, string $format = 'jpeg', int $quality = 85): array
    {
        return $this->processor->resize($file, $width, $height, $keepRatio, $format, $quality);
    }

    public function compress(array $file, int $quality = 75, string $format = 'jpeg'): array
    {
        return $this->processor->compress($file, $quality, $format);
    }

    public function convert(array $file, string $format = 'png', int $quality = 90): array
    {
        return $this->processor->convert($file, $format, $quality);
    }

    public function crop(array $file, int $x, int $y, int $width, int $height, string $format = 'jpeg', int $quality = 90): array
    {
        return $this->processor->crop($file, $x, $y, $width, $height, $format, $quality);
    }
}
