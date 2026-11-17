<div class="section-head"><a href="<?= e(url($backRoute)) ?>"><?= icon('back', 20) ?> Kembali</a>
    <h3>Detail Peminjaman</h3><span></span>
</div>

<div class="detail-card print-area">
    <div class="detail-head">
        <div class="detail-book-icon"><?= icon('book', 42) ?></div>
        <div>
            <h2><?= e($loan['judul']) ?></h2>
            <span class="badge <?= e(status_class($loan['status'])) ?>"><?= e(status_label($loan['status'])) ?></span>
        </div>
    </div>

    <div class="detail-body">
        <div><b>ID Peminjaman</b><span>PM-<?= str_pad((string) $loan['id'], 4, '0', STR_PAD_LEFT) ?></span></div>
        <div><b>Peminjam</b><span><?= e($loan['nama_lengkap']) ?></span></div>
        <div><b>Username</b><span><?= e($loan['username']) ?></span></div>
        <div><b>Email</b><span><?= e($loan['gmail'] ?? '-') ?></span></div>
        <div><b>Kategori</b><span><?= e($loan['Kategori'] ?? '-') ?></span></div>
        <div><b>Pengarang</b><span><?= e($loan['pengarang']) ?></span></div>
        <div><b>Penerbit</b><span><?= e($loan['penerbit']) ?></span></div>
        <div><b>Tanggal Pinjam</b><span><?= e(format_date_with_day($loan['tanggal_pinjam'])) ?></span></div>
        <div><b>Jatuh Tempo</b><span><?= e(format_date_with_day(due_date($loan['tanggal_pinjam']))) ?></span></div>
        <div><b>Status</b><span><?= e(status_label($loan['status'])) ?></span></div>
        <?php if ($returned !== null): ?>
            <div><b>Tanggal Pengembalian</b><span><?= e(format_date_with_day($returned['tanggal_pengembalian'])) ?></span></div>
            <div><b>Sanksi</b><span><?= e($sanction) ?></span></div>
            <div><b>Keterangan</b><span><?= e($returned['keterangan'] ?? '-') ?></span></div>
        <?php endif; ?>
    </div>
</div>
<div class="detail-actions <?= $role === 'admin' ? 'admin-detail-actions' : '' ?>">
    <a class="btn primary" href="<?= e(url('unduh', ['type' => 'loan', 'id' => $loan['id']])) ?>"><?= icon('save', 17) ?> Simpan ke Perangkat</a>
    <?php if ($role === 'admin'): ?><button class="btn ghost" type="button" onclick="window.print()"><?= icon('print', 17) ?> Print</button><?php endif; ?>
    <a class="btn ghost" href="<?= e(url($backRoute)) ?>">Kembali</a>
</div>
