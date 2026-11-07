# API e contratos JSON — SiteTools

A maior parte do produto é server-rendered (PHP + templates). Ainda assim, vários fluxos respondem **JSON**, especialmente auth, CSRF failures, negação de acesso e alguns endpoints em `api/` e `account/api/`.

## Formato geral de erro

Padrão frequente:

```json
{
  "error": "Mensagem legível para o usuário"
}
```

Serviços da camada `App\` podem enriquecer com código estável:

```json
{
  "error": "Limite de uso diário atingido para esta ferramenta.",
  "code": "usage_limit_exceeded",
  "context": {
    "used": 10,
    "max": 10,
    "remaining": 0,
    "period": "daily"
  }
}
```

Headers típicos em respostas de auth/CSRF:

- `Content-Type: application/json; charset=utf-8`
- `X-Content-Type-Options: nosniff`

## CSRF em requests JSON

Inclua o token em um dos canais:

1. Header `X-CSRF-Token: <token>`
2. Campo JSON `"_csrf": "<token>"`
3. Campo de formulário `_csrf` (POST clássico)

Token obtido da sessão (meta `csrf-token` ou campo hidden). Falha → **403** com mensagem de token inválido/ausente.

## Autenticação

Funções em `config/auth.php` (usadas por formulários e endpoints de login/cadastro/recuperação):

| Situação | HTTP (típico) |
|----------|----------------|
| Sucesso login | 200 + sessão |
| Credenciais inválidas | 401/403 conforme implementação do endpoint |
| Conta bloqueada por tentativas | 429 / mensagem de lockout |
| Sessão expirada / idle | tratado como não autenticado |

Sessão: cookie `SITETOOLSSESSID` (nome configurável no start de sessão).

Admin usa cookie/sessão **separada** (`ADMIN_SESSION_NAME`).

## Autorização de ferramentas

Antes de processar, o backend chama `tool_access_check` / `ToolAccessService`.

Negação JSON (`tool_access_deny_json`) — exemplos de motivos:

| reason | HTTP | Significado |
|--------|------|-------------|
| `login_required` | 401 | Precisa autenticar |
| `payment_required` | 402 | Precisa grant/pago |
| `unavailable` / `development` | 503 | Ferramenta fora de operação |
| outros | 403 | Acesso negado |

## Limites de uso

`UsageLimitService::assertWithinLimit`:

- Excesso → exceção de aplicação com status **429** e código `usage_limit_exceeded`.

## Upload

Validação via `upload_validate` / `FileStorageService::validateUpload`.

Falhas comuns (mensagem em `error` ou `ValidationException`):

- Nenhum arquivo / upload incompleto
- Tamanho acima do limite
- Extensão não permitida ou perigosa
- Conteúdo não confere com a extensão

## Endpoints auxiliares (`api/`)

| Arquivo | Papel |
|---------|--------|
| `api/save-tool-usage.php` | Persistência de uso |
| `api/function-admin.php` | Operações administrativas |
| `api/function-payments.php` | Pagamentos (em evolução no repositório) |

Área logada: `account/api/` (ex.: favoritos).

> Contratos exatos de request/response de cada endpoint devem ser confirmados no arquivo PHP correspondente ao evoluir a API. Este documento descreve os padrões transversais já estabelecidos no código.

## Processamento de documentos

Fluxo típico:

1. CSRF + auth/access + limite
2. Upload validado
3. Workspace temporário (`TempWorkspace` / `storage/temp`)
4. `ProcessRunner` / conversor (LibreOffice, GS, etc.) com timeout (`PROCESS_TIMEOUT`)
5. Disponibilização do resultado + limpeza

Jobs longos podem passar por `tool_processing_jobs` e `queue-worker.php`.

## Versionamento

Não há ainda versionamento público tipo `/api/v1`. Ao expor API estável para terceiros, introduzir prefixo de versão e changelog.
