<?php

require_once __DIR__ . '/_guard.php';
$php = PHP_VERSION;
$diskFree = @disk_free_space(__DIR__) ?: 0;
$diskTotal = @disk_total_space(__DIR__) ?: 1;
?>
<div class="page-heading"><div><p class="eyebrow">SISTEMA</p><h1>Manutenção</h1><p class="muted">Informações do ambiente.</p></div></div>
<section class="stats-grid">
    <article class="stat-card"><div class="stat-top"><span>PHP</span></div><strong><?= e($php) ?></strong></article>
    <article class="stat-card"><div class="stat-top"><span>Disco livre</span></div><strong><?= number_format($diskFree / 1073741824, 2, ',', '.') ?> GB</strong></article>
    <article class="stat-card"><div class="stat-top"><span>Disco total</span></div><strong><?= number_format($diskTotal / 1073741824, 2, ',', '.') ?> GB</strong></article>
</section>
<div class="panel" style="margin-top:20px;">
    <p class="muted">Rotinas futuras: limpeza de <code>file_uploads</code> expirados, otimização de tabelas e filas de <code>tool_processing_jobs</code>.</p>
</div>
