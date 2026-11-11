<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

try {
    require_once dirname(__DIR__) . '/includes/permissions.php';
} catch (Throwable $e) {
    http_response_code(500);
    error_log('[admin tools api bootstrap] ' . $e->getMessage());
    echo json_encode(['error' => 'Não foi possível iniciar a API.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_permission('tools.edit');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    verify_csrf();
} catch (Throwable $e) {
    http_response_code(419);
    echo json_encode(['error' => 'Token CSRF inválido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
$action = (string) ($_POST['action'] ?? 'update');

if ($id <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'ID inválido'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = db();

    if ($action === 'toggle_active') {
        $pdo->prepare('UPDATE tools SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?')->execute([$id]);
        record_admin_activity('tool_toggle', 'Alternou is_active #' . $id);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $category = trim((string) ($_POST['category'] ?? ''));
    $accessType = (string) ($_POST['access_type'] ?? 'free');
    $status = (string) ($_POST['status'] ?? 'active');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);

    if ($name === '') {
        http_response_code(422);
        echo json_encode(['error' => 'Nome obrigatório'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (!in_array($accessType, ['free', 'login', 'paid'], true)) {
        $accessType = 'free';
    }
    if (!in_array($status, ['active', 'development', 'unavailable'], true)) {
        $status = 'active';
    }

    $cols = $pdo->query('SHOW COLUMNS FROM tools')->fetchAll(PDO::FETCH_COLUMN);

    // UPDATE com placeholders posicionais (padrão MySQL/PDO estável)
    $fields = [];
    $values = [];

    $fields[] = 'name = ?';
    $values[] = $name;
    $fields[] = 'description = ?';
    $values[] = $description;
    $fields[] = 'category = ?';
    $values[] = $category;
    $fields[] = 'is_active = ?';
    $values[] = $isActive;

    if (in_array('access_type', $cols, true)) {
        $fields[] = 'access_type = ?';
        $values[] = $accessType;
    }
    if (in_array('status', $cols, true)) {
        $fields[] = 'status = ?';
        $values[] = $status;
    }
    if (in_array('is_featured', $cols, true)) {
        $fields[] = 'is_featured = ?';
        $values[] = $isFeatured;
    }
    if (in_array('sort_order', $cols, true)) {
        $fields[] = 'sort_order = ?';
        $values[] = $sortOrder;
    }

    $values[] = $id;
    $sql = 'UPDATE tools SET ' . implode(', ', $fields) . ' WHERE id = ? LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);

    if ($stmt->rowCount() === 0) {
        // Pode ser 0 se os dados forem iguais — ainda assim ok
    }

    try {
        $mode = ($accessType === 'free') ? 'free' : 'temporary';
        $pdo->prepare(
            'INSERT INTO tool_access_rules (tool_id, access_mode, guest_allowed)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE access_mode = VALUES(access_mode), guest_allowed = VALUES(guest_allowed)'
        )->execute([$id, $mode, $accessType === 'free' ? 1 : 0]);
    } catch (Throwable $e) {
        error_log('[tools api rules] ' . $e->getMessage());
    }

    record_admin_activity('tool_update', 'Atualizou ferramenta #' . $id . ' (' . $name . ')');
    echo json_encode(['ok' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $e) {
    error_log('[admin tools api] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Não foi possível salvar a ferramenta.'], JSON_UNESCAPED_UNICODE);
    exit;
}
