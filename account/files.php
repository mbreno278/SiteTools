<?php
require __DIR__ . '/_bootstrap.php';

$uid = (int) $currentUser['id'];
$success = null;
$error = null;

// Excluir arquivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    csrf_verify();
    $fileId = filter_input(INPUT_POST, 'file_id', FILTER_VALIDATE_INT);

    if ($fileId) {
        try {
            $stmt = $pdo->prepare("SELECT id, storage_path FROM user_files WHERE id = :id AND user_id = :uid AND status = 'available' LIMIT 1");
            $stmt->execute(['id' => $fileId, 'uid' => $uid]);
            $file = $stmt->fetch();

            if ($file) {
                // user_files não possui coluna updated_at no schema atual
                $upd = $pdo->prepare("UPDATE user_files SET status = 'deleted' WHERE id = :id AND user_id = :uid");
                $upd->execute(['id' => $fileId, 'uid' => $uid]);

                // Tenta remover o arquivo físico (não crítico se falhar)
                $root = realpath(__DIR__ . '/../storage/user-files');
                $path = realpath($file['storage_path'] ?? '');
                if ($root && $path && str_starts_with($path, $root . DIRECTORY_SEPARATOR) && is_file($path)) {
                    @unlink($path);
                }

                $success = 'Arquivo removido com sucesso.';
            } else {
                $error = 'Arquivo não encontrado.';
            }
        } catch (Throwable $e) {
            $error = 'Não foi possível remover o arquivo.';
        }
    }
}

$q = $pdo->prepare(
    "SELECT id, original_name, mime_type, size_bytes, created_at, expires_at
     FROM user_files
     WHERE user_id = :uid
       AND status = 'available'
       AND (expires_at IS NULL OR expires_at > NOW())
     ORDER BY created_at DESC
     LIMIT 100"
);
$q->execute(['uid' => $uid]);
$rows = $q->fetchAll();

$totalSize = array_sum(array_column($rows, 'size_bytes'));

account_page_start('Meus arquivos');
account_header('Meus arquivos', 'Arquivos que as ferramentas salvaram na sua conta.');
?>

<?php if ($success): ?>
    <div class="ua-alert ua-alert--success">
        <span class="material-symbols-outlined">check_circle</span>
        <?= user_area_e($success) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="ua-alert ua-alert--error">
        <span class="material-symbols-outlined">error</span>
        <?= user_area_e($error) ?>
    </div>
<?php endif; ?>

<section class="ua-panel">
    <div class="ua-panel-head">
        <h2>Arquivos disponíveis</h2>
        <?php if ($rows): ?>
            <span class="ua-meta-text"><?= count($rows) ?> arquivo<?= count($rows) > 1 ? 's' : '' ?> · <?= account_format_bytes((int) $totalSize) ?></span>
        <?php endif; ?>
    </div>

    <?php if (!$rows): ?>
        <div class="ua-empty">
            <span class="material-symbols-outlined">folder_open</span>
            <p>Nenhum arquivo salvo na sua conta.</p>
            <small>Arquivos temporários de processamento não aparecem aqui automaticamente. Use as ferramentas e salve o resultado quando disponível.</small>
            <a class="ua-button ua-button--light" href="../index.php" style="margin-top:14px">Explorar ferramentas</a>
        </div>
    <?php else: ?>
        <div class="ua-table-wrap">
            <table class="ua-table">
                <thead>
                    <tr>
                        <th>Arquivo</th>
                        <th>Tipo</th>
                        <th>Tamanho</th>
                        <th>Adicionado</th>
                        <th>Expira em</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r):
                        $expires = $r['expires_at'] ? strtotime($r['expires_at']) : null;
                        $daysLeft = $expires ? max(0, (int) ceil(($expires - time()) / 86400)) : null;
                    ?>
                        <tr>
                            <td>
                                <div class="ua-file-name">
                                    <span class="material-symbols-outlined">description</span>
                                    <?= user_area_e($r['original_name']) ?>
                                </div>
                            </td>
                            <td><?= user_area_e($r['mime_type'] ?: '—') ?></td>
                            <td><?= account_format_bytes((int) $r['size_bytes']) ?></td>
                            <td><?= user_area_e(date('d/m/Y H:i', strtotime($r['created_at']))) ?></td>
                            <td>
                                <?php if ($daysLeft === null): ?>
                                    <span class="ua-status">Sem prazo</span>
                                <?php elseif ($daysLeft <= 2): ?>
                                    <span class="ua-status ua-status--expired"><?= $daysLeft ?> dia<?= $daysLeft !== 1 ? 's' : '' ?></span>
                                <?php else: ?>
                                    <span class="ua-status"><?= $daysLeft ?> dias</span>
                                <?php endif; ?>
                            </td>
                            <td class="ua-table-actions">
                                <a class="ua-button ua-button--light" href="download.php?id=<?= (int) $r['id'] ?>">Baixar</a>
                                <form method="post" class="ua-inline-form" onsubmit="return confirm('Remover este arquivo permanentemente?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="file_id" value="<?= (int) $r['id'] ?>">
                                    <button type="submit" class="ua-button ua-button--danger">Remover</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<div class="ua-note">
    Arquivos salvos podem ter prazo de validade conforme a política de cada ferramenta.
    Baixe seus arquivos importantes antes da data de expiração.
</div>

<?php account_footer(); ?>
