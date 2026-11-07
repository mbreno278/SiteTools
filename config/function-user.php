
<?php
    include_once __DIR__ . '/database.php';
    include_once __DIR__ . '/function.php';

function user_tool_usage(
    PDO $pdo,
    int $user_id,
    int $tools_id,
    string $status,
    int $processing_time,
    int $file_size
): bool {

    $tool_data = get_tool_data($pdo, $tools_id);

    if (!$tool_data) {
        return false;
    }

    $sql = $pdo->prepare(
        'INSERT INTO user_tool_usage (
            user_id,
            tool_id,
            tool_name,
            tool_slug,
            status,
            processing_time_ms,
            file_size_bytes,
            created_at
        ) VALUES (
            :user_id,
            :tool_id,
            :tool_name,
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
        ':tool_name' => $tool_data['name'],
        ':tool_slug' => $tool_data['slug'],
        ':status' => $status,
        ':processing_time_ms' => $processing_time,
        ':file_size_bytes' => $file_size
    ]);
}