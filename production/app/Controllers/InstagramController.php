<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\InstagramService;

/**
 * Coordena consulta e download de mídias do Instagram.
 */
final class InstagramController
{
    public function __construct(
        private readonly InstagramService $instagram = new InstagramService(),
    ) {
    }

    public function getInfo(string $url): array
    {
        return $this->instagram->getVideoInfo($url);
    }

    public function download(string $url, string $formatId = 'best'): array
    {
        return $this->instagram->downloadVideo($url, $formatId);
    }
}
