# Desenvolvimento — SiteTools

## Ambiente local (WAMP)

### 1. Código

```text
C:\wamp64\www\projetos\SiteTools
```

URL base exemplo: `http://localhost/projetos/SiteTools/`

Defina no `.env`:

```env
APP_ENV=local
APP_DEBUG=true
BASE_URL=projetos/SiteTools
```

### 2. Banco

1. Crie o database `webtools__sitetools`.
2. Importe `config/webtools__sitetools.sql`.
3. Configure `DB_*` no `.env` com usuário **não-root**.

Em `APP_ENV=local`, `config/database.php` pode aplicar fallbacks de desenvolvimento se variáveis faltarem — em produção isso **não** ocorre.

### 3. Extensões PHP

Ative no WAMP: `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `gd` ou `imagick` (se usar processamento de imagem).

### 4. Binários opcionais

Para conversões reais, instale e (se necessário) aponte no `.env`:

- `LIBREOFFICE_PATH`
- `GHOSTSCRIPT_PATH`
- `QPDF_PATH`
- `YTDLP_PATH`

### 5. Permissões

Garanta escrita em `storage/**` e `uploads/`.

## Variáveis de ambiente

Ver `.env.example`. Principais:

| Variável | Uso |
|----------|-----|
| `APP_ENV` | `local` / `production` / … |
| `APP_DEBUG` | Exibir erros detalhados |
| `BASE_URL` | Subpath do app |
| `DB_*` | Conexão MySQL |
| `MAIL_FROM` | Remetente de e-mails |
| `UPLOAD_MAX_BYTES` | Teto de upload |
| `PROCESS_TIMEOUT` | Timeout de processos |
| `CLEANUP_TOKEN` | Token para limpeza via HTTP |

## Estrutura de código

- Novo domínio de negócio → `app/Services` + `app/Repositories`
- Funções transversais estáveis → `config/`
- Página de ferramenta → `content/` + script em `tools/`
- Área logada → `account/`
- Admin → `admin/`

Carregue a camada App quando necessário:

```php
require_once __DIR__ . '/../app/bootstrap.php';
```

## Testes

### PHPUnit (recomendado)

```bash
composer install
composer test
# ou
./vendor/bin/phpunit --testsuite Unit
```

Arquivos: `phpunit.xml.dist`, `tests/bootstrap.php`, `tests/Unit/`.

### Runner simples

Sem depender de extensões XML do PHPUnit:

```bash
php tests/run_simple.php
```

Cobertura unitária atual inclui: CSRF (contrato), upload/segurança, normalização de acesso, tokens auth, limites de uso, FileStorageService e contratos de autorização.

Testes de integração (login real, grants expirados, concorrência de contadores, ownership de arquivos) exigem banco de teste e devem ir em `tests/Integration/`.

**Política:** não declarar feature concluída sem executar os testes aplicáveis.

## Como adicionar uma ferramenta

1. Registrar em `tools` (slug, status, access_type).
2. Criar página em `content/` e processador em `tools/` (ou controller App).
3. Reutilizar `tool_access_require` / `ToolAccessService` e validação de upload.
4. Definir limites em `tool_usage_limits` se necessário.
5. Adicionar testes unitários para regras puras e, se possível, um caso de integração.

## Logs e debug

- `storage/logs/`
- `ErrorHandler` em `app/Helpers`
- Em local, e-mails de verificação/reset podem ser gravados em arquivo (ver `auth_send_mail`)

## Convenções

- `declare(strict_types=1);`
- Prepared statements
- Mensagens de erro seguras para o cliente; detalhe só em log
- Evitar abstrações sem problema real a resolver
