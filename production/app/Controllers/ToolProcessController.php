<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Middleware\CsrfMiddleware;
use App\Middleware\ToolAccessMiddleware;
use App\Services\UsageLimitService;
use Throwable;

/**
 * Controller fino de exemplo para endpoints de ferramentas.
 *
 * Os handlers legados em tools/*.php podem migrar gradualmente para este padrão:
 *
 *   require app/bootstrap.php
 *   (new ToolProcessController())->run('word-to-pdf', function(array $ctx) { ... });
 */
final class ToolProcessController
{
    /**
     * @param callable(array):void $processor recebe contexto [tool, check, pdo]
     */
    public function run(string $slug, callable $processor): void
    {
        try {
            (new CsrfMiddleware())->handle();

            $pdo = app_pdo();
            $access = ToolAccessMiddleware::make($pdo, true);
            $check = $access->handle($slug);

            $processor([
                'slug' => $slug,
                'tool' => $check['tool'] ?? null,
                'check' => $check,
                'pdo' => $pdo,
            ]);

            // Registra uso após sucesso do processor (se não encerrou a request)
            $toolId = (int) (($check['tool']['id'] ?? 0));
            if ($toolId > 0) {
                $userId = null;
                if (function_exists('auth_user')) {
                    $u = auth_user();
                    $userId = $u ? (int) $u['id'] : null;
                }
                $hasPaid = ($check['reason'] ?? '') === 'paid_active';
                UsageLimitService::make($pdo)->recordUse($toolId, $userId, $hasPaid);
            }
        } catch (Throwable $e) {
            // ErrorHandler global trata AppException; demais viram 500 seguro
            throw $e;
        }
    }
}
