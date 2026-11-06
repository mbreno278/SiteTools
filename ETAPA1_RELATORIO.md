# Etapa 1 — Segurança — Relatório de entrega

## Sub-etapas concluídas

### 1A — Fail-closed
- `tools/_tool_access_guard.php` — reescrito: DB offline / erro → **503/403**, nunca libera
- `content/tool-access-gate.php` — catch e ausência de PDO → **negado**
- `page.php` — catch de autorização → **negado** (removido fail-open)
- `config/tool-access.php` — grant com fail-closed; slug não registrado em `tool_access_require` → nega

### 1B — Guards nos endpoints
Inserido em todos os handlers de processamento:
```
require __DIR__ . '/_csrf_guard.php';
require __DIR__ . '/_tool_access_guard.php';
tool_access_guard_for('<slug>');
```
Arquivos: compactar-pdf, desbloquear-pdf, proteger-pdf, separar-pdf, unir-pdf, word-to-pdf, pdf-to-word, download-video-youtube, download-audio-youtube, download-video-instagram, download-video-tiktok.

### 1C — CSRF
- `_csrf_guard.php` cobre POST/PUT/PATCH/DELETE
- `tools/logout.php` — apenas POST + `csrf_verify()` obrigatório

### 1D — Autenticação / tokens
- `config/auth.php` — `auth_hash_token()`, logs de e-mail só em `APP_ENV=local` com token mascarado, `last_login_at` no login
- `tools/cadastro.php` / `recuperar-senha.php` — gravam **hash** do token
- `tools/redefinir-senha.php` / `verificar-email.php` — comparam **hash**

**Importante:** tokens emitidos *antes* deste deploy (texto puro) deixam de funcionar. Usuários devem solicitar novo link de verificação/recuperação.

### 1E — Admin
- `admin/includes/permissions.php` — mapa expandido + resposta JSON em API
- `admin/logout.php` — POST + CSRF
- `admin/pages/_require_map.php` — guia de `require_permission` por página

**Ação manual recomendada:** no topo de cada `admin/pages/*.php` e nas APIs de escrita, chamar `require_permission('...')` conforme o mapa.

### 1F — Uploads
- Novo `config/upload-security.php` com validação de MIME/assinatura, extensões perigosas, storage privado e anti path traversal
- Integração completa nos handlers pode ser feita na Etapa 6; a API já está disponível para uso imediato

### 1G — Hardening de superfície
- `.htaccess` raiz (headers + bloqueio `.sql/.env/.log`)
- `config/.htaccess`, `storage/.htaccess`, `uploads/.htaccess`
- `.gitignore` ampliado

## Como aplicar no WAMP

1. Backup do projeto atual
2. Copiar os arquivos deste pacote sobre a árvore do SiteTools (mesmos caminhos relativos)
3. Garantir que `storage/logs` exista e seja gravável
4. Testar:
   - ferramenta free sem login
   - ferramenta login sem sessão → bloqueio
   - ferramenta paid sem grant → bloqueio
   - POST sem CSRF → 403
   - logout GET → 405
   - recuperação de senha (novo fluxo com hash)

## Não incluído (próximas etapas)
- Enforcement de `tool_usage_limits` em todos os handlers
- Migração de tokens antigos
- Adoção completa de `upload_validate` em cada converter
- `require_permission` já injetado em cada página admin (mapa fornecido)
