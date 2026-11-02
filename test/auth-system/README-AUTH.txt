SiteTools — Login / Cadastro
============================

1) SQL
   Execute sql/users.sql no banco webtools__sitetools

2) Arquivos
   config/auth.php          → helpers de sessão
   tools/login.php          → API login
   tools/cadastro.php       → API cadastro
   tools/logout.php         → API logout
   content/content-login.php → HTML dos modais
   templates/header.php     → header com botões
   assets/css/auth.css
   assets/js/auth.js

3) Em index.php e page.php

   a) No topo (junto do csrf):
      require_once __DIR__ . '/config/auth.php';

   b) No <head>:
      <link rel="stylesheet" href="./assets/css/auth.css">
      <script src="./assets/js/auth.js" defer></script>
      (csrf.js deve vir antes do auth.js)

   c) Antes de </body>:
      <?php include __DIR__ . '/content/content-login.php'; ?>

4) Botões no header
   Login  = fundo branco + borda primary
   Cadastro = fundo primary

5) CSRF
   Já integrado (csrf_field + csrf_verify nos endpoints)
