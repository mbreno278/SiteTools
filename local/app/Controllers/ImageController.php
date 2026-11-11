<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ImageService;
use InvalidArgumentException;

/**
 * Orquestra as operações de imagem sem acoplar a regra de processamento ao HTTP.
 */
final class ImageController
{
    public function __construct(
        private readonly ImageService $images = new ImageService(),
    ) {
    }

    /**
     * @param array $file Entrada equivalente a $_FILES['image'].
     * @param array $options Parâmetros validados pelo endpoint.
     * @return array{path:string,mime:string,bytes:int,workspace:\App\Services\Process\TempWorkspace}
     */
    public function process(string $action, array $file, array $options = []): array
    {
        return match (strtolower($action)) {
            'resize' => $this->images->resize(
                $file,
                (int) ($options['width'] ?? 0),
                (int) ($options['height'] ?? 0),
                (bool) ($options['keep_ratio'] ?? true),
                (string) ($options['format'] ?? 'jpeg'),
                (int) ($options['quality'] ?? 85),
            ),
            'compress' => $this->images->compress(
                $file,
                (int) ($options['quality'] ?? 75),
                (string) ($options['format'] ?? 'jpeg'),
            ),
            'convert' => $this->images->convert(
                $file,
                (string) ($options['format'] ?? 'png'),
                (int) ($options['quality'] ?? 90),
            ),
            'crop' => $this->images->crop(
                $file,
                (int) ($options['x'] ?? 0),
                (int) ($options['y'] ?? 0),
                (int) ($options['width'] ?? 0),
                (int) ($options['height'] ?? 0),
                (string) ($options['format'] ?? 'jpeg'),
                (int) ($options['quality'] ?? 90),
            ),
            default => throw new InvalidArgumentException('Ação de imagem não suportada.'),
        };
    }
}
