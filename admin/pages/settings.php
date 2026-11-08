<?php

require_once __DIR__ . '/_guard.php';
$flash = '';
$settings = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $pdo = db();
        $keys = ['site_name', 'site_tagline', 'support_email', 'maintenance_mode'];
        foreach ($keys as $key) {
            if (!isset($_POST[$key])) continue;
            $val = trim((string) $_POST[$key]);
            $pdo->prepare(
                'INSERT INTO site_settings (setting_key, setting_value) VALUES (:k, :v)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
            )->execute(['k' => $key, 'v' => $val]);
        }
        record_admin_activity('settings_update', 'Atualizou configurações do site');
        $flash = 'Configurações salvas.';
    } catch (Throwable $e) {
        $flash = 'Erro ao salvar: ' . $e->getMessage();
    }
}

try {
    $rows = db()->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll();
    foreach ($rows as $r) {
        $settings[$r['setting_key']] = $r['setting_value'];
    }
} catch (Throwable) {
}
$get = static fn(string $k, string $d = '') => $settings[$k] ?? $d;
?>
<div class="page-heading">
    <div>
        <p class="eyebrow">SISTEMA</p>
        <h1>Configurações</h1>
        <p class="muted">Preferências gerais do SiteTools.</p>
    </div>
</div>
<?php if ($flash): ?><div class="alert alert-success"><?= e($flash) ?></div><?php endif; ?>

<div class="panel">
    <form method="post" class="admin-form">
        <?= csrf_field() ?>
        <div class="form-row">
            <div>
                <label class="field-label">Nome do site</label>
                <input class="form-control" name="site_name" value="<?= e($get('site_name', 'SiteTools')) ?>">
            </div>
            <div>
                <label class="field-label">E-mail de suporte</label>
                <input class="form-control" name="support_email" type="email" value="<?= e($get('support_email')) ?>">
            </div>
        </div>
        <label class="field-label">Slogan</label>
        <input class="form-control" name="site_tagline" value="<?= e($get('site_tagline', 'Ferramentas online para o seu dia a dia')) ?>">
        <label class="field-label" style="margin-top:12px;">Modo manutenção (0 ou 1)</label>
        <input class="form-control" name="maintenance_mode" value="<?= e($get('maintenance_mode', '0')) ?>">
        <button class="button button-primary" type="submit" style="margin-top:16px;">Salvar</button>
    </form>
</div>
