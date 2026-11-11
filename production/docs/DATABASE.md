# Banco de dados — SiteTools

## Visão geral

- SGBD: MySQL 8.x
- Charset: `utf8mb4`
- Schema de referência: `config/webtools__sitetools.sql`
- Nome padrão do banco: `webtools__sitetools`

A aplicação **não** usa o usuário MySQL `root`. Em produção, `DB_HOST`, `DB_NAME`, `DB_USER` e `DB_PASSWORD` são obrigatórios (via ambiente ou `.env`).

## Tabelas principais

### Usuários e sessão

| Tabela | Função |
|--------|--------|
| `users` | Contas de usuário (email, password_hash, verificação, reset) |
| `user_sessions` | Sessões registradas (ip, user_agent, expires_at) |
| `login_attempts` | Rate limit de login (email/IP) |
| `user_activity_logs` | Auditoria de ações do usuário |

### Ferramentas e acesso

| Tabela | Função |
|--------|--------|
| `tools` | Catálogo (slug, status, access_type, flags) |
| `categories` | Categorias das ferramentas |
| `tool_access_rules` | Regras comerciais (access_mode, guest_allowed) |
| `user_tool_access` | Grants individuais (pago / temporário), com validade |
| `tool_usage_limits` | Cotas por `user_type` (guest/registered/paid) e período |
| `tool_usage_counters` | Contadores por subject (guest key ou user_id) |
| `tool_usage` / `user_tool_usage` | Histórico de uso |
| `tool_processing_jobs` | Jobs de processamento assíncrono |
| `user_favorites` | Favoritos do usuário |

### Arquivos e pagamentos

| Tabela | Função |
|--------|--------|
| `user_files` / `file_uploads` | Metadados de arquivos do usuário |
| `user_payments` | Registro de pagamentos |
| `payment_webhook_logs` | Logs de webhooks |

### Admin e sistema

| Tabela | Função |
|--------|--------|
| `admin_users` | Operadores do painel |
| `admin_activity_logs` | Auditoria admin |
| `site_settings` | Configurações |
| `error_logs` | Erros persistidos |
| `advertisements` | Blocos de anúncio |
| `support_messages` | Mensagens de suporte |
| `backups` | Metadados de backup |
| `user_notifications` | Notificações |

## Relacionamentos relevantes

```
users 1──* user_sessions
users 1──* user_tool_access *──1 tools
users 1──* user_favorites *──1 tools
users 1──* user_payments
tools 1──* tool_access_rules
tools 1──* tool_usage_limits
tools 1──* tool_usage_counters
tools 1──* tool_processing_jobs
```

### Modos comerciais (`access_type` / `access_mode`)

- `free` — uso livre (respeitando limites e `guest_allowed`)
- `login` — exige usuário autenticado
- `paid` — exige grant em `user_tool_access`
- `temporary` — grant com data de expiração

### Status operacional (`tools.status`)

- `active`
- `development`
- `unavailable`

## Migrações

Hoje o schema canônico é o dump SQL:

```bash
mysql -u ... -p webtools__sitetools < config/webtools__sitetools.sql
```

Recomendações:

1. Em novos ambientes, preferir schema limpo + seeds mínimos (admin, categorias).
2. Evoluções futuras: pasta `database/migrations/` com arquivos versionados `.sql` e um runner simples ou ferramenta externa.
3. Nunca versionar dumps com dados sensíveis de produção.

## Usuário da aplicação

Crie um usuário dedicado. Privilégios mínimos dependem das features ativas; em operação completa são necessários DML nas tabelas de usuários, sessões, grants, contadores, arquivos e pagamentos — não apenas `SELECT` em `tools`.

Exemplo (ajuste conforme política de segurança):

```sql
CREATE USER 'sitetools_app'@'localhost' IDENTIFIED BY 'senha-forte';
GRANT SELECT, INSERT, UPDATE, DELETE ON webtools__sitetools.* TO 'sitetools_app'@'localhost';
FLUSH PRIVILEGES;
```

Restrinja ainda mais (por tabela) se a política da empresa exigir.

## Índices e performance

O dump já define chaves úteis (`email`, pares tool/subject/period nos contadores, etc.). Ao adicionar queries novas, preferir índices que cubram filtros por `user_id`, `tool_id` e janelas de data.
