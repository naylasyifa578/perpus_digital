<?php $isAdmin = $pageRole === 'admin'; ?>
<div class="page-heading">
    <div>
        <h2><?= $isAdmin ? 'Peminjaman' : 'Riwayat Peminjaman' ?></h2>
        <p><?= $isAdmin
                ? 'Kelola pengajuan dan status peminjaman.'
                : 'Lihat status buku yang Anda pinjam. Buku dapat dikembalikan kapan saja setelah statusnya Dipinjam, tanpa harus menunggu jatuh tempo.' ?></p>
    </div>
    <?php if ($isAdmin): ?>
        <a class="btn primary" href="<?= e(url('peminjaman.form')) ?>"><?= icon('plus', 18) ?> Tambah Transaksi</a>
    <?php endif; ?>
</div>
<form class="filter-bar" method="get" action="index.php">
    <input type="hidden" name="page" value="<?= $isAdmin ? 'peminjaman' : 'riwayat' ?>">
    <div class="search-field"><?= icon('search', 19) ?><input name="q" value="<?= e($keyword) ?>" placeholder="Cari nama buku..."></div>
    <select name="status">
        <option value="">Semua Status</option>
        <?php foreach ($statuses as $st): ?>
            <option value="<?= e($st) ?>" <?= $status === $st ? 'selected' : '' ?>><?= e(status_label($st)) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn primary" type="submit">Cari</button>
</form>
<div class="table-card">
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <?php if ($isAdmin): ?><th>Nama Peminjam</th><?php endif; ?>
                    <th>Nama Buku</th>
                    <th>Tanggal Pinjam</th>
                    <th>Tanggal Pengembalian</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($loans as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <?php if ($isAdmin): ?><td><?= e($r['nama_lengkap']) ?></td><?php endif; ?>
                        <td><a class="icon-btn" href="<?= e(url('peminjaman.detail', ['id' => $r['id']])) ?>" title="Detail"><?= icon('eye', 17) ?></a> <?= e($r['judul']) ?></td>
                        <td><?= e(format_date_with_day($r['tanggal_pinjam'])) ?></td>
                        <td><?= e(format_date_with_day(!empty($r['tanggal_pengembalian_aktual']) ? $r['tanggal_pengembalian_aktual'] : due_date($r['tanggal_pinjam']))) ?></td>
                        <td><span class="badge <?= e(status_class($r['status'])) ?>"><?= e(status_label($r['status'])) ?></span></td>
                        <td>
                            <?php if ($isAdmin): ?>
                                <div class="actions">
                                <form method="post" action="<?= e(url('peminjaman.status')) ?>" class="inline-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                    <select name="status">
                                        <option value="">Pilih aksi</option>
                                        <?php foreach (['disetujui', 'dipinjam', 'ditolak'] as $st): ?>
                                            <option value="<?= e($st) ?>"><?= e(status_label($st)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn primary small" type="submit">Update</button>
                                </form>
                                <?php if ($r['status'] !== 'dipinjam'): ?>
                                    <form method="post" action="<?= e(url('peminjaman.delete')) ?>" class="inline-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                        <button class="icon-btn danger-text" type="submit" title="Hapus transaksi" data-confirm="Hapus transaksi ini? Data pengembalian terkait ikut terhapus."><?= icon('trash', 17) ?></button>
                                    </form>
                                <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="actions">
                                    <?php if (in_array($r['status'], ['pengajuan', 'disetujui'], true)): ?>
                                        <form method="post" action="<?= e(url('peminjaman.cancel')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                            <button class="btn danger-outline small" type="submit" data-confirm="Batalkan pengajuan peminjaman ini?">Batalkan</button>
                                        </form>
                                    <?php elseif ($r['status'] === 'dipinjam'): ?>
                                        <form method="post" action="<?= e(url('peminjaman.return')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                            <button class="btn primary small" type="submit" data-confirm="Kembalikan buku ini?">Kembalikan Sekarang</button>
                                        </form>
                                    <?php elseif (in_array($r['status'], ['dibatalkan', 'ditolak'], true)): ?>
                                        <form method="post" action="<?= e(url('peminjaman.delete')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                            <button class="icon-btn danger-text" type="submit" title="Hapus dari riwayat" data-confirm="Hapus riwayat ini?"><?= icon('trash', 17) ?></button>
                                        </form>
                                    <?php else: ?>—<?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$loans): ?>
                    <tr>
                        <td colspan="<?= $isAdmin ? 7 : 6 ?>" class="empty">Belum ada transaksi.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
