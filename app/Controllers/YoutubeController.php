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

    public function getAudioInfo(string $url): array
    {
        return $this->youtube->getAudioInfo($url);
    }

    public function downloadAudio(string $url, string $formatId, string $output): array
    {
        return $this->youtube->downloadAudio($url, $formatId, $output);
    }

    public function download(string $url, string $formatId): array
    {
        return $this->youtube->downloadVideo($url, $formatId);
    }
}
