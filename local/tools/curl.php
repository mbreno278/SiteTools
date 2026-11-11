<?php
/**
 * Ferramenta cURL do SiteTools
 * Permite executar requisições HTTP via interface web (usando PHP cURL)
 */

declare(strict_types=1);

$result = null;
$error  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validação básica CSRF (opcional mas recomendado)
    if (!csrf_check($_POST['_csrf'] ?? null)) {
        $error = 'Token de segurança inválido. Recarregue a página.';
    } else {
        $url     = trim($_POST['url'] ?? '');
        $method  = strtoupper(trim($_POST['method'] ?? 'GET'));
        $headers = trim($_POST['headers'] ?? '');
        $body    = $_POST['body'] ?? '';
        $follow  = isset($_POST['follow_redirects']);
        $timeout = max(1, min(60, (int)($_POST['timeout'] ?? 30)));
        $insecure = isset($_POST['insecure']);

        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            $error = 'URL inválida.';
        } elseif (!in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'], true)) {
            $error = 'Método HTTP não permitido.';
        } else {
            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER         => true,
                CURLOPT_CUSTOMREQUEST  => $method,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_FOLLOWLOCATION => $follow,
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_USERAGENT      => 'SiteTools-cURL/1.0',
                CURLOPT_SSL_VERIFYPEER => !$insecure,
                CURLOPT_SSL_VERIFYHOST => $insecure ? 0 : 2,
            ]);

            // Headers
            $headerList = [];
            if ($headers !== '') {
                foreach (explode("\n", $headers) as $line) {
                    $line = trim($line);
                    if ($line !== '' && strpos($line, ':') !== false) {
                        $headerList[] = $line;
                    }
                }
            }
            if (!empty($headerList)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headerList);
            }

            // Body (para métodos que suportam)
            if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true) && $body !== '') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }

            $response = curl_exec($ch);
            $info     = curl_getinfo($ch);
            $curlErr  = curl_error($ch);
            curl_close($ch);

            if ($response === false) {
                $error = 'Erro cURL: ' . $curlErr;
            } else {
                $headerSize = $info['header_size'] ?? 0;
                $rawHeaders = substr($response, 0, $headerSize);
                $rawBody    = substr($response, $headerSize);

                $result = [
                    'http_code'     => $info['http_code'] ?? 0,
                    'total_time'    => round($info['total_time'] ?? 0, 3),
                    'size_download' => $info['size_download'] ?? 0,
                    'content_type'  => $info['content_type'] ?? '',
                    'headers'       => $rawHeaders,
                    'body'          => $rawBody,
                    'url'           => $info['url'] ?? $url,
                    'method'        => $method,
                ];
            }
        }
    }
}

