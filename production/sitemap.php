<?php

declare(strict_types=1);

/**
 * Sitemap XML dinâmico — SiteTools
 * Acesse via /sitemap.xml (rewrite) ou /sitemap.php
 */

require_once __DIR__ . '/config/app.php';

header('Content-Type: application/xml; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=3600');

$urls = [];

// Home
$urls[] = [
    'loc' => absolute_url('/'),
    'changefreq' => 'daily',
    'priority' => '1.0',
];

// Catálogo
$urls[] = [
    'loc' => absolute_url('/tools.php'),
    'changefreq' => 'daily',
    'priority' => '0.9',
];

// Ferramentas ativas do banco
try {
    require_once __DIR__ . '/config/database.php';
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->query(
            "SELECT slug, updated_at, created_at
             FROM tools
             WHERE is_active = 1
               AND (status = 'active' OR status IS NULL OR status = '')
               AND slug IS NOT NULL
               AND slug <> ''
             ORDER BY sort_order ASC, name ASC"
        );
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $slug = (string) ($row['slug'] ?? '');
            if ($slug === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
                continue;
            }
            $lastmod = $row['updated_at'] ?? $row['created_at'] ?? null;
            $entry = [
                'loc' => absolute_url('/' . $slug),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
            if ($lastmod) {
                $ts = strtotime((string) $lastmod);
                if ($ts) {
                    $entry['lastmod'] = date('Y-m-d', $ts);
                }
            }
            $urls[] = $entry;
        }
    }
} catch (Throwable $e) {
    error_log('[sitemap] ' . $e->getMessage());
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($urls as $u) {
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
    if (!empty($u['lastmod'])) {
        echo '    <lastmod>' . htmlspecialchars($u['lastmod'], ENT_XML1, 'UTF-8') . "</lastmod>\n";
    }
    if (!empty($u['changefreq'])) {
        echo '    <changefreq>' . htmlspecialchars($u['changefreq'], ENT_XML1, 'UTF-8') . "</changefreq>\n";
    }
    if (!empty($u['priority'])) {
        echo '    <priority>' . htmlspecialchars($u['priority'], ENT_XML1, 'UTF-8') . "</priority>\n";
    }
    echo "  </url>\n";
}

echo '</urlset>';
