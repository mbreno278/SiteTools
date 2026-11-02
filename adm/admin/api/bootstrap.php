<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/permissions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    verify_csrf();
}
