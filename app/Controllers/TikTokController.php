<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\TikTokService;

/**
 * Coordena consulta e download de vídeos do TikTok.
 */
final class TikTokController
{
    public function __construct(
        private readonly TikTokService $tiktok = new TikTokService(),
    ) {
    }

    public function getInfo(string $url): array
    {
        return $this->tiktok->getVideoInfo($url);
    }

    public function download(string $url, string $formatId = 'h264'): array
    {
        return $this->tiktok->downloadVideo($url, $formatId);
    }
}
