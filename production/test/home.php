<?php
/**
 * Página inicial do SiteTools (exemplo)
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiteTools - Ferramentas Online</title>
    <style>
        :root {
            --bg: #0f172a;
            --card: #1e293b;
            --border: #334155;
            --text: #e2e8f0;
            --muted: #94a3b8;
            --accent: #3b82f6;
        }
        body {
            font-family: system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 40px 20px;
            line-height: 1.6;
        }
        .container { max-width: 800px; margin: 0 auto; text-align: center; }
        h1 { font-size: 2.2rem; margin-bottom: 0.5rem; }
        p { color: var(--muted); margin-bottom: 2rem; }
        .tools {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        .tool {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            text-decoration: none;
            color: var(--text);
            transition: border-color 0.2s;
        }
        .tool:hover { border-color: var(--accent); }
        .tool h3 { margin: 0 0 0.5rem; color: var(--accent); }
        .tool p { margin: 0; font-size: 0.9rem; }
    </style>
</head>
<body>
<div class="container">
    <h1>SiteTools</h1>
    <p>Projeto com várias ferramentas online. Sistema de URLs amigáveis e redirecionamentos.</p>

    <div class="tools">
        <a class="tool" href="/curl">
            <h3>cURL Online</h3>
            <p>Execute requisições HTTP (GET, POST, etc.) direto do navegador.</p>
        </a>
        <a class="tool" href="/admin/redirects">
            <h3>Redirecionamentos</h3>
            <p>Adicione e gerencie a lista de URLs que devem ser redirecionadas.</p>
        </a>
    </div>
</div>
</body>
</html>
