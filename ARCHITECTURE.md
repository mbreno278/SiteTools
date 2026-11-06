# SiteTools — Arquitetura (Etapa 2)

## Princípio

Evolução **incremental**. O código legado em `config/`, `tools/` e `page.php` continua funcionando.
A pasta `app/` passa a concentrar novas regras e, aos poucos, lógica extraída dos scripts procedurais.

## Estrutura

```
app/
  bootstrap.php          # autoload + config legado + ErrorHandler
  Controllers/           # HTTP fino
  Services/              # regras de negócio
  Repositories/          # SQL centralizado (PDO)
  Middleware/            # CSRF, acesso, quotas
  Exceptions/            # AppException, Validation, Authorization
  Helpers/               # Response, Logger, Autoloader, ErrorHandler
tools/                   # endpoints HTTP legados (migração gradual)
config/                  # database, auth, csrf, tool-access, upload-security
storage/logs/            # logs da aplicação
```

## Fluxo alvo de um endpoint de ferramenta

```
Request
  → CsrfMiddleware
  → ToolAccessMiddleware (auth + paid + status)
  → UsageLimitService.assertWithinLimit
  → Controller coordena
  → Service (conversão / processamento)
  → Response::success | Response::error
```

## Resposta JSON padrão

Sucesso:
```json
{ "success": true, "message": "OK", "data": { } }
```

Erro:
```json
{ "success": false, "message": "…", "data": null, "code": "validation_error" }
```

Stack traces e caminhos internos **não** vão para o cliente (apenas em `APP_ENV=local` mensagem bruta opcional).

## Como migrar um handler legado

1. Manter `tools/minha-ferramenta.php` como entrypoint.
2. No topo:
   ```php
   require_once __DIR__ . '/../app/bootstrap.php';
   ```
3. Trocar o bloco inicial CSRF/acesso por:
   ```php
   (new \App\Controllers\ToolProcessController())->run('slug', function(array $ctx) {
       // lógica de processamento
   });
   ```
4. Extrair SQL repetido para um Repository.
5. Extrair regra de negócio para um Service.

Não é necessário migrar todos os handlers de uma vez.

## Serviços iniciais

| Serviço | Responsabilidade |
|---|---|
| `ToolAccessService` | free/login/paid/dev/unavailable |
| `UsageLimitService` | cotas guest/registered/paid |
| `FileStorageService` | validação e storage seguro |
| `UserService` | perfil / senha |

## Compatibilidade

- Sem React/Vue/Angular
- Sem obrigatoriedade de Composer (autoload próprio)
- PDO e sessões legadas preservados
- WAMP/Windows
