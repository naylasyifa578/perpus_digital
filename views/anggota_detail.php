<?php
require_once __DIR__ . '/../models/koneksi.php';
require_once __DIR__ . '/../models/auth.php';
require_once __DIR__ . '/../models/migrate.php';
require_once __DIR__ . '/../models/notifikasi.php';
require_once __DIR__ . '/layout.php';
require_role('admin');
ensure_due_dates_14_days($conn);
ensure_notifikasi_tables($conn);
$id = (int)($_GET['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    http_response_code(405);
    exit('Gunakan tombol Simpan ke Perangkat.');
}
$s = mysqli_prepare($conn, "SELECT id,username,nama_lengkap,role,created_at,gmail,foto_profil,blocked_until FROM users WHERE id=? AND role='siswa' LIMIT 1");
mysqli_stmt_bind_param($s, 'i', $id);
mysqli_stmt_execute($s);
$u = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
mysqli_stmt_close($s);

if (!$u) {
    http_response_code(404);
    exit('Data user tidak ditemukan.');
}
$s = mysqli_prepare($conn, 'SELECT p.*,b.judul FROM peminjaman p JOIN buku b ON b.id=p.buku_id WHERE p.user_id=? ORDER BY p.id DESC LIMIT 10');
mysqli_stmt_bind_param($s, 'i', $id);
mysqli_stmt_execute($s);
$hist = mysqli_stmt_get_result($s);

page_start('Detail User', 'anggota.php', 'admin');
render_flash();
?>
<div class="section-head"><a href="anggota.php"><?= icon('back', 20) ?> Kembali</a>
    <h3>Detail User</h3><span></span>
</div>
<div class="profile-layout">
    <div class="profile-card">
        <?php
        if (!empty($u['foto_profil'])):
        ?>
            <div class="profile-avatar large"><img src="../assets/<?= e($u['foto_profil']) ?>" alt="Foto profil"></div>
        <?php
        else: ?><div class="profile-avatar large">
                <?= strtoupper(substr($u['nama_lengkap'], 0, 1)) ?></div>
        <?php endif; ?>
        <h2><?= e($u['nama_lengkap']) ?></h2>
        <p><?= e($u['username']) ?></p>
    </div>
    <div class="profile-panels">
        <div class="detail-card">
            <div class="detail-body">
                <div><b>Nama Lengkap</b> <span><?= e($u['nama_lengkap']) ?></span></div>
                <div><b>Username</b><span><?= e($u['username']) ?></span></div>
                <div><b>Email</b><span><?= e($u['gmail'] ?? '-') ?></span></div>
                <div><b>Terdaftar</b><span><?= format_date($u['created_at']) ?></span></div>
                <div><b>Status Peminjaman</b><span>
                        <?= $u['blocked_until'] && $u['blocked_until'] >= date('Y-m-d') ? 'Dibatasi sampai ' . format_date($u['blocked_until']) : 'Aktif' ?></span></div>
            </div>
        </div>
        <div class="detail-actions">
            <a class="btn outline" href="anggota_form.php?edit=<?= e($id) ?>"><?= icon('edit', 17) ?> Edit Anggota</a>
            <a class="btn primary" href="download_detail.php?type=user&id=<?= e($id) ?>">
                <?= icon('save', 17) ?> Simpan ke Perangkat</a>
        </div>
    </div>
</div>
<div class="table-card user-history-card">
    <div class="section-head">
        <h3>Riwayat Peminjaman</h3><span></span>
    </div>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Buku</th>
                    <th>Tanggal Pinjam</th>
                    <th>Jatuh Tempo</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>

                <?php while ($h = mysqli_fetch_assoc($hist)): ?><tr>
                        <td><?= e($h['judul']) ?></td>
                        <td><?= format_date($h['tanggal_pinjam']) ?></td>
                        <td><?= format_date(calculate_due_date($h['tanggal_pinjam'])) ?></td>
                        <td><span class="badge <?= status_class($h['status']) ?>"><?= e(status_label($h['status'])) ?></span></td>
                    </tr>

                <?php endwhile; ?></tbody>
        </table>
    </div>
</div><?php page_end(); ?>