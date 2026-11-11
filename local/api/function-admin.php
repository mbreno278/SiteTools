<?php

declare(strict_types=1);

/**
 * Placeholder — endpoints administrativos ficam em /admin/api/.
 */

http_response_code(410);
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'error' => 'Endpoint movido. Use as rotas em /admin/api/.',
    'code' => 'gone',
], JSON_UNESCAPED_UNICODE);
exit;
