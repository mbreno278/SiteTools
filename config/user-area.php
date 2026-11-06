<?php
declare(strict_types=1);

/**
 * Helpers da área do usuário.
 * Depende de config/auth.php e config/csrf.php.
 */
require_once __DIR__ . '/auth.php';

function user_area_require_login(): array
{
    $user = auth_user();

    if (!$user || empty($user['id'])) {
        // Página de login do site (modal/página pública), não o endpoint JSON
        $return = $_SERVER['REQUEST_URI'] ?? '/account/index.php';
        header('Location: ../index.php?login=1&redirect=' . rawurlencode($return));
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
    return '../';
}

/**
 * Registra uso autenticado em user_tool_usage (sem conteúdo de arquivo).
 */
function user_area_log_tool_usage(
    PDO $pdo,
    ?int $toolId,
    string $toolSlug,
    string $status = 'completed',
    ?int $processingMs = null,
    ?int $fileSizeBytes = null
): void {
    $userId = !empty($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    if ($userId === null || $userId <= 0) {
        return;
    }

    $status = in_array($status, ['started', 'completed', 'failed'], true) ? $status : 'failed';

    $stmt = $pdo->prepare(
        'INSERT INTO user_tool_usage
         (user_id, tool_id, tool_slug, status, processing_time_ms, file_size_bytes)
         VALUES (:uid, :tool_id, :slug, :status, :duration, :size)'
    );
    $stmt->execute([
        'uid' => $userId,
        'tool_id' => $toolId,
        'slug' => $toolSlug,
        'status' => $status,
        'duration' => $processingMs,
        'size' => $fileSizeBytes,
    ]);
}

/**
 * Grava metadados de arquivo processado (não o conteúdo em blob).
 * O arquivo físico fica em storage/user-files/{userId}/...
 *
 * @return int ID em user_files
 */
function user_area_save_file(
    PDO $pdo,
    int $userId,
    ?int $toolId,
    string $originalName,
    string $storedName,
    string $storagePath,
    ?string $mimeType,
    int $sizeBytes,
    ?DateTimeInterface $expiresAt = null
): int {
    $expires = $expiresAt ? $expiresAt->format('Y-m-d H:i:s') : date('Y-m-d H:i:s', time() + 7 * 86400);

    $stmt = $pdo->prepare(
        'INSERT INTO user_files
         (user_id, tool_id, original_name, stored_name, storage_path, mime_type, size_bytes, status, expires_at)
         VALUES (:uid, :tid, :orig, :stored, :path, :mime, :size, \'available\', :exp)'
    );
    $stmt->execute([
        'uid' => $userId,
        'tid' => $toolId,
        'orig' => mb_substr($originalName, 0, 255),
        'stored' => mb_substr($storedName, 0, 255),
        'path' => $storagePath,
        'mime' => $mimeType,
        'size' => $sizeBytes,
        'exp' => $expires,
    ]);

    return (int) $pdo->lastInsertId();
}

function user_area_format_bytes(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1048576) {
        return number_format($bytes / 1024, 1, ',', '.') . ' KB';
    }
    return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
}

function user_area_status_label(string $status): string
{
    return match ($status) {
        'started' => 'Iniciado',
        'completed' => 'Concluído',
        'failed' => 'Falhou',
        'active' => 'Ativo',
        'expired' => 'Expirado',
        'cancelled' => 'Cancelado',
        'suspended' => 'Suspenso',
        'available' => 'Disponível',
        'deleted' => 'Excluído',
        'pending' => 'Pendente',
        'paid' => 'Pago',
        'refunded' => 'Estornado',
        default => $status,
    };
}
