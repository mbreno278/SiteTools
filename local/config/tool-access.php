<?php

declare(strict_types=1);
//tool-access.php
/**
 * SiteTools — controle de acesso (modelo v2)
 *
 * Operacional (tools.status):
 *   active | development | unavailable
 *
 * Comercial (tools.access_type OU tool_access_rules.access_mode):
 *   free | login | paid | temporary
 *
 * guest_allowed: se 1, visitante pode usar quando mode=free
 *
 * Comportamento paid/temporary:
 *   - Visitante ou logado sem grant → reason = payment_required (tela premium)
 *   - Logado com grant ativo → allowed = true
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/modules-helper.php';

function tool_access_labels(): array
{
    return [
        'free'         => 'Gratuito',
        'login'        => 'Requer login',
        'paid'         => 'Pago',
        'temporary'    => 'Acesso temporário',
        'development'  => 'Em desenvolvimento',
        'unavailable'  => 'Indisponível',
        'active'       => 'Ativa',
    ];
}

function tool_access_normalize_mode(?string $mode): string
{
    $mode = strtolower(trim((string) $mode));
    $allowed = ['free', 'login', 'paid', 'temporary'];
    return in_array($mode, $allowed, true) ? $mode : 'free';
}

function tool_access_normalize_status(?string $status): string
{
    $status = strtolower(trim((string) $status));
    $allowed = ['active', 'development', 'unavailable'];
    return in_array($status, $allowed, true) ? $status : 'active';
}

/**
 * Resolve status operacional + modo comercial.
 *
 * @return array{status:string,mode:string,guest_allowed:bool}
 */
function tool_access_resolve(PDO $pdo, array $tool): array
{
    $status = tool_access_normalize_status(
        (string) ($tool['status'] ?? 'active')
    );

    // Compat: access_type legado development/unavailable
    $legacy = strtolower((string) ($tool['access_type'] ?? ''));
    if (in_array($legacy, ['development', 'unavailable'], true)) {
        $status = $legacy;
    }

    $mode = tool_access_normalize_mode(
        (string) ($tool['access_type'] ?? 'free')
    );
    if (in_array($legacy, ['development', 'unavailable'], true)) {
        $mode = 'free'; // status já bloqueia
    }

    $guestAllowed = ($mode === 'free');
    $toolId = (int) ($tool['id'] ?? 0);

    if ($toolId > 0) {
        try {
            $stmt = $pdo->prepare(
                'SELECT access_mode, guest_allowed
                 FROM tool_access_rules WHERE tool_id = :id LIMIT 1'
            );
            $stmt->execute(['id' => $toolId]);
            $rule = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($rule) {
                $mode = tool_access_normalize_mode((string) $rule['access_mode']);
                $guestAllowed = (int) ($rule['guest_allowed'] ?? 0) === 1;
            }
        } catch (Throwable) {
            // regras opcionais
        }
    }

    return [
        'status' => $status,
        'mode' => $mode,
        'guest_allowed' => $guestAllowed,
    ];
}

function tool_user_has_grant(PDO $pdo, int $userId, int $toolId): bool
{
    if ($userId <= 0 || $toolId <= 0) {
        return false;
    }

    try {
        $stmt = $pdo->prepare(
            "SELECT id FROM user_tool_access
             WHERE user_id = :uid
               AND tool_id = :tid
               AND status = 'active'
               AND (revoked_at IS NULL)
               AND starts_at <= NOW()
               AND (expires_at IS NULL OR expires_at > NOW())
             LIMIT 1"
        );
        $stmt->execute(['uid' => $userId, 'tid' => $toolId]);
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        // Fail-closed: erro ao consultar grant = sem acesso
        error_log('[tool_user_has_grant] ' . $e->getMessage());
        return false;
    }
}

/**
 * @return array{
 *   allowed:bool,
 *   mode:string,
 *   status:string,
 *   reason:string,
 *   label:string,
 *   tool_slug?:string,
 *   logged_in?:bool
 * }
 */
