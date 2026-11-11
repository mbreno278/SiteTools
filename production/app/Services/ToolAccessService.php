<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AuthorizationException;
use App\Repositories\ToolRepository;
use PDO;

/**
 * Fachada de autorização de ferramentas.
 * Reutiliza a lógica legada de config/tool-access.php quando disponível.
 */
final class ToolAccessService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly ToolRepository $tools,
    ) {
    }

    public static function make(PDO $pdo): self
    {
        return new self($pdo, new ToolRepository($pdo));
    }

    /**
     * @return array{allowed:bool,mode:string,status:string,reason:string,label:string,tool?:array}
     */
    public function checkBySlug(string $slug): array
    {
        $tool = $this->tools->findBySlug($slug);
        if (!$tool) {
            throw new AuthorizationException('Ferramenta indisponível.', 'unavailable', 503);
        }

        if (function_exists('tool_access_check')) {
            $result = tool_access_check($this->pdo, $tool);
            $result['tool'] = $tool;
            return $result;
        }

        // Fallback mínimo se o legado não estiver carregado
        if ((int) ($tool['is_active'] ?? 0) !== 1) {
            return [
                'allowed' => false,
                'mode' => 'unavailable',
                'status' => 'unavailable',
                'reason' => 'unavailable',
                'label' => 'Indisponível',
                'tool' => $tool,
            ];
        }

        return [
            'allowed' => true,
            'mode' => (string) ($tool['access_type'] ?? 'free'),
            'status' => (string) ($tool['status'] ?? 'active'),
            'reason' => 'fallback',
            'label' => 'OK',
            'tool' => $tool,
        ];
    }

    /**
     * Exige acesso; lança AuthorizationException se negado.
     *
     * @return array tool + check
     */
    public function requireBySlug(string $slug): array
    {
        $check = $this->checkBySlug($slug);
        if (empty($check['allowed'])) {
            $reason = (string) ($check['reason'] ?? 'forbidden');
            $status = match ($reason) {
                'login_required' => 401,
                'payment_required' => 402,
                'development', 'unavailable', 'service_unavailable' => 503,
                default => 403,
            };
            $messages = [
                'login_required' => 'Faça login para usar esta ferramenta.',
                'payment_required' => 'Esta ferramenta é paga. Adquira o acesso para continuar.',
                'development' => 'Esta ferramenta está em desenvolvimento.',
                'unavailable' => 'Esta ferramenta está indisponível.',
            ];
            throw new AuthorizationException(
                $messages[$reason] ?? 'Acesso negado.',
                $reason,
                $status
            );
        }
        return $check;
    }
}
