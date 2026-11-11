<?php
/**
 * Exemplo de seções no index/tools.php
 * Grátis (público) | Com login | Pagas
 *
 * Requer: $pdo e config/tool-access.php
 */

require_once __DIR__ . '/../config/tool-access.php';

$freeTools = tool_list_by_access($pdo, 'free');
$loginTools = tool_list_by_access($pdo, 'login');
$paidTools = tool_list_by_access($pdo, 'paid');
$devTools = tool_list_by_access($pdo, 'development');

$user = function_exists('auth_user') ? auth_user() : null;
?>

<section class="tools-section">
    <h2 class="tools-section__title">
        Gratuitas
        <?= tool_access_badge_html('free') ?>
    </h2>
    <p class="muted">Use sem precisar de conta.</p>
    <div class="tools-grid">
        <?php foreach ($freeTools as $t): ?>
            <a class="tool-card" href="<?= e(tool_url((string) $t['slug'])) ?>">
                <strong><?= htmlspecialchars((string) $t['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?= tool_access_badge_html('free') ?>
            </a>
        <?php endforeach; ?>
        <?php if (!$freeTools): ?>
            <p class="muted">Nenhuma ferramenta gratuita no momento.</p>
        <?php endif; ?>
    </div>
</section>

<section class="tools-section">
    <h2 class="tools-section__title">
        Com login
        <?= tool_access_badge_html('login') ?>
    </h2>
    <p class="muted">Disponíveis para usuários cadastrados.</p>
    <div class="tools-grid">
        <?php foreach ($loginTools as $t): ?>
            <a class="tool-card" href="<?= e(tool_url((string) $t['slug'])) ?>">
                <strong><?= htmlspecialchars((string) $t['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?= tool_access_badge_html('login') ?>
            </a>
        <?php endforeach; ?>
        <?php if (!$loginTools): ?>
            <p class="muted">Nenhuma ferramenta nesta categoria.</p>
        <?php endif; ?>
    </div>
</section>

<?php if ($user): ?>
<section class="tools-section">
    <h2 class="tools-section__title">
        Premium
        <?= tool_access_badge_html('paid') ?>
    </h2>
    <p class="muted">Ferramentas pagas da sua conta.</p>
    <div class="tools-grid">
        <?php foreach ($paidTools as $t): ?>
            <a class="tool-card" href="<?= e(tool_url((string) $t['slug'])) ?>">
                <strong><?= htmlspecialchars((string) $t['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?= tool_access_badge_html('paid') ?>
            </a>
        <?php endforeach; ?>
        <?php if (!$paidTools): ?>
            <p class="muted">Nenhuma ferramenta premium cadastrada.</p>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($devTools): ?>
<section class="tools-section">
    <h2 class="tools-section__title">
        Em breve
        <?= tool_access_badge_html('development') ?>
    </h2>
    <div class="tools-grid">
        <?php foreach ($devTools as $t): ?>
            <div class="tool-card tool-card--disabled">
                <strong><?= htmlspecialchars((string) $t['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?= tool_access_badge_html('development') ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
