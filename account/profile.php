<?php
require __DIR__ . '/_bootstrap.php';

$uid = (int) $currentUser['id'];
$success = null;
$error = null;

// Processar atualização de nome
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();

    $action = $_POST['action'];

    if ($action === 'update_name') {
        $newName = trim((string) ($_POST['name'] ?? ''));

        if ($newName === '' || mb_strlen($newName) < 2) {
            $error = 'Informe um nome válido (mínimo 2 caracteres).';
        } elseif (mb_strlen($newName) > 80) {
            $error = 'O nome deve ter no máximo 80 caracteres.';
        } else {
            try {
                $stmt = $pdo->prepare('UPDATE users SET name = :name, updated_at = NOW() WHERE id = :id');
                $stmt->execute(['name' => $newName, 'id' => $uid]);
                $currentUser['name'] = $newName;
                $accountName = $newName;
                $success = 'Nome atualizado com sucesso.';
            } catch (Throwable $e) {
                $error = 'Não foi possível atualizar o nome. Tente novamente.';
            }
        }
    }

    if ($action === 'change_password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $newPass = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        if ($current === '' || $newPass === '' || $confirm === '') {
            $error = 'Preencha todos os campos de senha.';
        } elseif (strlen($newPass) < 8) {
            $error = 'A nova senha deve ter no mínimo 8 caracteres.';
        } elseif ($newPass !== $confirm) {
            $error = 'A confirmação da senha não confere.';
        } else {
            // Buscar hash atual (coluna real do schema: password_hash)
            $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $uid]);
            $hash = $stmt->fetchColumn();

            if (!$hash || !password_verify($current, (string) $hash)) {
                $error = 'Senha atual incorreta.';
            } else {
                try {
                    $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                    $upd = $pdo->prepare('UPDATE users SET password_hash = :pass, updated_at = NOW() WHERE id = :id');
                    $upd->execute(['pass' => $newHash, 'id' => $uid]);
                    $success = 'Senha alterada com sucesso.';
                } catch (Throwable $e) {
                    $error = 'Não foi possível alterar a senha. Tente novamente.';
                }
            }
        }
    }
}

// Dados extras da conta
$memberSince = null;
$lastLogin = null;
try {
    $stmt = $pdo->prepare('SELECT created_at, last_login_at FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $uid]);
    $info = $stmt->fetch();
    if ($info) {
        $memberSince = $info['created_at'] ?? null;
        $lastLogin = $info['last_login_at'] ?? null;
    }
} catch (Throwable $e) {
    // colunas opcionais
}

account_page_start('Minha conta');
account_header('Minha conta', 'Gerencie suas informações pessoais e preferências.');
?>

<?php if ($success): ?>
    <div class="ua-alert ua-alert--success">
        <span class="material-symbols-outlined">check_circle</span>
        <?= user_area_e($success) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="ua-alert ua-alert--error">
        <span class="material-symbols-outlined">error</span>
        <?= user_area_e($error) ?>
    </div>
<?php endif; ?>

<section class="ua-panel">
    <div class="ua-panel-head">
        <h2>Informações da conta</h2>
    </div>

    <div class="ua-profile-header">
        <div class="ua-avatar ua-avatar--lg"><?= user_area_e(strtoupper(substr($accountName, 0, 1))) ?></div>
        <div>
            <strong class="ua-profile-name"><?= user_area_e($accountName) ?></strong>
            <p class="ua-profile-email"><?= user_area_e($accountEmail) ?></p>
            <div class="ua-profile-meta">
                <?php if ($memberSince): ?>
                    <span>Membro desde <?= user_area_e(date('d/m/Y', strtotime($memberSince))) ?></span>
                <?php endif; ?>
                <?php if ($lastLogin): ?>
                    <span>Último acesso: <?= user_area_e(date('d/m/Y H:i', strtotime($lastLogin))) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="ua-panel">
    <div class="ua-panel-head">
        <h2>Editar nome</h2>
    </div>
    <form method="post" class="ua-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_name">
        <div class="ua-form-row">
            <div class="ua-field">
                <label for="name">Nome completo</label>
                <input type="text" id="name" name="name" value="<?= user_area_e($accountName) ?>" required maxlength="80">
            </div>
            <div class="ua-field">
                <label for="email">E-mail</label>
                <input type="email" id="email" value="<?= user_area_e($accountEmail) ?>" readonly disabled>
                <small class="ua-field-hint">O e-mail não pode ser alterado por aqui.</small>
            </div>
        </div>
        <div class="ua-form-actions">
            <button type="submit" class="ua-button">Salvar nome</button>
        </div>
    </form>
</section>

<section class="ua-panel">
    <div class="ua-panel-head">
        <h2>Alterar senha</h2>
    </div>
    <form method="post" class="ua-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="change_password">
        <div class="ua-form-row">
            <div class="ua-field">
                <label for="current_password">Senha atual</label>
                <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
            </div>
        </div>
        <div class="ua-form-row">
            <div class="ua-field">
                <label for="new_password">Nova senha</label>
                <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
                <small class="ua-field-hint">Mínimo de 8 caracteres.</small>
            </div>
            <div class="ua-field">
                <label for="confirm_password">Confirmar nova senha</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password">
            </div>
        </div>
        <div class="ua-form-actions">
            <button type="submit" class="ua-button">Alterar senha</button>
        </div>
    </form>
</section>

<section class="ua-panel">
    <div class="ua-panel-head">
        <h2>Zona de perigo</h2>
    </div>
    <p class="ua-note">
        A exclusão permanente da conta remove seus dados de uso, favoritos e arquivos salvos.
        Esta ação não pode ser desfeita. Em caso de necessidade, entre em contato com o suporte.
    </p>
    <div class="ua-form-actions">
        <a class="ua-button ua-button--danger" href="../contact.php?assunto=exclusao-conta">Solicitar exclusão de conta</a>
    </div>
</section>

<?php account_footer(); ?>