function tool_access_check(PDO $pdo, array $tool): array
{
    // Ferramenta desativada no painel = nunca libera
    if (array_key_exists('is_active', $tool) && (int) $tool['is_active'] !== 1) {
        return [
            'allowed' => false,
            'mode' => 'unavailable',
            'status' => 'unavailable',
            'reason' => 'unavailable',
            'label' => 'Indisponível',
            'tool_slug' => (string) ($tool['slug'] ?? ''),
            'logged_in' => false,
        ];
    }

    $resolved = tool_access_resolve($pdo, $tool);
    $status = $resolved['status'];
    $mode = $resolved['mode'];
    $guestAllowed = $resolved['guest_allowed'];
    $labels = tool_access_labels();
    $slug = (string) ($tool['slug'] ?? '');

    if ($status === 'unavailable') {
        return [
            'allowed' => false,
            'mode' => $mode,
            'status' => $status,
            'reason' => 'unavailable',
            'label' => $labels['unavailable'],
            'tool_slug' => $slug,
            'logged_in' => false,
        ];
    }

    if ($status === 'development') {
        return [
            'allowed' => false,
            'mode' => $mode,
            'status' => $status,
            'reason' => 'development',
            'label' => $labels['development'],
            'tool_slug' => $slug,
            'logged_in' => false,
        ];
    }

    // is_active = 0 (defesa extra)
    if (isset($tool['is_active']) && (int) $tool['is_active'] !== 1) {
        return [
            'allowed' => false,
            'mode' => $mode,
            'status' => 'unavailable',
            'reason' => 'unavailable',
            'label' => $labels['unavailable'],
            'tool_slug' => $slug,
            'logged_in' => false,
        ];
    }

    $user = function_exists('auth_user') ? auth_user() : null;
    $userId = (int) ($user['id'] ?? 0);
    $loggedIn = $userId > 0;

    // ---------- free ----------
    if ($mode === 'free') {
        if ($guestAllowed || $loggedIn) {
            return [
                'allowed' => true,
                'mode' => $mode,
                'status' => $status,
                'reason' => $loggedIn ? 'logged_in' : 'public',
                'label' => $labels['free'],
                'tool_slug' => $slug,
                'logged_in' => $loggedIn,
            ];
        }

        // free com guest_allowed = 0 e visitante
        return [
            'allowed' => false,
            'mode' => $mode,
            'status' => $status,
            'reason' => 'login_required',
            'label' => $labels['login'],
            'tool_slug' => $slug,
            'logged_in' => false,
        ];
    }

    // ---------- login ----------
    if ($mode === 'login') {
        if ($loggedIn) {
            return [
                'allowed' => true,
                'mode' => $mode,
                'status' => $status,
                'reason' => 'logged_in',
                'label' => $labels['login'],
                'tool_slug' => $slug,
                'logged_in' => true,
            ];
        }

        return [
            'allowed' => false,
            'mode' => $mode,
            'status' => $status,
            'reason' => 'login_required',
            'label' => $labels['login'],
            'tool_slug' => $slug,
            'logged_in' => false,
        ];
    }

    // ---------- paid | temporary ----------
    // Sempre trata como premium na UI (payment_required),
    // inclusive para visitante — não força só a tela de login.
    if ($mode === 'paid' || $mode === 'temporary') {
        if ($loggedIn) {
            $toolId = (int) ($tool['id'] ?? 0);
            if (tool_user_has_grant($pdo, $userId, $toolId)) {
                return [
                    'allowed' => true,
                    'mode' => $mode,
                    'status' => $status,
                    'reason' => 'paid_active',
                    'label' => $labels[$mode] ?? $labels['paid'],
                    'tool_slug' => $slug,
                    'logged_in' => true,
                ];
            }
        }

        return [
            'allowed' => false,
            'mode' => $mode,
            'status' => $status,
            'reason' => 'payment_required',
            'label' => $labels[$mode] ?? $labels['paid'],
            'tool_slug' => $slug,
            'logged_in' => $loggedIn,
        ];
    }

    // Fallback fail-closed
    return [
        'allowed' => false,
        'mode' => $mode,
        'status' => $status,
        'reason' => 'unavailable',
        'label' => $labels['unavailable'],
        'tool_slug' => $slug,
        'logged_in' => $loggedIn,
    ];
}

function tool_access_badge_html(string $modeOrStatus): string
{
    $key = strtolower(trim($modeOrStatus));
    $labels = tool_access_labels();
    $label = htmlspecialchars($labels[$key] ?? $key, ENT_QUOTES, 'UTF-8');
    $class = 'tool-badge tool-badge--' . preg_replace('/[^a-z]/', '', $key);
    return '<span class="' . $class . '">' . $label . '</span>';
}

