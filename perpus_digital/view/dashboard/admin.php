<?php
$quickMenus = [
    ['buku', 'book', 'Data', 'Kelola semua data buku'],
    ['peminjaman', 'swap', 'Peminjaman', 'Kelola transaksi pinjam'],
    ['profil', 'user', 'Profil', 'Kelola akun dan pengaturan'],
];
?>
<div class="welcome-banner">
    <div>
        <h2>Halo, <?= e($userName) ?>! </h2>
        <p>Selamat datang di Aksaraya</p>
    </div>
    <div class="welcome-mark"><?= icon('book', 18) ?></div>
</div>
<div class="quick-title">Menu cepat</div>
<div class="quick-grid">
    <?php foreach ($quickMenus as $q): ?>
        <a class="quick-card" href="<?= e(url($q[0])) ?>">
            <div class="quick-icon"><?= icon($q[1], 30) ?></div><strong><?= e($q[2]) ?></strong><small><?= e($q[3]) ?></small>
        </a>
    <?php endforeach; ?>
</div>

<div class="stats-grid">
    <div class="stat"><span class="stat-icon blue"><?= icon('book', 22) ?></span>
        <div><small>Total Buku</small><b><?= (int) $stats['buku'] ?></b><em><?= (int) $stats['stok'] ?> stok</em></div>
    </div>
    <div class="stat"><span class="stat-icon green"><?= icon('users', 22) ?></span>
        <div><small>Total Anggota</small><b><?= (int) $stats['anggota'] ?></b><em>anggota siswa</em></div>
    </div>
    <div class="stat"><span class="stat-icon purple"><?= icon('swap', 22) ?></span>
        <div><small>Total Transaksi</small><b><?= (int) $stats['transaksi'] ?></b><em>peminjaman</em></div>
    </div>
    <div class="stat"><span class="stat-icon orange"><?= icon('return', 22) ?></span>
        <div><small>Peminjaman Aktif</small><b><?= (int) $stats['aktif'] ?></b><em>belum kembali</em></div>
    </div>
</div>

<div class="section-head">
    <h3>Peminjaman terbaru</h3><a href="<?= e(url('peminjaman')) ?>">Lihat semua</a>
</div>
<div class="table-card">
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Peminjam</th>
                    <th>Buku</th>
                    <th>Tanggal Pinjam</th>
                    <th>Jatuh Tempo</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= e($r['nama_lengkap']) ?></td>
                        <td><?= e($r['judul']) ?></td>
                        <td><?= e(format_date_with_day($r['tanggal_pinjam'])) ?></td>
                        <td><?= e(format_date_with_day(due_date($r['tanggal_pinjam']))) ?></td>
                        <td><span class="badge <?= e(status_class($r['status'])) ?>"><?= e(status_label($r['status'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$recent): ?>
                    <tr>
                        <td colspan="6" class="empty">Belum ada transaksi.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
