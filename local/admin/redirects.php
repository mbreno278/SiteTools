<?php
/**
 * Painel de gerenciamento de redirecionamentos de URL
 * SiteTools
 */

declare(strict_types=1);

$message = null;
$error   = null;

// Processa ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['_csrf'] ?? null)) {
        $error = 'Token CSRF inválido.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $from = trim($_POST['from'] ?? '');
            $to   = trim($_POST['to'] ?? '');
            $code = (int)($_POST['code'] ?? 301);

            if ($from === '' || $to === '') {
                $error = 'Origem e destino são obrigatórios.';
            } elseif (!in_array($code, [301, 302, 307, 308], true)) {
                $error = 'Código de redirecionamento inválido.';
            } else {
                if (add_redirect($from, $to, $code)) {
                    $message = 'Redirecionamento adicionado com sucesso!';
                } else {
                    $error = 'Falha ao salvar o redirecionamento.';
                }
            }
        } elseif ($action === 'delete') {
            $from = trim($_POST['from'] ?? '');
            if ($from && remove_redirect($from)) {
                $message = 'Redirecionamento removido.';
            } else {
                $error = 'Não foi possível remover.';
            }
        }
    }
}

$redirects = load_redirects();
// Ordena por caminho
ksort($redirects);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecionamentos - SiteTools</title>
    <style>
        :root {
            --bg: #0f172a;
            --card: #1e293b;
            --border: #334155;
            --text: #e2e8f0;
            --muted: #94a3b8;
            --accent: #3b82f6;
            --danger: #ef4444;
            --success: #22c55e;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 20px;
            line-height: 1.5;
        }
        .container { max-width: 960px; margin: 0 auto; }
        h1 { font-size: 1.8rem; margin-bottom: 0.25rem; }
        .subtitle { color: var(--muted); margin-bottom: 1.5rem; }
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        label { display: block; font-weight: 600; margin-bottom: 0.35rem; font-size: 0.9rem; }
        input[type="text"], select {
            width: 100%;
            padding: 0.65rem 0.85rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #0f172a;
            color: var(--text);
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }
        .row { display: grid; grid-template-columns: 1fr 1fr 120px auto; gap: 1rem; align-items: end; }
        @media (max-width: 700px) { .row { grid-template-columns: 1fr; } }
        button, .btn {
            background: var(--accent);
            color: white;
            border: none;
            padding: 0.65rem 1.2rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.95rem;
            text-decoration: none;
            display: inline-block;
        }
        button:hover, .btn:hover { filter: brightness(1.1); }
        .btn-danger { background: var(--danger); }
        .msg {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .msg-success { background: rgba(34,197,94,0.15); border: 1px solid var(--success); color: #86efac; }
        .msg-error { background: rgba(239,68,68,0.15); border: 1px solid var(--danger); color: #fca5a5; }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 0.75rem;
            border-bottom: 1px solid var(--border);
        }
        th { color: var(--muted); font-weight: 600; font-size: 0.85rem; }
        code {
            background: #0f172a;
            padding: 0.2rem 0.45rem;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .nav { margin-bottom: 1.5rem; }
        .nav a { color: var(--accent); text-decoration: none; margin-right: 1rem; }
        .empty { color: var(--muted); text-align: center; padding: 2rem; }
        .badge {
            display: inline-block;
            padding: 0.15rem 0.45rem;
            border-radius: 4px;
            font-size: 0.8rem;
            background: rgba(59,130,246,0.2);
            color: #93c5fd;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="nav">
        <a href="/">← Início</a>
        <a href="/curl">Ferramenta cURL</a>
    </div>

    <h1>Redirecionamentos de URL</h1>
    <p class="subtitle">Gerencie a lista de redirecionamentos. Quando alguém acessar a URL de origem, será enviado para o destino com o código HTTP configurado.</p>

    <?php if ($message): ?>
        <div class="msg msg-success"><?= e($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="msg msg-error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <h2 style="margin-top:0; font-size:1.2rem;">Adicionar redirecionamento</h2>
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="add">

            <div class="row">
                <div>
                    <label for="from">URL de origem (caminho)</label>
                    <input type="text" id="from" name="from" placeholder="/antiga-pagina" required>
                </div>
                <div>
                    <label for="to">URL de destino</label>
                    <input type="text" id="to" name="to" placeholder="https://exemplo.com/nova ou /nova-pagina" required>
                </div>
                <div>
                    <label for="code">Código</label>
                    <select id="code" name="code">
                        <option value="301">301 Permanente</option>
                        <option value="302">302 Temporário</option>
                        <option value="307">307 Temporary</option>
                        <option value="308">308 Permanent</option>
                    </select>
                </div>
                <div>
                    <button type="submit">Adicionar</button>
                </div>
            </div>
        </form>
    </div>

    <div class="card">
        <h2 style="margin-top:0; font-size:1.2rem;">Lista atual (<?= count($redirects) ?>)</h2>

        <?php if (empty($redirects)): ?>
            <p class="empty">Nenhum redirecionamento cadastrado ainda.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Origem</th>
                        <th>Destino</th>
                        <th>Código</th>
                        <th>Criado em</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($redirects as $from => $data): ?>
                    <?php
                    $url  = is_array($data) ? ($data['url'] ?? '') : $data;
                    $code = is_array($data) ? ($data['code'] ?? 301) : 301;
                    $created = is_array($data) ? ($data['created_at'] ?? '—') : '—';
                    ?>
                    <tr>
                        <td><code><?= e($from) ?></code></td>
                        <td><code><?= e($url) ?></code></td>
                        <td><span class="badge"><?= (int)$code ?></span></td>
                        <td style="color:var(--muted); font-size:0.9rem;"><?= e($created) ?></td>
                        <td>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Remover este redirecionamento?');">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="from" value="<?= e($from) ?>">
                                <button type="submit" class="btn-danger" style="padding:0.35rem 0.7rem; font-size:0.85rem;">Remover</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card" style="font-size:0.9rem; color:var(--muted);">
        <strong>Como funciona:</strong>
        <ul>
            <li>O sistema verifica a lista <strong>antes</strong> de qualquer rota de ferramenta.</li>
            <li>Use caminhos relativos na origem (ex: <code>/blog/post-antigo</code>).</li>
            <li>No destino pode ser URL completa ou caminho interno.</li>
            <li>301 = permanente (bom para SEO), 302 = temporário.</li>
        </ul>
    </div>
</div>
</body>
</html>
