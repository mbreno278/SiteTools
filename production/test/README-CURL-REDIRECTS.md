# Sistema de cURL + URLs Amigáveis + Redirecionamentos — SiteTools

Este pacote adiciona ao projeto SiteTools:

1. **URLs amigáveis** (pretty URLs) via front-controller + `.htaccess`
2. **Ferramenta cURL online** (`/curl`)
3. **Sistema de redirecionamentos de URL** gerenciável em `/admin/redirects`

## Estrutura adicionada

```
sitetools/
├── .htaccess                 # Rewrite para index.php
├── index.php                 # Front-controller (rotas + redirects)
├── home.php                  # Página inicial de exemplo
├── config/
│   └── helpers.php           # Funções de redirects, CSRF, etc.
├── tools/
│   └── curl.php              # Interface web do cURL
├── admin/
│   └── redirects.php         # Painel para adicionar/remover redirects
└── data/
    └── redirects.json        # Lista persistente de redirecionamentos
```

## Como funciona

### URLs amigáveis
- Todas as requisições passam pelo `index.php`.
- Rotas mapeadas:
  - `/` → home
  - `/curl` ou `/ferramentas/curl` → ferramenta cURL
  - `/admin/redirects` ou `/redirects` → painel de redirects
  - Qualquer `/slug` tenta carregar `tools/slug.php` se existir

### Redirecionamentos
- Antes de qualquer rota, o sistema consulta `data/redirects.json`.
- Se a URL de origem existir, faz o `header('Location: ...')` com o código configurado (301, 302, 307 ou 308).
- Você adiciona novos redirecionamentos pelo painel em `/admin/redirects`.

### Ferramenta cURL
- Interface completa: método, headers, body, timeout, seguir redirects, ignorar SSL.
- Executa via PHP `curl_*` e mostra status, headers e body da resposta.
- Protegido com token CSRF simples.

## Instalação no projeto existente

1. Copie os arquivos para a raiz do SiteTools (ou adapte os caminhos).
2. Certifique-se de que o Apache tenha `mod_rewrite` habilitado e `AllowOverride All`.
3. Dê permissão de escrita na pasta `data/` (para o JSON de redirects).
4. Se o projeto já tiver um `index.php` ou `.htaccess`, integre as regras de rewrite e a checagem de redirects no início do seu front-controller.

### Integração rápida no seu index.php existente

No início do seu front-controller (antes de qualquer output):

```php
require_once __DIR__ . '/config/helpers.php'; // se ainda não carregou

$redirects = load_redirects();
$path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/', '/') ?: '/';

if (isset($redirects[$path])) {
    $t = $redirects[$path];
    $url  = is_array($t) ? $t['url'] : $t;
    $code = is_array($t) ? ($t['code'] ?? 301) : 301;
    header('Location: ' . $url, true, (int)$code);
    exit;
}
```

E adicione a rota `/curl` apontando para `tools/curl.php`.

## Segurança

- A ferramenta cURL pode ser usada para SSRF se exposta publicamente sem limites. Considere:
  - Rate limiting
  - Bloquear IPs privados / localhost
  - Exigir login (como as outras ferramentas do SiteTools)
- O painel de redirects também deve ficar protegido por autenticação de admin.

## Exemplos de uso do painel

| Origem              | Destino                          | Código |
|---------------------|----------------------------------|--------|
| `/blog/post-velho`  | `/blog/post-novo`                | 301    |
| `/promo`            | `https://parceiro.com/oferta`    | 302    |
| `/antigo-curl`      | `/curl`                          | 301    |

Pronto! Qualquer dúvida ou ajuste (proteger com login, salvar no banco em vez de JSON, etc.) é só pedir.
