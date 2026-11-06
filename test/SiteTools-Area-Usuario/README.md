# SiteTools — Área exclusiva do usuário

Módulo complementar. Não cria nem modifica `users`, login, cadastro, recuperação de senha ou autenticação.

## Compatibilidade identificada no repositório
- `config/auth.php`: disponibiliza `auth_user()` e `auth_check()`.
- `config/csrf.php`: disponibiliza `csrf_verify()` e `csrf_field()`.
- `config/database.php`: cria `$pdo` (PDO).
- `tools`: tabela existente com `id`, `name`, `slug`, `description`, `category`, `icon`, `handler`, `is_active`.
- `page.php`: carrega as ferramentas dinamicamente.

## Instalação
1. Faça backup do banco.
2. Execute `database/user-area-schema.sql` no banco do SiteTools.
   - O script NÃO cria `users`.
   - `user_id` é armazenado sem FK para evitar assumir o tipo exato da PK da tabela `users` existente.
3. Copie `account/` para a raiz do SiteTools.
4. Copie `config/user-area.php` e `config/tool-access.php` para a pasta `config/`.
5. Confira o caminho da instalação. Os links usam caminhos relativos e funcionam em `/SiteTools/` ou `/projetos/SiteTools/`.
6. Teste primeiro com uma conta existente.


## Linkar a área no cabeçalho existente
O `templates/header.php` já identifica `$authUser`. Dentro do bloco `<?php if ($authUser): ?>`,
antes do formulário de logout, acrescente:

```php
<a class="button" href="./account/index.php">Minha conta</a>
```

Assim o visitante continua vendo Login/Cadastro e o usuário autenticado passa a ver
o atalho para o painel. O módulo não altera seu cabeçalho automaticamente.

## Rotas
- `account/index.php` — painel
- `account/tools.php` — ferramentas e acessos
- `account/subscriptions.php` — acessos temporários individuais
- `account/history.php` — operações vinculadas à conta
- `account/files.php` — arquivos registrados para o usuário
- `account/favorites.php` — favoritos
- `account/profile.php` — dados básicos da sessão
- `account/download.php?id=...` — download seguro de arquivo registrado

## Integração do bloqueio de ferramentas pagas
Por padrão, toda ferramenta continua pública e pode ser usada sem conta.
Somente ferramentas cadastradas em `tool_access_rules` com `access_mode = 'temporary'`
passam a exigir uma concessão válida.

No `page.php`, depois que `$tool` tiver sido carregada e antes do HTML, adicione:

```php
require_once __DIR__ . '/config/tool-access.php';
$toolAccess = ['allowed' => true, 'reason' => 'public'];

if ($tool && isset($pdo) && $pdo instanceof PDO) {
    $toolAccess = tool_access_check($pdo, $tool);
}
```

No bloco que renderiza a área funcional da ferramenta, antes dos `if/elseif` dos includes,
envolva o conteúdo atual assim:

```php
<?php if (empty($toolAccess['allowed'])): ?>
    <section class="account-access-denied">
        <h2>Esta ferramenta exige acesso ativo</h2>
        <p>Entre na sua conta para consultar ou adquirir o acesso individual.</p>
        <a class="button button--primary" href="./tools/login.php">Entrar na conta</a>
        <a href="./account/subscriptions.php">Consultar meus acessos</a>
    </section>
<?php else: ?>
    <!-- mantenha aqui o bloco atual de includes das ferramentas -->
<?php endif; ?>
```

Ajuste o link de login se a rota pública do seu projeto for diferente.
`tool_access_check()` retorna permitido para qualquer ferramenta sem regra cadastrada;
assim, não quebra o uso público atual.

## Como marcar uma ferramenta como temporária
```sql
INSERT INTO tool_access_rules (tool_id, access_mode, guest_allowed)
SELECT id, 'temporary', 0
FROM tools
WHERE slug = 'pdf-to-word'
ON DUPLICATE KEY UPDATE
    access_mode = VALUES(access_mode),
    guest_allowed = VALUES(guest_allowed);
```

Ferramentas públicas:
```sql
INSERT INTO tool_access_rules (tool_id, access_mode, guest_allowed)
SELECT id, 'free', 1
FROM tools
WHERE slug = 'word-counter'
ON DUPLICATE KEY UPDATE
    access_mode = VALUES(access_mode),
    guest_allowed = VALUES(guest_allowed);
```

## Conceder acesso temporário manualmente (teste)
```sql
INSERT INTO user_tool_access
(user_id, tool_id, access_type, status, starts_at, expires_at, source)
SELECT 1, t.id, 'temporary', 'active', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 'manual'
FROM tools t
WHERE t.slug = 'pdf-to-word';
```
Troque `1` pelo ID real de uma conta de teste. O pagamento ainda não é integrado:
o registro manual serve apenas para validar o controle de acesso.

## Registrar operações no histórico
A tabela histórica do painel administrativo `tool_usage` não é alterada.
Para histórico individual, use `user_tool_usage`. Após a operação ser concluída no handler,
inclua `config/user-area.php` e chame:

```php
user_area_log_tool_usage($pdo, $toolId, $toolSlug, 'completed', $processingMs, $fileSizeBytes);
```

A função associa a operação ao `$_SESSION['user_id']` quando existe sessão autenticada.
Visitantes sem login continuam usando a ferramenta, mas não recebem histórico pessoal.

## Arquivos
A tabela `user_files` registra somente arquivos que você decidir manter para o usuário.
O módulo não copia automaticamente arquivos temporários de conversão. Integre o registro
ao handler depois de salvar o arquivo em diretório privado fora da raiz pública.

## Pagamentos
`user_payments` guarda registros de pagamento e referência externa. Não há gateway,
checkout ou webhook neste pacote. A concessão de acesso deve ser criada somente após
confirmação autenticada do provedor de pagamento.

## Segurança
- Cada rota privada valida `auth_user()`.
- Ações POST usam CSRF.
- Queries usam PDO preparado.
- Downloads verificam proprietário e caminho privado.
- Não coloque arquivos privados em `assets/` ou outra pasta pública.
