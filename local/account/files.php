<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

$uid = (int) $currentUser['id'];

// Exclusão lógica via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_verify();
    $fileId = filter_var($_POST['file_id'] ?? null, FILTER_VALIDATE_INT);
    if ($fileId) {
        try {
            $stmt = $pdo->prepare(
                "SELECT id, storage_path FROM user_files
                 WHERE id = :id AND user_id = :uid AND status = 'available' LIMIT 1"
            );
            $stmt->execute(['id' => $fileId, 'uid' => $uid]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($file) {
                $pdo->prepare(
                    "UPDATE user_files SET status = 'deleted' WHERE id = :id AND user_id = :uid"
                )->execute(['id' => $fileId, 'uid' => $uid]);

                // Remove físico se estiver dentro de storage/user-files
                $root = realpath(__DIR__ . '/../storage/user-files');
                $path = realpath((string) $file['storage_path']);
                if ($root && $path && str_starts_with($path, $root . DIRECTORY_SEPARATOR) && is_file($path)) {
                    @unlink($path);
                }
            }
        } catch (Throwable $e) {
            error_log('[account/files delete] ' . $e->getMessage());
        }
    }
    header('Location: files.php?deleted=1');
    exit;
}

$rows = [];
try {
    $stmt = $pdo->prepare(
        "SELECT f.id, f.original_name, f.mime_type, f.size_bytes, f.expires_at, f.created_at,
                t.name AS tool_name
         FROM user_files f
         LEFT JOIN tools t ON t.id = f.tool_id
         WHERE f.user_id = :uid
           AND f.status = 'available'
           AND (f.expires_at IS NULL OR f.expires_at > NOW())
         ORDER BY f.created_at DESC
         LIMIT 100"
    );
    $stmt->execute(['uid' => $uid]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log('[account/files] ' . $e->getMessage());
}

$csrf = function_exists('csrf_token') ? csrf_token() : '';

account_page_start('Meus arquivos');
account_header('Meus arquivos', 'Arquivos processados salvos na sua conta. Download apenas autenticado.');
?>
<?php if (isset($_GET['deleted'])): ?>
    <p class="ua-note">Arquivo removido.</p>
<?php endif; ?>

<section class="ua-panel">
    <?php if (!$rows): ?>
        <div class="ua-empty">
            <span class="material-symbols-outlined">folder_open</span>
            Nenhum arquivo disponível. Ao processar arquivos logado, eles poderão aparecer aqui quando a ferramenta salvar o resultado.
        </div>
    <?php else: ?>
        <div class="ua-table-wrap">
            <table class="ua-table">
                <thead>
                    <tr>
                        <th>Arquivo</th>
                        <th>Ferramenta</th>
                        <th>Tamanho</th>
                        <th>Criado</th>
                        <th>Expira</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $f): ?>
                    <tr>
                        <td><?= user_area_e((string) $f['original_name']) ?></td>
                        <td><?= user_area_e((string) ($f['tool_name'] ?? '—')) ?></td>
                        <td><?= user_area_e(user_area_format_bytes((int) ($f['size_bytes'] ?? 0))) ?></td>
                        <td><?= user_area_e(date('d/m/Y H:i', strtotime((string) $f['created_at']))) ?></td>
                        <td><?= !empty($f['expires_at']) ? user_area_e(date('d/m/Y', strtotime((string) $f['expires_at']))) : '—' ?></td>
                        <td>
                            <a class="ua-button ua-button--light" href="download.php?id=<?= (int) $f['id'] ?>">Baixar</a>
                            <form method="post" style="display:inline" onsubmit="return confirm('Excluir este arquivo?');">
                                <input type="hidden" name="_csrf" value="<?= user_area_e($csrf) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="file_id" value="<?= (int) $f['id'] ?>">
                                <button type="submit" class="ua-button ua-button--danger">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <p class="ua-note" style="margin-top:16px">
        Arquivos ficam fora do acesso público direto. O download exige login e valida se o arquivo pertence à sua conta.
    </p>
</section>
<?php account_footer(); ?>
