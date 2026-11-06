<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

/**
 * Ferramentas sem regra cadastrada permanecem públicas.
 * Somente regras access_mode=temporary exigem uma concessão válida.
 */
function tool_access_check(PDO $pdo, array $tool): array
{
    $toolId = (int) ($tool['id'] ?? 0);
    $slug = (string) ($tool['slug'] ?? '');

    if ($toolId <= 0) {
        return ['allowed' => true, 'reason' => 'unregistered'];
    }

    $stmt = $pdo->prepare(
        'SELECT access_mode, guest_allowed
         FROM tool_access_rules
         WHERE tool_id = :tool_id LIMIT 1'
    );
    $stmt->execute(['tool_id' => $toolId]);
    $rule = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rule || $rule['access_mode'] === 'free') {
        return ['allowed' => true, 'reason' => 'public'];
    }

    $user = auth_user();

    if (!$user) {
        return [
            'allowed' => false,
            'reason' => 'login_required',
            'tool_slug' => $slug,
        ];
    }

    $access = $pdo->prepare(
        "SELECT id
         FROM user_tool_access
         WHERE user_id = :user_id
           AND tool_id = :tool_id
           AND access_type IN ('free','temporary')
           AND status = 'active'
           AND starts_at <= NOW()
           AND (expires_at IS NULL OR expires_at > NOW())
         ORDER BY expires_at IS NULL DESC, expires_at DESC
         LIMIT 1"
    );
    $access->execute([
        'user_id' => (int) $user['id'],
        'tool_id' => $toolId,
    ]);

    if ($access->fetchColumn()) {
        return ['allowed' => true, 'reason' => 'access_active'];
    }

    return [
        'allowed' => false,
        'reason' => 'access_required',
        'tool_slug' => $slug,
    ];
}
