<?php

declare(strict_types=1);

/**
 * Registro de assets e conteúdo por handler de ferramenta.
 * page.php usa este mapa em vez de dezenas de if/elseif.
 *
 * Chaves:
 *   content  — arquivo em /content (obrigatório se a ferramenta tem UI)
 *   css      — lista de arquivos em /assets/css
 *   js       — lista de arquivos em /assets/js
 *   shared_css — folhas compartilhadas (ex.: pdf-tools.css)
 */
return [
    'word-counter' => [
        'content' => 'word-counter.php',
        'css' => [],
        'js' => ['word-counter.js'],
    ],
    'image-resizer' => [
        'content' => 'image-resizer.php',
        'css' => ['image-resizer.css'],
        'js' => ['image-resizer.js'],
    ],
    'image-compressor' => [
        'content' => 'image-compressor.php',
        'css' => ['image-compressor.css'],
        'js' => ['image-compressor.js'],
    ],
    'image-converter' => [
        'content' => 'image-converter.php',
        'css' => ['image-converter.css'],
        'js' => ['image-converter.js'],
    ],
    'image-cropper' => [
        'content' => 'image-cropper.php',
        'css' => ['image-cropper.css'],
        'js' => ['image-cropper.js'],
    ],
    // pdf-merge é alias legado de unir-pdf (mesma UI e endpoint lógico)
    'pdf-merge' => [
        'content' => 'unir-pdf.php',
        'css' => [],
        'shared_css' => ['pdf-tools.css'],
        'js' => ['unir-pdf.js'],
    ],
    'unir-pdf' => [
        'content' => 'unir-pdf.php',
        'css' => [],
        'shared_css' => ['pdf-tools.css'],
        'js' => ['unir-pdf.js'],
    ],
    'compactar-pdf' => [
        'content' => 'compactar-pdf.php',
        'css' => [],
        'shared_css' => ['pdf-tools.css'],
        'js' => ['compactar-pdf.js'],
    ],
    'separar-pdf' => [
        'content' => 'separar-pdf.php',
        'css' => [],
        'shared_css' => ['pdf-tools.css'],
        'js' => ['separar-pdf.js'],
    ],
    'proteger-pdf' => [
        'content' => 'proteger-pdf.php',
        'css' => [],
        'shared_css' => ['pdf-tools.css'],
        'js' => ['proteger-pdf.js'],
    ],
    'desbloquear-pdf' => [
        'content' => 'desbloquear-pdf.php',
        'css' => [],
        'shared_css' => ['pdf-tools.css'],
        'js' => ['desbloquear-pdf.js'],
    ],
    'pdf-to-word' => [
        'content' => 'pdf-to-word.php',
        'css' => ['pdf-to-word.css'],
        'js' => ['pdf-to-word.js'],
    ],
    'word-to-pdf' => [
        'content' => 'word-to-pdf.php',
        'css' => ['word-to-pdf.css'],
        'js' => ['word-to-pdf.js'],
    ],
    'download-video-youtube' => [
        'content' => 'download-video-youtube.php',
        'css' => ['download-video-youtube.css'],
        'js' => ['download-video-youtube.js'],
    ],
    'download-audio-youtube' => [
        'content' => 'download-audio-youtube.php',
        'css' => ['download-audio-youtube.css'],
        'js' => ['download-audio-youtube.js'],
    ],
    'download-video-instagram' => [
        'content' => 'download-video-instagram.php',
        'css' => ['download-video-instagram.css'],
        'js' => ['download-video-instagram.js'],
    ],
    'download-video-tiktok' => [
        'content' => 'download-video-tiktok.php',
        'css' => ['download-video-tiktok.css'],
        'js' => ['download-video-tiktok.js'],
    ],
    'formatar-texto' => [
        'content' => 'formatar-texto.php',
        'css' => ['formatar-texto.css'],
        'js' => ['formatar-texto.js'],
    ],
    'remover-espacos' => [
        'content' => 'remover-espacos.php',
        'css' => ['remover-espacos.css'],
        'js' => ['remover-espacos.js'],
    ],
    'gerador-lorem' => [
        'content' => 'gerador-lorem.php',
        'css' => ['gerador-lorem.css'],
        'js' => ['gerador-lorem.js'],
    ],
];
