<?php

declare(strict_types=1);

/**
 * Placeholder — pagamentos devem usar handlers dedicados.
 */

http_response_code(410);
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'error' => 'Endpoint de pagamento não disponível neste caminho.',
    'code' => 'gone',
], JSON_UNESCAPED_UNICODE);
exit;
