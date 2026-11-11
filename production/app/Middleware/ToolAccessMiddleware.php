<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\ToolAccessService;
use App\Services\UsageLimitService;
use PDO;

/**
 * Autorização + cota antes de processar uma ferramenta.
 */
final class ToolAccessMiddleware
{
    public function __construct(
        private readonly ToolAccessService $access,
        private readonly ?UsageLimitService $limits = null,
    ) {
    }

    public static function make(PDO $pdo, bool $enforceLimits = true): self
    {
        return new self(
            ToolAccessService::make($pdo),
            $enforceLimits ? UsageLimitService::make($pdo) : null
        );
    }

    /**
     * @return array check + tool
     */
    public function handle(string $slug): array
    {
        $check = $this->access->requireBySlug($slug);
        $tool = $check['tool'] ?? null;
        $toolId = (int) ($tool['id'] ?? 0);

        if ($this->limits && $toolId > 0) {
            $userId = null;
            $hasPaid = ($check['reason'] ?? '') === 'paid_active';
            if (function_exists('auth_user')) {
                $user = auth_user();
                $userId = $user ? (int) $user['id'] : null;
            }
            $this->limits->assertWithinLimit($toolId, $userId, $hasPaid);
        }

        return $check;
    }
}
