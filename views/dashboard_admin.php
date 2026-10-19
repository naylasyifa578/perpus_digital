<?php
require_once __DIR__ . '/../models/koneksi.php';
require_once __DIR__ . '/../models/auth.php';
require_once __DIR__ . '/../models/migrate.php';
require_once __DIR__ . '/layout.php';
require_role('admin');
ensure_due_dates_14_days($conn);
$total_buku = (int)(mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) total FROM buku'))['total'] ?? 0);
$total_stok = (int)(mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COALESCE(SUM(jumlah_stok),0) total FROM buku'))['total'] ?? 0);
$total_anggota = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM users WHERE role='siswa'"))['total'] ?? 0);
$total_pinjam = (int)(mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) total FROM peminjaman'))['total'] ?? 0);
$total_aktif = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM peminjaman WHERE status IN ('disetujui','dipinjam')"))['total'] ?? 0);
$recent = mysqli_query($conn, "SELECT p.*,u.nama_lengkap,b.judul FROM peminjaman p JOIN users u ON u.id=p.user_id JOIN buku b ON b.id=p.buku_id ORDER BY p.id DESC LIMIT 5");
page_start('Dashboard', 'dashboard_admin.php', 'admin');
render_flash();
?>
<div class="welcome-banner">
    <div>
        <h2>Halo, <?= e($_SESSION['nama_lengkap']) ?>! </h2>
        <p>Selamat datang di Aksaraya</p>
    </div>
    <div class="welcome-mark"><?= icon('book', 18) ?></div>
</div>
<div class="quick-title">Menu cepat</div>
<div class="quick-grid">

    <?php
    foreach ([['buku.php', 'book', 'Data', 'Kelola semua data buku'], ['peminjaman.php', 'swap', 'Peminjaman', 'Kelola transaksi pinjam'], ['profile.php', 'user', 'Profil', 'Kelola akun dan pengaturan']] as $q): ?>
        <a class="quick-card" href="<?= $q[0] ?>">
            <div class="quick-icon"><?= icon($q[1], 30) ?></div><strong><?= $q[2] ?></strong><small><?= $q[3] ?></small>
        </a>
    <?php endforeach; ?>
</div>

<div class="stats-grid">
    <div class="stat"><span class="stat-icon blue"><?= icon('book', 22) ?></span>
        <div><small>Total Buku</small><b><?= $total_buku ?></b><em><?= $total_stok ?>
                stok</em></div>
    </div>
    <div class="stat"><span class="stat-icon green"><?= icon('users', 22) ?></span>
        <div><small>Total Anggota</small><b><?= $total_anggota ?></b><em>anggota siswa</em></div>
    </div>
    <div class="stat"><span class="stat-icon purple"><?= icon('swap', 22) ?></span>
        <div><small>Total Transaksi</small><b><?= $total_pinjam ?></b><em>peminjaman</em></div>
    </div>
    <div class="stat"><span class="stat-icon orange"><?= icon('return', 22) ?></span>
        <div><small>Peminjaman Aktif</small><b><?= $total_aktif ?></b><em>belum kembali</em></div>
    </div>
</div>

<div class="section-head">
    <h3>Peminjaman terbaru</h3><a href="peminjaman.php">Lihat semua</a>
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
                <?php $no = 1;
                while ($r = mysqli_fetch_assoc($recent)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= e($r['nama_lengkap']) ?></td>
                        <td><?= e($r['judul']) ?></td>
                        <td><?= format_date($r['tanggal_pinjam']) ?></td>
                        <td><?= format_date(calculate_due_date($r['tanggal_pinjam'])) ?></td>
                        <td><span class="badge <?= status_class($r['status']) ?>"><?= e(status_label($r['status'])) ?></span></td>
                    </tr>
                <?php endwhile;
                if ($no === 1): ?><tr>
                        <td colspan="6" class="empty">Belum ada transaksi.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php page_end(); ?>