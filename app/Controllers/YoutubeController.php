<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\YoutubeService;

/**
 * Coordena consulta e download de vídeos do YouTube.
 */
final class YoutubeController
{
    public function __construct(
        private readonly YoutubeService $youtube = new YoutubeService(),
    ) {
    }

    public function getInfo(string $url): array
    {
        return $this->youtube->getVideoInfo($url);
    }

    public function download(string $url, string $formatId): array
    {
        return $this->youtube->downloadVideo($url, $formatId);
    }
}