// Layout simples
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>cURL Online - SiteTools</title>
    <style>
        :root {
            --bg: #0f172a;
            --card: #1e293b;
            --border: #334155;
            --text: #e2e8f0;
            --muted: #94a3b8;
            --accent: #3b82f6;
            --success: #22c55e;
            --danger: #ef4444;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 20px;
            line-height: 1.5;
        }
        .container { max-width: 1000px; margin: 0 auto; }
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
        input[type="text"], input[type="number"], select, textarea {
            width: 100%;
            padding: 0.65rem 0.85rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #0f172a;
            color: var(--text);
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }
        textarea { min-height: 100px; font-family: ui-monospace, monospace; resize: vertical; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 640px) { .row { grid-template-columns: 1fr; } }
        .checks { display: flex; gap: 1.5rem; flex-wrap: wrap; margin-bottom: 1rem; }
        .checks label { display: flex; align-items: center; gap: 0.4rem; font-weight: 500; margin: 0; }
        button {
            background: var(--accent);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
        }
        button:hover { filter: brightness(1.1); }
        .error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid var(--danger);
            color: #fca5a5;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        .meta span {
            background: #0f172a;
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            border: 1px solid var(--border);
        }
        .code {
            background: #0f172a;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1rem;
            overflow-x: auto;
            font-family: ui-monospace, monospace;
            font-size: 0.85rem;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 400px;
            overflow-y: auto;
        }
        .nav { margin-bottom: 1.5rem; }
        .nav a { color: var(--accent); text-decoration: none; margin-right: 1rem; }
        .nav a:hover { text-decoration: underline; }
        .badge {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .badge-2xx { background: rgba(34,197,94,0.2); color: #4ade80; }
        .badge-3xx { background: rgba(59,130,246,0.2); color: #60a5fa; }
        .badge-4xx, .badge-5xx { background: rgba(239,68,68,0.2); color: #f87171; }
    </style>
</head>
<body>
<div class="container">
    <div class="nav">
        <a href="/">← Início</a>
        <a href="/admin/redirects">Gerenciar Redirecionamentos</a>
    </div>

    <h1>cURL Online</h1>
    <p class="subtitle">Execute requisições HTTP diretamente do navegador. Útil para testar APIs e endpoints.</p>

    <div class="card">
        <form method="post" action="/curl">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

            <label for="url">URL</label>
            <input type="text" id="url" name="url" placeholder="https://api.exemplo.com/v1/resource"
                   value="<?= e($_POST['url'] ?? '') ?>" required>

            <div class="row">
                <div>
                    <label for="method">Método</label>
                    <select id="method" name="method">
                        <?php
                        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];
                        $selected = $_POST['method'] ?? 'GET';
                        foreach ($methods as $m) {
                            $sel = $m === $selected ? ' selected' : '';
                            echo "<option value=\"$m\"$sel>$m</option>";
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <label for="timeout">Timeout (segundos)</label>
                    <input type="number" id="timeout" name="timeout" min="1" max="60"
                           value="<?= e((string)($_POST['timeout'] ?? '30')) ?>">
                </div>
            </div>

            <label for="headers">Headers (um por linha, formato: Nome: valor)</label>
            <textarea id="headers" name="headers" placeholder="Content-Type: application/json&#10;Authorization: Bearer token"><?= e($_POST['headers'] ?? '') ?></textarea>

            <label for="body">Body (JSON, form-data, texto...)</label>
            <textarea id="body" name="body" placeholder='{"chave": "valor"}'><?= e($_POST['body'] ?? '') ?></textarea>

            <div class="checks">
                <label>
                    <input type="checkbox" name="follow_redirects" <?= isset($_POST['follow_redirects']) ? 'checked' : 'checked' ?>>
                    Seguir redirecionamentos
                </label>
                <label>
                    <input type="checkbox" name="insecure" <?= isset($_POST['insecure']) ? 'checked' : '' ?>>
                    Ignorar erros SSL (inseguro)
                </label>
            </div>

            <button type="submit">Executar cURL</button>
        </form>
    </div>

    <?php if ($error): ?>
        <div class="error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($result): ?>
        <div class="card">
            <h2 style="margin-top:0;">Resultado</h2>

            <div class="meta">
                <?php
                $code = (int)$result['http_code'];
                $badgeClass = 'badge-4xx';
                if ($code >= 200 && $code < 300) $badgeClass = 'badge-2xx';
                elseif ($code >= 300 && $code < 400) $badgeClass = 'badge-3xx';
                elseif ($code >= 500) $badgeClass = 'badge-5xx';
                ?>
                <span>Status: <span class="badge <?= $badgeClass ?>"><?= $code ?></span></span>
                <span>Tempo: <?= e((string)$result['total_time']) ?>s</span>
                <span>Tamanho: <?= number_format($result['size_download']) ?> bytes</span>
                <span>Content-Type: <?= e($result['content_type'] ?: '—') ?></span>
                <span>Método: <?= e($result['method']) ?></span>
            </div>

            <h3>Headers da resposta</h3>
            <div class="code"><?= e(trim($result['headers'])) ?></div>

            <h3 style="margin-top:1.25rem;">Body</h3>
            <div class="code"><?= e($result['body'] ?: '(vazio)') ?></div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
