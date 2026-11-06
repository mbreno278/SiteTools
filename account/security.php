<?php
require __DIR__ . '/_bootstrap.php';

$uid = (int) $currentUser['id'];

// Dados básicos de segurança
$memberSince = null;
$lastLogin = null;
$emailVerified = null;

try {
    $stmt = $pdo->prepare('SELECT created_at, last_login_at, email_verified_at FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $uid]);
    $info = $stmt->fetch();
    if ($info) {
        $memberSince = $info['created_at'] ?? null;
        $lastLogin = $info['last_login_at'] ?? null;
        $emailVerified = $info['email_verified_at'] ?? null;
    }
} catch (Throwable $e) {
    // colunas opcionais
}

account_page_start('Segurança');
account_header('Segurança', 'Acompanhe o status da sua conta e recomendações de segurança.');
?>

<section class="ua-panel">
    <div class="ua-panel-head">
        <h2>Status da conta</h2>
    </div>

    <div class="ua-security-grid">
        <div class="ua-security-item">
            <div class="ua-security-icon <?= $emailVerified ? 'is-ok' : 'is-warn' ?>">
                <span class="material-symbols-outlined"><?= $emailVerified ? 'verified' : 'mark_email_unread' ?></span>
            </div>
            <div>
                <strong>E-mail verificado</strong>
                <small>
                    <?= $emailVerified
                        ? 'Verificado em ' . user_area_e(date('d/m/Y', strtotime($emailVerified)))
                        : 'Seu e-mail ainda não foi verificado.' ?>
                </small>
            </div>
            <?php if (!$emailVerified): ?>
                <a class="ua-button ua-button--light" href="../verificar-email.php">Verificar agora</a>
            <?php else: ?>
                <span class="ua-status">OK</span>
            <?php endif; ?>
        </div>

        <div class="ua-security-item">
            <div class="ua-security-icon is-ok">
                <span class="material-symbols-outlined">lock</span>
            </div>
            <div>
                <strong>Senha</strong>
                <small>Recomendamos alterar a senha periodicamente.</small>
            </div>
            <a class="ua-button ua-button--light" href="profile.php">Alterar senha</a>
        </div>

        <div class="ua-security-item">
            <div class="ua-security-icon is-ok">
                <span class="material-symbols-outlined">schedule</span>
            </div>
            <div>
                <strong>Último acesso</strong>
                <small>
                    <?= $lastLogin
                        ? user_area_e(date('d/m/Y H:i', strtotime($lastLogin)))
                        : 'Informação não disponível' ?>
                </small>
            </div>
        </div>

        <div class="ua-security-item">
            <div class="ua-security-icon is-ok">
                <span class="material-symbols-outlined">calendar_month</span>
            </div>
            <div>
                <strong>Conta criada em</strong>
                <small>
                    <?= $memberSince
                        ? user_area_e(date('d/m/Y', strtotime($memberSince)))
                        : 'Informação não disponível' ?>
                </small>
            </div>
        </div>
    </div>
</section>

<section class="ua-panel">
    <div class="ua-panel-head">
        <h2>Recomendações</h2>
    </div>
    <ul class="ua-tips">
        <li>
            <span class="material-symbols-outlined">password</span>
            Use uma senha forte e exclusiva para o SiteTools.
        </li>
        <li>
            <span class="material-symbols-outlined">logout</span>
            Sempre saia da conta em dispositivos compartilhados.
        </li>
        <li>
            <span class="material-symbols-outlined">visibility_off</span>
            Não compartilhe links de redefinição de senha ou códigos de verificação.
        </li>
        <li>
            <span class="material-symbols-outlined">mail</span>
            Mantenha seu e-mail atualizado e verificado para recuperar o acesso quando necessário.
        </li>
    </ul>
</section>

<section class="ua-panel">
    <div class="ua-panel-head">
        <h2>Sessão atual</h2>
    </div>
    <p class="ua-note">
        Você está autenticado nesta sessão. Se notar atividade suspeita, altere sua senha imediatamente
        e entre em contato com o suporte.
    </p>
    <div class="ua-form-actions">
        <a class="ua-button ua-button--danger" href="../tools/logout.php">Encerrar sessão</a>
        <a class="ua-button ua-button--light" href="../contact.php?assunto=seguranca">Reportar problema</a>
    </div>
</section>

<?php account_footer(); ?>
