
<?php
    include_once __DIR__ . '/database.php';
    include_once __DIR__ . '/function.php';

function user_tool_usage($user_id, $tools_id, $st, $process, $file_size) {

    $tool_data = get_tool_data($tools_id);
    $tool_name = $tool_data['name'];
    $tool_slug = $tool_data['slug'];
    $status = $st;
    $processing_time_ms = $process;
    $file_size_bytes = $file_size;
    $created_at = '0000-00-00 00:00:00'; //new Date()->date_format('YYYY-D-M H:i:s');

    $sql = $pdo->prepare('INSERT INTO user_tool_usage (user_id, tool_id, tool_slug, status, processing_time_ms, file_size_bytes, created_at) VALUES (:user_id, :tool_id, :tool_slug, :status, :processing_time_ms, :file_size_bytes, :created_at)');
    $sql->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $sql->bindParam(':tool_id', $tools_id, PDO::PARAM_INT);
    $sql->bindParam(':tool_slug', $tool_slug, PDO::PARAM_STR);
    $sql->bindParam(':status', $status, PDO::PARAM_STR);
    $sql->bindParam(':processing_time_ms', $processing_time_ms, PDO::PARAM_INT);
    $sql->bindParam(':file_size_bytes', $file_size_bytes, PDO::PARAM_INT);
    $sql->bindParam(':created_at', $created_at, PDO::PARAM_STR);
    $result = $sql->execute();

    echo "User ID: $user_id, Tool ID: $tools_id, Tool Slug: $tool_slug, Status: $status, Processing Time (ms): $processing_time_ms, File Size (bytes): $file_size_bytes, Created At: $created_at";
    if ($result === true) {
        return true;
    }

    return false;

}
