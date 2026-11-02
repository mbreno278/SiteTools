<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function admin_logged_in(): bool
{
    return isset($_SESSION['admin']['id']);
}

function require_admin(): void
{
    if (!admin_logged_in()) {
        redirect(admin_url('login.php'));
    }
}

function current_admin(): array
{
    return $_SESSION['admin'] ?? [];
}
