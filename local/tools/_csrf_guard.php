<?php

declare(strict_types=1);

/**
 * Inclua no topo de todo endpoint tools/*.php que aceita POST/PUT/PATCH/DELETE.
 * Ex.: require __DIR__ . '/_csrf_guard.php';
 *
 * CSRF é obrigatório — não há bypass silencioso.
 */

require_once __DIR__ . '/../config/csrf.php';

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
    csrf_verify();
}
