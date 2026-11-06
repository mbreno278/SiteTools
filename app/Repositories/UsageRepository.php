<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UsageRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function getLimit(int $toolId, string $userType, string $period = 'daily'): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT max_uses, period FROM tool_usage_limits
             WHERE tool_id = :tid AND user_type = :ut AND period = :period
             LIMIT 1'
        );
        $stmt->execute(['tid' => $toolId, 'ut' => $userType, 'period' => $period]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getCounter(int $toolId, string $subjectType, string $subjectKey, string $period, string $periodKey): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT use_count FROM tool_usage_counters
             WHERE tool_id = :tid AND subject_type = :st AND subject_key = :sk
               AND period_type = :pt AND period_key = :pk
             LIMIT 1'
        );
        $stmt->execute([
            'tid' => $toolId,
            'st' => $subjectType,
            'sk' => $subjectKey,
            'pt' => $period,
            'pk' => $periodKey,
        ]);
        $val = $stmt->fetchColumn();
        return $val === false ? 0 : (int) $val;
    }

    public function incrementCounter(int $toolId, string $subjectType, string $subjectKey, string $period, string $periodKey): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO tool_usage_counters (tool_id, subject_type, subject_key, period_type, period_key, use_count)
             VALUES (:tid, :st, :sk, :pt, :pk, 1)
             ON DUPLICATE KEY UPDATE use_count = use_count + 1, updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            'tid' => $toolId,
            'st' => $subjectType,
            'sk' => $subjectKey,
            'pt' => $period,
            'pk' => $periodKey,
        ]);
    }

    public function logUserUsage(int $userId, int $toolId, string $slug, string $status, ?int $ms = null, ?int $bytes = null): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO user_tool_usage (user_id, tool_id, tool_slug, status, processing_time_ms, file_size_bytes)
             VALUES (:uid, :tid, :slug, :status, :ms, :bytes)'
        );
        $stmt->execute([
            'uid' => $userId,
            'tid' => $toolId,
            'slug' => $slug,
            'status' => $status,
            'ms' => $ms,
            'bytes' => $bytes,
        ]);
    }
}
