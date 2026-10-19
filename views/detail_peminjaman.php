<?php
require_once __DIR__ . '/../models/koneksi.php';
require_once __DIR__ . '/../models/auth.php';
require_once __DIR__ . '/../models/migrate.php';
require_once __DIR__ . '/../models/notifikasi.php';
require_once __DIR__ . '/layout.php';
require_login();
ensure_due_dates_14_days($conn);
ensure_notifikasi_tables($conn);

$id = (int)($_GET['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    http_response_code(405);
    exit('Gunakan tombol Simpan ke Perangkat.');
}
$s = mysqli_prepare($conn, "SELECT p.*,u.nama_lengkap,u.username,u.gmail,b.judul,b.pengarang,b.penerbit,k.Kategori 

FROM peminjaman p
JOIN users u 
ON u.id=p.user_id 
JOIN buku b 
ON b.id=p.buku_id 
LEFT JOIN kategori k 
ON k.id=b.kategori_id 
WHERE p.id=? 
LIMIT 1");

mysqli_stmt_bind_param($s, 'i', $id);
mysqli_stmt_execute($s);
$r = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
mysqli_stmt_close($s);

if (!$r || ($_SESSION['role'] === 'siswa' && (int)$r['user_id'] !== (int)$_SESSION['id'])) {
    http_response_code(404);
    exit('Data tidak ditemukan.');
}
$ret = null;
$s = mysqli_prepare($conn, 'SELECT * FROM pengembalian WHERE peminjaman_id=? ORDER BY tanggal_pengembalian DESC LIMIT 1');

mysqli_stmt_bind_param($s, 'i', $id);

mysqli_stmt_execute($s);
$ret = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
mysqli_stmt_close($s);

page_start('Detail Peminjaman', $_SESSION['role'] === 'admin' ? 'peminjaman.php' : 'riwayat.php', $_SESSION['role']);
render_flash();
?>

<div class="section-head"><a href="<?= e($_SESSION['role'] === 'admin' ? 'peminjaman.php' : 'riwayat.php') ?>"><?= icon('back', 20) ?>
        Kembali</a>
    <h3>Detail
        Peminjaman</h3><span></span>
</div>

<div class="detail-card print-area">
    <div class="detail-head">
        <div class="detail-book-icon"><?= icon('book', 42) ?></div>
        <div>
            <h2><?= e($r['judul']) ?></h2>
            <span class="badge <?= status_class($r['status']) ?>">
                <?= e(status_label($r['status'])) ?></span>
        </div>
    </div>

    <div class="detail-body">
        <div><b>ID Peminjaman</b><span>PM-<?= str_pad((string)$r['id'], 4, '0', STR_PAD_LEFT) ?></span></div>
        <div><b>Peminjam</b><span><?= e($r['nama_lengkap']) ?></span></div>
        <div><b>Username</b><span><?= e($r['username']) ?></span></div>
        <div><b>Email</b><span><?= e($r['gmail'] ?? '-') ?></span></div>
        <div><b>Kategori</b><span><?= e($r['Kategori'] ?? '-') ?></span></div>
        <div><b>Pengarang</b><span><?= e($r['pengarang']) ?></span></div>
        <div><b>Penerbit</b><span><?= e($r['penerbit']) ?></span></div>
        <div><b>Tanggal Pinjam</b><span><?= format_date($r['tanggal_pinjam']) ?></span></div>
        <div><b>Jatuh Tempo</b><span><?= format_date(calculate_due_date($r['tanggal_pinjam'])) ?></span></div>
        <div><b>Status</b><span><?= e(status_label($r['status'])) ?></span></div><?php if ($ret): ?>
            <div><b>Tanggal Pengembalian</b>
                <span><?= format_date($ret['tanggal_pengembalian']) ?></span>
            </div>
            <div><b>Sanksi</b><span><?php
                $late = (int)((strtotime($ret['tanggal_pengembalian']) - strtotime(calculate_due_date($r['tanggal_pinjam']))) / 86400);
                echo $late > 0 ? 'Peminjaman dinonaktifkan 14 hari' : 'Tidak ada';
                ?></span></div>
            <div><b>Keterangan</b><span><?= e($ret['keterangan'] ?? '-') ?></span></div>

        <?php
                                                                                endif; ?>
    </div>
</div>
<div class="detail-actions <?= $_SESSION['role'] === 'admin' ? 'admin-detail-actions' : '' ?>">
    <a class="btn primary" href="download_detail.php?type=loan&id=<?= e($id) ?>"><?= icon('save', 17) ?> Simpan ke Perangkat</a>
    <?php if ($_SESSION['role'] === 'admin'): ?><button class="btn ghost" type="button" onclick="window.print()"><?= icon('print', 17) ?> Print</button><?php endif;
                                                                                                                                                    ?>
    <a class="btn ghost" href="<?= e($_SESSION['role'] === 'admin' ? 'peminjaman.php' : 'riwayat.php') ?>">Kembali</a>
</div>
<?php page_end(); ?>