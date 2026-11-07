<?php

require_once __DIR__ . '/database.php';

function get_tool_data(PDO $pdo, int $id): array|false
{
    $sql = $pdo->prepare(
        'SELECT id, name, slug
         FROM tools
         WHERE id = :id
         LIMIT 1'
    );

    $sql->execute([':id' => $id]);

    return $sql->fetch(PDO::FETCH_ASSOC);
}


function user_tool_usage(
    PDO $pdo,
    int $user_id,
    int $tools_id,
    string $status,
    int $processing_time,
    int $file_size
): bool {

    $tool = get_tool_data($pdo, $tools_id);

    if (!$tool) {
        return false;
    }

    $sql = $pdo->prepare(
        'INSERT INTO user_tool_usage (
            user_id,
            tool_id,
            tool_slug,
            status,
            processing_time_ms,
            file_size_bytes,
            created_at
        ) VALUES (
            :user_id,
            :tool_id,
            :tool_slug,
            :status,
            :processing_time_ms,
            :file_size_bytes,
            CURRENT_TIMESTAMP
        )'
    );

    return $sql->execute([
        ':user_id' => $user_id,
        ':tool_id' => $tools_id,
        ':tool_slug' => $tool['slug'],
        ':status' => $status,
        ':processing_time_ms' => $processing_time,
        ':file_size_bytes' => $file_size
    ]);
}