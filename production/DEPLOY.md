# Deploy SiteTools — produção (ao ar)

## 1. Pré-requisitos do servidor

- PHP **8.2+** (recomendado 8.3) com extensões: `pdo_mysql`, `mbstring`, `json`, `fileinfo`, `gd` ou `imagick`, `curl`, `zip`
- MySQL 8 / MariaDB 10.6+
- Apache com `mod_rewrite` + `AllowOverride` **ou** Nginx + PHP-FPM
- Binários opcionais conforme ferramentas:
  - Ghostscript (`gs`) — compactar PDF
  - qpdf — proteger/desbloquear PDF
  - yt-dlp + ffmpeg — downloads de vídeo/áudio
  - LibreOffice — conversão Word/PDF (se usar)

## 2. Upload dos arquivos

Envie o conteúdo de `production/` para o **document root** do domínio  
(ex.: `public_html/` ou `/var/www/webtools/`).

**Não** versionar nem publicar:

- `.env` (só `.env.example`)
- `storage/logs/*`, `storage/temp/*`, dumps `.sql`

## 3. Ambiente

```bash
cp .env.example .env
# edite .env com DB real, BASE_URL, MAIL_FROM
```

Confirme:

```
APP_ENV=production
APP_DEBUG=false
```

Permissões (Linux):

```bash
chmod 640 .env
chown www-data:www-data .env   # ajuste o usuário do PHP-FPM
chmod -R 775 storage uploads
chown -R www-data:www-data storage uploads
```

## 4. Banco de dados

1. Importe o schema (dump atualizado do phpMyAdmin / migrations).
2. Usuário MySQL **sem** ser `root`, só com privilégios necessários.
3. Alinhe `tools.access_type` e `tool_access_rules.access_mode` (mesmo valor).
4. Confira grants em `user_tool_access` se houver ferramentas pagas.

## 5. Feature flags

Arquivo: `config/modules.php`  
Em produção padrão: **todos `true`**, `beta.enabled = false`.

Para desligar downloads de vídeo sem redeploy de código:

```php
'video_downloads' => false,
```

## 6. HTTPS e domínio

- Certificado SSL válido (Let's Encrypt / painel)
- `BASE_URL` com `https://`
- Redirecionar HTTP → HTTPS no painel ou vhost

## 7. Checklist pós-deploy

- [ ] Home carrega sem erro 500
- [ ] `tools.php` lista ferramentas
- [ ] Ferramenta gratuita funciona sem login
- [ ] Login / cadastro (se authentication on)
- [ ] Admin acessível só com credencial forte
- [ ] Upload PDF/imagem dentro do limite
- [ ] Tema dark/light e modal de login OK
- [ ] Headers de segurança presentes (DevTools → Network)
- [ ] `display_errors` off (não vaza path/SQL na tela)
- [ ] Cron de limpeza (opcional): `php tools/cleanup-expired-files.php`

## 8. Segurança urgente

Se `.env.example` ou o repositório já expuseram senha de banco:

1. **Troque a senha** no MySQL agora
2. Atualize `.env` no servidor
3. Revogue usuários antigos se necessário

## 9. OPcache / performance

- OPcache ligado em produção
- Após deploy: reinicie PHP-FPM ou `opcache_reset`
