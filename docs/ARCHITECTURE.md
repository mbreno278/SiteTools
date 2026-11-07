# Arquitetura — SiteTools

## Visão geral

O SiteTools combina:

1. **Camada legada procedural** em `config/`, `tools/`, `content/` e páginas PHP na raiz — responsável por grande parte do runtime atual.
2. **Camada orientada a serviços** em `app/` (PSR-4 `App\`) — Services, Repositories, Middleware, Exceptions e Helpers, usada progressivamente pelos endpoints.

O bootstrap da camada nova está em `app/bootstrap.php` e pode ser incluído por endpoints legados.

## Estrutura de pastas

```
SiteTools/
├── account/           # Área do usuário autenticado
├── admin/             # Painel administrativo (sessão própria)
├── api/               # Endpoints JSON auxiliares
├── app/               # Camada App\ (Services, Repos, Middleware…)
├── assets/            # CSS/JS/imagens estáticos
├── config/            # Auth, CSRF, DB, tool-access, upload-security…
├── content/           # Conteúdo/páginas de ferramentas
├── handlers/          # Handlers auxiliares
├── includes/          # Includes compartilhados de front
├── storage/           # logs, temp, queue, user-files, backups
├── templates/         # Layout (header, footer, tool-page…)
├── tools/             # Scripts de processamento das ferramentas
├── uploads/           # Uploads públicos/temporários (quando aplicável)
├── tests/             # Testes automatizados
├── index.php          # Home
├── page.php           # Roteamento de páginas/ferramentas
└── .env.example
```

### `app/`

| Pasta | Papel |
|-------|--------|
| `Controllers/` | Orquestração de requests (ex.: `ToolProcessController`) |
| `Services/` | Regras de negócio (acesso, limites, arquivos, conversão) |
| `Repositories/` | Acesso a dados via PDO |
| `Middleware/` | CSRF, tool access |
| `Exceptions/` | `AppException`, `AuthorizationException`, `ValidationException` |
| `Helpers/` | Autoloader, Response, Logger, ErrorHandler |
| `Services/Process/` | Workspace temporário, runner de processos, fila simples |

### `config/`

Funções e constantes compartilhadas:

- `auth.php` — sessão, login, lockout, tokens
- `csrf.php` — token e verificação
- `tool-access.php` — modos free/login/paid/temporary + grants
- `upload-security.php` — validação de upload e path seguro
- `database.php` / `env.php` — conexão e ambiente
- `user-area.php`, `functions.php`, `converters.php`

## Fluxo de uma requisição típica de ferramenta

```
Browser
  → page.php / tools/*.php
    → config (auth, csrf, tool-access, upload-security)
    → (opcional) app/bootstrap.php
    → ToolAccessService / tool_access_check
    → UsageLimitService (cotas)
    → FileStorageService / upload_validate
    → DocumentConverterService / ImageProcessingService / ProcessRunner
    → resposta HTML ou JSON
```

## Separação de responsabilidades

- **Autenticação**: `config/auth.php` + sessão PHP + tabela `user_sessions`
- **Autorização de ferramenta**: `tool_access_check` / `ToolAccessService` (status operacional + modo comercial + grant)
- **Limites**: `UsageLimitService` + `tool_usage_limits` / `tool_usage_counters`
- **Arquivos**: `upload-security.php` + `FileStorageService` + `storage/user-files`
- **Admin**: sessão e tabelas distintas (`admin_users`, `ADMIN_SESSION_NAME`)

## Princípios adotados

- Validação no servidor (nunca confiar só no cliente)
- Consultas preparadas (PDO)
- Fail-closed em grants e acesso pago
- Exceções de aplicação com código HTTP e `errorCode` estáveis
- Pouca abstração: services finos sobre o legado, sem over-engineering

## Evolução

Novos fluxos devem preferir `App\Services` + Repositories. Endpoints legados podem continuar chamando funções de `config/` até migração gradual.
