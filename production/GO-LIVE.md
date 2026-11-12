# SiteTools — checklist para ir ao ar

Pacote pronto: `sitetools-production.zip`

## O que foi ajustado neste pacote

- `APP_ENV=production` e `APP_DEBUG=false` no `.env`
- Removidos defaults hardcoded de desenvolvimento (`/projetos/SiteTools/local`)
- URL do cropper de imagem deixa de apontar para localhost
- `index.php`, `page.php` e `tools.php` passam a carregar `config/app.php` e usam `BASE_URL` do ambiente
- Storage/logs/temp limpos (sem arquivos de sessão de desenvolvimento)
- `robots.txt` sem domínio de exemplo fixo
- Feature flags em modo produção (`config/modules.php`)

## Passos no servidor

1. **Upload** do conteúdo do zip para o document root (ex.: `public_html/`).
2. **Editar `.env`**:
   - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`
   - `MAIL_FROM` com e-mail real do domínio
   - `BASE_URL=` (vazio se o site estiver na raiz)
   - Gerar um `CLEANUP_TOKEN` forte se for usar limpeza via HTTP
3. **Importar** `database/webtools__sitetools.sql` no MySQL.
4. **Trocar senhas** do admin e usuários importados (hash do dump é de ambiente de dev):
   - Admin: `mbreno278@gmail.com` (role super_admin)
5. **Permissões**:
   ```bash
   chmod 640 .env
   chmod -R 775 storage uploads
   chown -R www-data:www-data storage uploads .env   # ajuste o user do PHP-FPM
   ```
6. **HTTPS** + redirecionar HTTP → HTTPS.
7. **Binários** (conforme ferramentas usadas): `gs`, `qpdf`, `yt-dlp`, `ffmpeg`, LibreOffice.
8. **Cron** (recomendado):
   ```cron
   */15 * * * * www-data php /caminho/do/projeto/tools/cleanup-expired-files.php
   */5  * * * * www-data php /caminho/do/projeto/tools/queue-worker.php
   ```

## Validação rápida

- [ ] Home carrega (sem 500)
- [ ] `/tools.php` lista ferramentas
- [ ] Ferramenta gratuita funciona sem login
- [ ] Login / cadastro
- [ ] Admin só com credencial nova
- [ ] Upload dentro do limite
- [ ] Headers de segurança presentes
- [ ] Nenhum path/SQL vazando na tela

Detalhes completos: `DEPLOY.md` e `docs/DEPLOYMENT.md`.
