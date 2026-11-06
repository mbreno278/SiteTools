<?php

declare(strict_types=1);

/**
 * Uso no topo de tools/xxx.php (após CSRF):
 *
 *   require __DIR__ . '/_tool_access_guard.php';
 *   tool_access_guard_for('download-video-youtube');
 *
 * Ou passe o slug manualmente.
 */

require_once __DIR__ . '/../config/tool-access.php';
require_once __DIR__ . '/../config/database.php';

function tool_access_guard_for(string $slug): void
{
    global $pdo;

    if (!isset($pdo) || !($pdo instanceof PDO)) {
        if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
            $pdo = $GLOBALS['pdo'];
        } else {
            // tenta carregar
            try {
                include __DIR__ . '/../config/database.php';
            } catch (Throwable) {
                return; // não bloqueia se DB offline (opcional)
            }
        }
    }

    if (!isset($pdo) || !($pdo instanceof PDO)) {
        return;
    }

    tool_access_require($pdo, $slug);
}
