<?php

declare(strict_types=1);

/**
 * Feature flags — PRODUÇÃO
 * Ajuste só se precisar desligar um bloco sem redeploy completo.
 */

return [
    'modules' => [
        'admin' => false,
        'account' => false,
        'authentication' => false,
        'payments' => false,
        'premium' => false,
        'video_downloads' => false,
    ],
    'beta' => [
        'enabled' => true,
        'unlimited_usage' => false,
    ],
];
