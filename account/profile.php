<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

$uid = (int) $currentUser['id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name = trim((string) ($_POST['name'] ?? ''));

    if (mb_strlen($name) < 2 || mb_strlen($name) > 80) {
        $error = 'Informe um nome válido (2 a 80 caracteres).';
    } else {
        try {
            $stmt = $pdo->prepare('UPDATE users SET name = :name, updated_at = NOW() WHERE id = :id AND deleted_at IS NULL');
            $stmt->execute(['name' => $name, 'id' => $uid]);
            $_SESSION['user_name'] = $name;
            $accountName = $name;
            $currentUser['name'] = $name;
            $message = 'Nome atualizado com sucesso.';
        } catch (Throwable $e) {
            error_log('[account/profile] ' . $e->getMessage());
            $error = 'Não foi possível salvar. Tente novamente.';
        }
    }
}

// Dados frescos do banco
$dbUser = $currentUser;
try {
    $stmt = $pdo->prepare(
        'SELECT name, email, created_at, last_login_at, email_verified_at
         FROM users WHERE id = :id LIMIT 1'
    );
    $stmt->execute(['id' => $uid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $dbUser = $row;
    }
} catch (Throwable) {
}

$csrf = function_exists('csrf_token') ? csrf_token() : '';

account_page_start('Perfil');
account_header('Perfil', 'Informações da sua conta SiteTools.');
?>
<section class="ua-panel">
    <?php if ($message): ?><p class="ua-note" style="background:#ecfdf5;color:#065f46"><?= user_area_e($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="ua-note" style="background:#fff1f2;color:#9f1239"><?= user_area_e($error) ?></p><?php endif; ?>

    <form method="post" class="ua-form-row" style="grid-template-columns:1fr">
        <input type="hidden" name="_csrf" value="<?= user_area_e($csrf) ?>">
        <div class="ua-field">
            <label for="name">Nome</label>
            <input id="name" name="name" maxlength="80" required value="<?= user_area_e((string) ($dbUser['name'] ?? '')) ?>">
        </div>
        <div class="ua-field">
            <label>E-mail</label>
            <input value="<?= user_area_e((string) ($dbUser['email'] ?? '')) ?>" readonly>
        </div>
        <div class="ua-field">
            <label>Conta criada em</label>
            <input value="<?= !empty($dbUser['created_at']) ? user_area_e(date('d/m/Y H:i', strtotime((string) $dbUser['created_at']))) : '—' ?>" readonly>
        </div>
        <div class="ua-field">
            <label>Último login</label>
            <input value="<?= !empty($dbUser['last_login_at']) ? user_area_e(date('d/m/Y H:i', strtotime((string) $dbUser['last_login_at']))) : '—' ?>" readonly>
        </div>
        <div class="ua-field">
            <label>E-mail verificado</label>
            <input value="<?= !empty($dbUser['email_verified_at']) ? 'Sim' : 'Não' ?>" readonly>
        </div>
        <div>
            <button type="submit" class="ua-button">Salvar nome</button>
            <a class="ua-button ua-button--light" href="security.php">Alterar senha</a>
        </div>
    </form>
</section>
<?php account_footer(); ?>
