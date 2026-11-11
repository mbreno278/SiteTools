<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ToolRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, slug, description, category, category_id, icon, handler,
                    access_type, status, is_active, is_featured, sort_order, max_file_size
             FROM tools
             WHERE slug = :slug
             LIMIT 1'
        );
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findAccessRule(int $toolId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT access_mode, guest_allowed FROM tool_access_rules WHERE tool_id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $toolId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** @return list<array> */
    public function listActive(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, name, slug, description, category, icon, access_type, status, is_active, sort_order
             FROM tools
             WHERE is_active = 1
             ORDER BY sort_order ASC, name ASC"
        );
        return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    }
}
