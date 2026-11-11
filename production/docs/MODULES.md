# Sistema de módulos (feature flags)

Arquivo: `config/modules.php`  
Helpers: `config/modules-helper.php`

## Flags

| Módulo | Efeito quando `false` |
|--------|------------------------|
| `admin` | `/admin` responde 503 |
| `account` | `/account` responde 503; link some do header |
| `authentication` | Some login/cadastro do header; ferramentas `login` liberam como free |
| `payments` | Ferramentas pagas sem grant não abrem fluxo de compra (reason ajustado) |
| `premium` | `paid`/`temporary` liberam sem grant |
| `video_downloads` | Some do catálogo, `page.php` e endpoints `/tools/download-*` |

## Beta

| Flag | Efeito |
|------|--------|
| `beta.enabled` | Liga modo beta |
| `beta.unlimited_usage` | (com beta on) libera paid/temporary e ignora monetização |

## Helpers

```php
module_enabled('video_downloads');
beta_enabled();
beta_unlimited_usage();
module_allows_handler('download-video-youtube');
```

## Exemplo produção

```php
'modules' => [
    'admin' => true,
    'account' => true,
    'authentication' => true,
    'payments' => true,
    'premium' => true,
    'video_downloads' => true,
],
'beta' => [
    'enabled' => false,
    'unlimited_usage' => false,
],
```
