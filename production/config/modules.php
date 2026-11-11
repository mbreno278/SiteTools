<?php

declare(strict_types=1);

/**
 * Feature flags — PRODUÇÃO
 * Ajuste só se precisar desligar um bloco sem redeploy completo.
 */

return [
    'modules' => [
        'admin' => true,
        'account' => true,
        'authentication' => true,
        'payments' => true,
        'premium' => true,
        'video_downloads' => true,
    ],
    'beta' => [
        'enabled' => false,
        'unlimited_usage' => false,
    ],
];
