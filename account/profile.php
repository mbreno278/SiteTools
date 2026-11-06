<?php
require __DIR__ . '/_bootstrap.php';
account_page_start('Minha conta');account_header('Minha conta','Informações básicas da sua conta SiteTools.');
?>
<section class="ua-panel">
<div class="ua-form-row">
<div class="ua-field"><label>Nome</label><input value="<?=user_area_e($currentUser['name']??'')?>" readonly></div>
<div class="ua-field"><label>E-mail</label><input value="<?=user_area_e($currentUser['email']??'')?>" readonly></div>
</div>
<p class="ua-note" style="margin-top:18px">Os dados são exibidos a partir da sessão de autenticação existente. Edição de perfil e alteração de senha continuam sob responsabilidade do módulo de conta/autenticação já instalado.</p>
</section>
<?php account_footer(); ?>