function tool_access_block_html(array $check): string
{
    $reason = $check['reason'] ?? '';
    $title = 'Ferramenta indisponível';
    $message = 'Esta ferramenta não está disponível no momento.';
    $action = '';
    $loggedIn = !empty($check['logged_in']);

    if ($reason === 'development') {
        $title = 'Em desenvolvimento';
        $message = 'Estamos trabalhando nesta ferramenta. Em breve estará disponível.';
    } elseif ($reason === 'unavailable') {
        $title = 'Indisponível';
        $message = 'Esta ferramenta está temporariamente fora do ar.';
    } elseif ($reason === 'login_required') {
        $title = 'Login necessário';
        $message = 'Faça login para usar esta ferramenta.';
        $action = '<button type="button" class="button button--primary" data-auth-open="login">Fazer login</button>';
    } elseif ($reason === 'payment_required') {
        $title = 'Ferramenta paga';
        $message = 'Esta é uma ferramenta premium. Adquira o acesso para continuar.';

        if ($loggedIn) {
            $action = '<a class="button button--primary" href="./account/">Ver planos / Minha conta</a>';
        } else {
            $action =
                '<a class="button button--primary" href="./account/">Ver planos</a>'
                . '<button type="button" class="button" data-auth-open="login">Já tenho conta — entrar</button>';
        }
    }

    return '<div class="tool-access-lock" role="status">'
        . '<span class="material-symbols-outlined tool-access-lock__icon" aria-hidden="true">lock</span>'
        . '<h2 class="tool-access-lock__title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>'
        . '<p class="tool-access-lock__text">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p class="tool-access-lock__badge">' . tool_access_badge_html((string) ($check['mode'] ?? $check['status'] ?? 'unavailable')) . '</p>'
        . ($action !== '' ? '<div class="tool-access-lock__actions">' . $action . '</div>' : '')
        . '</div>';
}

function tool_access_deny_json(array $check): never
{
    $messages = [
        'unavailable' => 'Esta ferramenta está indisponível.',
        'development' => 'Esta ferramenta está em desenvolvimento.',
        'login_required' => 'Faça login para usar esta ferramenta.',
        'payment_required' => 'Esta ferramenta é paga. Adquira o acesso para continuar.',
    ];
    $reason = (string) ($check['reason'] ?? 'denied');
    $status = match ($reason) {
        'login_required' => 401,
        'payment_required' => 402,
        'development', 'unavailable' => 503,
        default => 403,
    };
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => $messages[$reason] ?? 'Acesso negado.',
        'reason' => $reason,
        'mode' => $check['mode'] ?? null,
        'status' => $check['status'] ?? null,
        'logged_in' => !empty($check['logged_in']),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function tool_access_require(PDO $pdo, string $slug): array
{
    $stmt = $pdo->prepare(
        'SELECT id, name, slug, access_type, status, is_active, handler
         FROM tools WHERE slug = :slug LIMIT 1'
    );
    $stmt->execute(['slug' => $slug]);
    $tool = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tool) {
        // Fail-closed: slug desconhecido no banco não processa no endpoint
        tool_access_deny_json([
            'allowed' => false,
            'mode' => 'unavailable',
            'status' => 'unavailable',
            'reason' => 'unavailable',
            'label' => 'Indisponível',
            'logged_in' => false,
        ]);
    }

    $check = tool_access_check($pdo, $tool);
    if (!$check['allowed']) {
        tool_access_deny_json($check);
    }
    return $check;
}

/**
 * @return list<array>
 */
function tool_list_by_access(PDO $pdo, ?string $mode = null, bool $onlyActive = true): array
{
    $sql = 'SELECT id, name, slug, description, category, category_id, icon, handler,
                   access_type, status, is_active, is_featured, sort_order
            FROM tools WHERE 1=1';
    $params = [];

    if ($onlyActive) {
        $sql .= " AND is_active = 1 AND (status = 'active' OR status IS NULL OR status = '')";
    }
    if ($mode !== null) {
        $sql .= ' AND access_type = :mode';
        $params['mode'] = tool_access_normalize_mode($mode);
    }
    $sql .= ' ORDER BY sort_order ASC, name ASC';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable) {
        return [];
    }
}
