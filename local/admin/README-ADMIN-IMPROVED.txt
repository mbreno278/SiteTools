SiteTools Admin — Melhorias
===========================

Arquivos para substituir/adicionar na pasta admin/:

  includes/auth.php          (sessão + rate limit)
  login.php                  (UI + segurança)
  logout.php
  pages/dashboard.php        (visão geral melhorada)
  assets/css/login.css       (NOVO)
  assets/css/dashboard-extra.css  (NOVO)

No includes/header.php ou layout, adicione no <head>:

  <link rel="stylesheet" href="<?= e(admin_url('assets/css/dashboard-extra.css')) ?>">

No login.php já carrega login.css.

Melhorias:
- Login com painel lateral, mostrar/ocultar senha
- Bloqueio após 5 tentativas (15 min)
- Sessão: 2h absolutas + 45 min inatividade
- Dashboard com saudação, usuários do site, top ferramentas, atalhos
- Logout mais limpo

Não remove suas outras páginas (tools, users, etc.).
