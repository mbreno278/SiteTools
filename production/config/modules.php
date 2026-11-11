<?php

declare(strict_types=1);

/**
 * SiteTools — feature flags / módulos
 *
 * true  = módulo ativo
 * false = módulo desligado (UI e endpoints devem respeitar)
 *
 * beta.unlimited_usage: quando true, não aplica limites de uso pagos
 * mesmo se a ferramenta estiver como paid/temporary no banco.
 */

return [
    'modules' => [
        /** Painel /admin */
        'admin' => false,

        /** Área logada /account */
        'account' => false,

        /** Login, cadastro, sessão de usuário no front */
        'authentication' => false,

        /** Cobrança, webhooks, grants via pagamento */
        'payments' => false,

        /** Ferramentas paid/temporary exigem grant (user_tool_access) */
        'premium' => false,

        /** YouTube, Instagram, TikTok (handlers de download) */
        'video_downloads' => false,
    ],

    'beta' => [
        /** Modo beta global (mensagens / comportamento relaxed) */
        'enabled' => true,

        /** Ignora limites de uso e trata paid como acessível sem grant */
        'unlimited_usage' => true,
    ],
];
