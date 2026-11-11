# Deploy e produção — SiteTools

## Checklist antes de publicar

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `DB_*` definidos no ambiente (não commitados)
- [ ] Usuário MySQL dedicado (sem `root`)
- [ ] HTTPS obrigatório; cookies `Secure`
- [ ] `BASE_URL` / `APP_URL` corretos
- [ ] Permissões de escrita só em `storage/` e pastas de upload necessárias
- [ ] Document root apontando para a raiz do projeto (ou ajuste de alias)
- [ ] `mod_rewrite` / regras equivalentes ativas (ver `.htaccess`)
- [ ] Binários de conversão instalados no servidor, se usados
- [ ] Cron de limpeza configurado
- [ ] Backup de banco e de `storage/user-files` agendado

## Configuração de ambiente

Prefira variáveis do sistema (Apache `SetEnv`, systemd, painel de hospedagem) ou arquivo `.env` **fora** de versionamento.

Nunca deixe `APP_DEBUG=true` em produção.

## Servidor web

### Apache

- `AllowOverride` habilitado para o `.htaccess` do projeto
- PHP via mod_php ou PHP-FPM
- Negar acesso HTTP direto a `config/`, `app/`, `storage/logs`, `.env`

### Nginx (notas)

- `root` na pasta do projeto
- `try_files` para front controller / páginas PHP existentes
- `location` bloqueando paths sensíveis
- PHP via `fastcgi_pass` para o pool FPM

## Permissões de diretório

```text
storage/logs        → escrita pelo usuário do PHP
storage/temp
storage/queue
storage/user-files
storage/backups
uploads             → conforme política (preferir não executar scripts)
```

Arquivos de código e `config/` → somente leitura para o usuário web.

## Rotinas de limpeza

Scripts existentes (ex.: `tools/cleanup.php`, `tools/cleanup-expired-files.php`, `tools/queue-worker.php`):

- Preferir execução via **CLI + cron**
- Se expostos via HTTP, exigir `CLEANUP_TOKEN` forte e rede restrita

Exemplo cron (Linux):

```cron
*/15 * * * * www-data php /var/www/sitetools/tools/cleanup-expired-files.php
*/5  * * * * www-data php /var/www/sitetools/tools/queue-worker.php
```

## Backup

1. Dump MySQL diário (`mysqldump` ou ferramenta gerenciada).
2. Cópia de `storage/user-files` e, se necessário, `uploads`.
3. Testar restore periodicamente.
4. Metadados podem ser registrados na tabela `backups`.

## Escala e crescimento

- Contadores de uso: atenção a race conditions sob carga (transações / `ON DUPLICATE KEY UPDATE` já usados)
- Processamento pesado: fila (`storage/queue`, `SimpleJobQueue`, `tool_processing_jobs`) e worker
- Separar storage de arquivos do disco da aplicação quando o volume crescer (S3-compatível no futuro)
- Monitorar `error_logs` e logs de aplicação

## Rollback

- Manter releases versionadas (git tag + artefato)
- Schema: migrações reversíveis ou backup pré-deploy
- Configuração: documentar diff de variáveis entre versões
