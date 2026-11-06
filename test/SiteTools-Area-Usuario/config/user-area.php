<?php
declare(strict_types=1);

/**
 * Helpers da área do usuário.
 * Depende de config/auth.php e config/csrf.php já existentes.
 */
require_once __DIR__ . '/auth.php';

function user_area_require_login(): array
{
    $user = auth_user();

    if (!$user || empty($user['id'])) {
        $login = '../tools/login.php';
        $return = $_SERVER['REQUEST_URI'] ?? '../account/index.php';
        header('Location: ' . $login . '?redirect=' . rawurlencode($return));
        exit;
    }

    return $user;
}

function user_area_e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function user_area_base_path(): string
{
    // account/xxx.php => raiz do SiteTools, inclusive quando instalado em subpasta.
    return '../';
}

function user_area_log_tool_usage(
    PDO $pdo,
    ?int $toolId,
    string $toolSlug,
    string $status = 'completed',
    ?int $processingMs = null,
    ?int $fileSizeBytes = null
): void {
    $userId = !empty($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

    $stmt = $pdo->prepare(
        'INSERT INTO user_tool_usage
         (user_id, tool_id, tool_slug, status, processing_time_ms, file_size_bytes)
         VALUES (:uid, :tool_id, :slug, :status, :duration, :size)'
    );

    $stmt->execute([
        'uid' => $userId,
        'tool_id' => $toolId,
        'slug' => $toolSlug,
        'status' => in_array($status, ['started', 'completed', 'failed'], true) ? $status : 'failed',
        'duration' => $processingMs,
        'size' => $fileSizeBytes,
    ]);
}
