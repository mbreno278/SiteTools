# SiteTools — Admin Starter

Estrutura inicial da área administrativa do SiteTools, preparada para PHP 8+, MySQL/MariaDB, PDO, HTML, CSS e JavaScript.

## O que já está incluído

- Login administrativo com `password_hash()` / `password_verify()`.
- Proteção de páginas e APIs por sessão.
- Token CSRF para formulários e requisições POST.
- Layout responsivo com sidebar, header, navegação e tema claro/escuro.
- Dashboard com indicadores obtidos do banco de dados.
- Listagem inicial das ferramentas existentes na tabela `tools`.
- Páginas-base para categorias, usuários, analytics, arquivos, logs, anúncios, suporte, SEO, segurança, configurações e manutenção.
- Endpoints iniciais para dashboard, ferramentas, analytics e sistema.
- SQL para tabelas administrativas complementares.
- Pastas privadas para logs, temporários e backups.

## Instalação no WAMP

1. Faça backup do projeto atual.
2. Extraia o conteúdo do ZIP.
3. Copie as pastas/arquivos para dentro da pasta existente `SiteTools`, mesclando as pastas. **Não substitua o `index.php` público nem os arquivos atuais do projeto.**
4. Edite `config/database.php` e informe host, banco, usuário e senha.
5. Abra o phpMyAdmin e execute `database/schema.sql` no banco utilizado pelo SiteTools.
6. Gere um hash de senha no terminal:

   ```bash
   php -r "echo password_hash('SUA_SENHA_FORTE', PASSWORD_DEFAULT), PHP_EOL;"
   ```

7. Copie o hash gerado e execute o INSERT abaixo no banco:

   ```sql
   INSERT INTO admin_users (name, email, password_hash, role, is_active)
   VALUES ('Administrador', 'admin@seudominio.com', 'COLE_O_HASH_AQUI', 'super_admin', 1);
   ```

8. Acesse `http://localhost/SiteTools/admin/login.php`.

## Compatibilidade com a tabela `tools`

A listagem usa os campos já descritos para o SiteTools:

`id, name, slug, description, category, icon, handler, meta_title, meta_description, is_active`

O código não cria nem altera a tabela `tools`. A página de ferramentas é inicialmente de consulta; o CRUD completo será uma próxima etapa, depois de validar o esquema real e os handlers atuais.

## Configuração importante

- Ajuste `BASE_URL` em `config/app.php` se a pasta ou domínio for diferente.
- Em produção, use HTTPS e configure `APP_ENV` como `production`.
- Configure permissões de escrita em `storage/logs`, `storage/temp`, `storage/backups` e `uploads`.
- Não deixe `display_errors` habilitado em produção.
- Não exponha `config`, `database` e `storage` pela web. Os arquivos `.htaccess` incluídos ajudam em Apache, mas também configure o servidor.
- Este starter não implementa ainda coleta automática de pageviews, processamento de uploads, AdSense, backups agendados ou gestão completa de usuários. As telas correspondentes são pontos de extensão, não funcionalidades já conectadas.

## Estrutura

- `admin/`: painel administrativo.
- `app/`: classes reutilizáveis.
- `config/`: configuração da aplicação e conexão.
- `database/`: SQL de apoio.
- `handlers/`: reservado aos handlers públicos das ferramentas.
- `storage/`: dados privados gerados pelo sistema.
- `uploads/`: arquivos enviados.
- `assets/` e `templates/`: recursos do site público; preservados como pastas de integração.

## Segurança e observações

O primeiro administrador deve ser criado manualmente no banco usando o hash acima. Não existe senha padrão embutida no projeto. Antes de publicar, revise permissões, HTTPS, regras do Apache e política de retenção de arquivos.
