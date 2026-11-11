<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AppException;
use App\Repositories\UsageRepository;
use PDO;

/**
 * Controle de cotas diárias (guest / registered / paid).
 */
final class UsageLimitService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly UsageRepository $usage,
    ) {
    }

    public static function make(PDO $pdo): self
    {
        return new self($pdo, new UsageRepository($pdo));
    }

    /**
     * @return array{allowed:bool,used:int,max:?int,remaining:?int,period:string}
     */
    public function check(int $toolId, ?int $userId, bool $hasPaidGrant = false): array
    {
        $userType = 'guest';
        $subjectType = 'guest';
        $subjectKey = $this->guestKey();

        if ($userId && $userId > 0) {
            $userType = $hasPaidGrant ? 'paid' : 'registered';
            $subjectType = 'user';
            $subjectKey = (string) $userId;
        }

        $period = 'daily';
        $periodKey = date('Y-m-d');
        $limit = $this->usage->getLimit($toolId, $userType, $period);

        if ($limit === null || $limit['max_uses'] === null) {
            return [
                'allowed' => true,
                'used' => 0,
                'max' => null,
                'remaining' => null,
                'period' => $period,
            ];
        }

        $max = (int) $limit['max_uses'];
        $used = $this->usage->getCounter($toolId, $subjectType, $subjectKey, $period, $periodKey);

        return [
            'allowed' => $used < $max,
            'used' => $used,
            'max' => $max,
            'remaining' => max(0, $max - $used),
            'period' => $period,
        ];
    }

    public function assertWithinLimit(int $toolId, ?int $userId, bool $hasPaidGrant = false): void
    {
        $check = $this->check($toolId, $userId, $hasPaidGrant);
        if (!$check['allowed']) {
            throw new AppException(
                'Limite de uso diário atingido para esta ferramenta.',
                429,
                'usage_limit_exceeded',
                $check
            );
        }
    }

    public function recordUse(int $toolId, ?int $userId, bool $hasPaidGrant = false): void
    {
        $subjectType = 'guest';
        $subjectKey = $this->guestKey();
        if ($userId && $userId > 0) {
            $subjectType = 'user';
            $subjectKey = (string) $userId;
        }
        $this->usage->incrementCounter($toolId, $subjectType, $subjectKey, 'daily', date('Y-m-d'));
    }

    private function guestKey(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return hash('sha256', $ip . '|' . substr($ua, 0, 80));
    }
}
