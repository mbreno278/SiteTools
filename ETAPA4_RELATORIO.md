# Etapa 4 — Área do usuário — Relatório

## Entrega

Pasta `account/` completa + `config/user-area.php` atualizado.

### Páginas
| Arquivo | Função |
|---|---|
| `index.php` | Dashboard com stats, atividade recente, acessos a vencer |
| `tools.php` | Catálogo com filtros (já usei / favoritas / free / login / paid) |
| `subscriptions.php` | **Meus acessos** por ferramenta (ativos / histórico / pagamentos) |
| `history.php` | Histórico paginado (`user_tool_usage`) |
| `files.php` | Lista / exclui arquivos (`user_files`) |
| `download.php` | Download autenticado + anti path traversal |
| `favorites.php` | Favoritos persistidos |
| `profile.php` | Editar nome |
| `security.php` | Alterar senha |
| `api/favorite.php` | Toggle favorito (CSRF + UNIQUE) |

### Login / logout
- Área exige `auth_user()`; sem sessão redireciona para `index.php?login=1`
- Logout via **POST + CSRF** (formulário no topo), compatível com Etapa 1

### Histórico (4.1)
- Lê `user_tool_usage` (ferramenta, data, status, tempo, tamanho)
- Helper `user_area_log_tool_usage()` para os handlers registrarem uso autenticado
- **Não** grava conteúdo de arquivos

### Arquivos (4.2)
- `user_files` + `storage/user-files/` (`.htaccess` nega acesso direto)
- Download só com login e `user_id` do dono
- Exclusão lógica + unlink físico seguro
- `tools/cleanup-expired-files.php` para expirados

### Favoritos (4.3)
- `INSERT IGNORE` / `DELETE` com UNIQUE `(user_id, tool_id)`
- API JSON com CSRF

### Acessos individuais (4.4)
- UI em `subscriptions.php` baseada em `user_tool_access` + `user_payments`
- Não há assinatura global
- Texto deixa claro: liberação só após confirmação server-side do pagamento

## Como aplicar
1. Backup
2. Copiar `account/` e `config/user-area.php` para a raiz do SiteTools
3. Garantir `storage/user-files/` gravável
4. (Opcional) agendar `php tools/cleanup-expired-files.php`

## Próximo passo técnico
Chamar `user_area_log_tool_usage()` e `user_area_save_file()` nos handlers de ferramentas autenticadas (Etapa de processamento).
