# Testes Automatizados — SiteTools (ETAPA 8)

## Objetivo

Cobertura automatizada das áreas críticas:

- Autenticação (tokens, sessão, hash)
- Autorização e controle de acesso a ferramentas
- Acesso temporário / expiração (via ToolAccessService + lógica legada)
- Limites de utilização (UsageLimitService)
- Upload / validação de arquivos (upload-security + FileStorageService)
- CSRF
- Rejeição de arquivos inválidos / acima do limite
- Tentativas de acesso a recursos inexistentes ou de outro contexto

## Requisitos

- PHP >= 8.2
- Extensões recomendadas: `mbstring`, `dom`, `xmlwriter`, `pdo_mysql` ou `pdo_sqlite` (para suite completa com PHPUnit)

### Instalação com Composer (recomendado)

```bash
composer install
composer test
# ou
./vendor/bin/phpunit
```

### Runner simples (sem PHPUnit / sem extensões XML)

Útil em ambientes mínimos:

```bash
php tests/run_simple.php
```

## Estrutura

```
tests/
├── bootstrap.php
├── run_simple.php
├── README.md
├── Support/
│   └── SimpleTestCase.php
├── Unit/
│   ├── AuthTokenTest.php
│   ├── CsrfTest.php
│   ├── UploadSecurityTest.php
│   ├── ToolAccessNormalizeTest.php
│   ├── ToolAccessServiceTest.php
│   ├── UsageLimitServiceTest.php
│   └── FileStorageServiceTest.php
└── Integration/          # reservado para testes com banco real
```

## O que é testado

| Área                    | Tipo     | Observação                                      |
|-------------------------|----------|-------------------------------------------------|
| Upload inválido / limite| Unit     | `upload_validate`, FileStorageService           |
| Extensões perigosas     | Unit     | php, phtml, exe, etc. bloqueados                |
| CSRF token / field      | Unit     | geração e comparação hash_equals                |
| Normalização access mode| Unit     | free / login / paid / temporary                 |
| Tokens auth             | Unit     | random 64 hex + sha256                          |
| Limites diários         | Unit     | mock de UsageRepository (guest/user/paid)       |
| Tool não encontrada     | Unit     | AuthorizationException                          |
| Path traversal upload   | Unit     | `upload_assert_safe_path`                       |

## Testes ainda manuais / integração (requerem DB + servidor)

- Login/logout completo com banco
- Grants temporários e expiração (`tool_user_grants`)
- Favoritos
- Download de arquivo de outro usuário
- Pagamentos (webhooks)
- Requisições simultâneas (race em contadores)
- Processamento real de documentos (LibreOffice/Ghostscript)

Para esses casos, utilize ambiente local com `.env` de teste e um banco `webtools__sitetools_test`.

## Política

Não declarar funcionalidade como concluída sem executar os testes aplicáveis desta pasta.
