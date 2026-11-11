<?php

declare(strict_types=1);

/**
 * EXEMPLO de endpoint no padrão em camadas.
 * Não substitui os handlers existentes — serve de referência de migração.
 *
 * Fluxo: CSRF → ToolAccess + Quota → validação → processamento → Response JSON
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Controllers\ToolProcessController;
use App\Exceptions\ValidationException;
use App\Helpers\Response;

(new ToolProcessController())->run('word-to-pdf', function (array $ctx): void {
    if (empty($_FILES['word'])) {
        throw new ValidationException('Selecione um arquivo Word.');
    }

    // Aqui entraria DocumentConverterService / lógica atual de word-to-pdf.php
    // Por enquanto apenas demonstra o envelope de resposta.

    Response::success([
        'tool' => $ctx['tool']['name'] ?? 'word-to-pdf',
        'note' => 'Substitua este bloco pela conversão real ao migrar o handler.',
    ], 'Requisição autorizada.');
});
