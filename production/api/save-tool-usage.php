<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

// Somente POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.'
    ]);

    exit;
}

// Proteção CSRF
csrf_verify();

// Recupera usuário autenticado
$user = auth_user();

if (!$user) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Authentication required.'
    ]);

    exit;
}

// Recupera dados JSON
$data = csrf_json_input();

if (!is_array($data)) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid request.'
    ]);

    exit;
}

// Valida os dados
$tool_id = filter_var(
    $data['tool_id'] ?? null,
    FILTER_VALIDATE_INT
);

$processing_time = filter_var(
    $data['processing_time_ms'] ?? null,
    FILTER_VALIDATE_INT
);

$file_size = filter_var(
    $data['file_size_bytes'] ?? null,
    FILTER_VALIDATE_INT
);

$status = $data['status'] ?? '';

if (
    !$tool_id ||
    $processing_time === false ||
    $file_size === false ||
    $processing_time < 0 ||
    $processing_time > 3600000 ||
    $file_size < 0 ||
    $file_size > 10485760 ||
    !in_array($status, ['completed', 'failed'], true)
) {
    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid usage data.'
    ]);

    exit;
}

try {

    $result = user_tool_usage(
        $pdo,
        (int) $user['id'],
        $tool_id,
        $status,
        $processing_time,
        $file_size
    );

    if (!$result) {
        throw new RuntimeException(
            'Unable to save tool usage.'
        );
    }

    echo json_encode([
        'success' => true,
        'message' => 'Usage recorded successfully.'
    ]);

} catch (Throwable $e) {

    error_log('[tool-usage] ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to record usage.'
    ]);
}