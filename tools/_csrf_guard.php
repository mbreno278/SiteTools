<?php

declare(strict_types=1);

/**
 * Inclua no topo de todo endpoint tools/*.php que aceita POST.
 * Ex.: require __DIR__ . '/_csrf_guard.php';
 */

require_once __DIR__ . '/../config/csrf.php';

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify();
}
