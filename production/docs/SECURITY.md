# Segurança — SiteTools

## Princípios

- Segurança por padrão (fail-closed em grants e tokens)
- Validação sempre no servidor
- Consultas preparadas (PDO)
- Sessões com regeneração de ID no login
- CSRF em mutações
- Upload com checagem de extensão, MIME e assinatura

## Autenticação

Implementação principal: `config/auth.php`.

| Controle | Valor padrão (constantes) |
|----------|---------------------------|
| Duração máxima da sessão | 2 h (`AUTH_SESSION_LIFETIME`) |
| Inatividade máxima | 30 min (`AUTH_SESSION_IDLE`) |
| Tentativas de login | 5 (`AUTH_MAX_ATTEMPTS`) |
| Janela de bloqueio | 15 min (`AUTH_LOCKOUT_WINDOW`) |
| TTL token verify/reset | 1 h (`AUTH_TOKEN_TTL`) |

Fluxo resumido:

1. Credenciais validadas; tentativas registradas em `login_attempts`.
2. Em sucesso: `session_regenerate_id(true)`, dados em `$_SESSION`, registro em `user_sessions`.
3. `auth_session_valid()` verifica idle e lifetime.
4. Tokens de e-mail/reset são aleatórios (64 hex) e armazenados como hash SHA-256.

Sessão de admin é **separada** (`ADMIN_SESSION_NAME`), com tabela `admin_users`.

## CSRF

`config/csrf.php`:

- Token por sessão (`bin2hex(random_bytes(32))`)
- Envio via campo `_csrf`, header `X-CSRF-Token` ou JSON `_csrf`
- Comparação com `hash_equals`
- Resposta 403 JSON se inválido

Todo POST de ferramenta/API mutável deve chamar `csrf_verify()` (ou middleware equivalente).

## Autorização de ferramentas

`config/tool-access.php` + `App\Services\ToolAccessService`:

1. Resolve **status** operacional (`active` / `development` / `unavailable`).
2. Resolve **modo** comercial (`free` / `login` / `paid` / `temporary`), com override opcional em `tool_access_rules`.
3. Para `login` / `paid` / `temporary`: exige usuário e, quando aplicável, grant ativo em `user_tool_access`.
4. Erros de consulta de grant → **sem acesso** (fail-closed).

Códigos típicos de negação JSON: 401/403/402/503 conforme o motivo.

## Limites de utilização

`UsageLimitService` + tabelas `tool_usage_limits` / `tool_usage_counters`:

- Tipos: `guest`, `registered`, `paid`
- Período padrão: diário
- Guest identificado por chave derivada (IP/UA) no serviço

Exceder limite → HTTP 429 / `usage_limit_exceeded`.

## Upload e arquivos

`config/upload-security.php` + `FileStorageService`:

- Não confiar em `$_FILES['type']` isoladamente
- Limite de tamanho configurável (`UPLOAD_MAX_BYTES` / opts)
- Bloqueio de extensões perigosas (php, phtml, exe, sh, html, …)
- MIME via `finfo` + mapa por extensão
- Verificação de assinatura de arquivo quando necessário
- `upload_assert_safe_path` contra path traversal
- Armazenamento preferencial sob `storage/` (não exposto como código executável)

## Isolamento entre usuários

Downloads e listagens da área logada devem filtrar por `user_id` do dono do recurso. Tentativas de acessar arquivo/grant de outro usuário devem falhar com 403/404.

## Headers e configuração

- `X-Content-Type-Options: nosniff` em respostas JSON de auth/CSRF
- Cookies de sessão: `HttpOnly`, `SameSite=Lax`, `Secure` quando HTTPS
- `APP_DEBUG=false` em produção (não vazar stack traces)
- Segredos apenas em ambiente / `.env` (nunca commitados)

## Pagamentos

Registros em `user_payments` e logs em `payment_webhook_logs`. Webhooks devem validar assinatura do provedor e ser idempotentes. O endpoint `api/function-payments.php` no repositório pode estar em evolução — trate pagamentos como superfície crítica de segurança.

## Checklist rápido de review

- [ ] POST protegido com CSRF
- [ ] Acesso a tool checado antes do processamento
- [ ] Upload validado (tamanho, extensão, conteúdo)
- [ ] Query com prepared statement
- [ ] Recurso filtrado pelo usuário autenticado
- [ ] Erros não expõem detalhes internos em produção
