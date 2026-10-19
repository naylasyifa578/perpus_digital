<?php
require_once __DIR__ . '/../models/koneksi.php';
require_once __DIR__ . '/../models/auth.php';
require_once __DIR__ . '/../models/migrate.php';
require_once __DIR__ . '/../models/notifikasi.php';
require_once __DIR__ . '/../views/layout.php';
ensure_borrow_sanction($conn);
require_role('admin');
ensure_due_dates_14_days($conn);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)$_POST['peminjaman_id'];
    $tanggal = $_POST['tanggal_pengembalian'] ?? date('Y-m-d');
    $denda = 0.0;
    $ket = trim($_POST['keterangan'] ?? '');
    $today = date('Y-m-d');
    if ($tanggal > $today) {
        flash('danger', 'Tanggal pengembalian tidak boleh melebihi hari ini.');
        redirect('pengembalian.php');
    }
    mysqli_begin_transaction($conn);
    $s = mysqli_prepare($conn, "SELECT * FROM peminjaman WHERE id=? AND status='dipinjam' FOR UPDATE");
    mysqli_stmt_bind_param($s, 'i', $id);
    mysqli_stmt_execute($s);
    $p = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
    mysqli_stmt_close($s);
    if (!$p) {
        mysqli_rollback($conn);
        flash('danger', 'Peminjaman tidak aktif atau sudah dikembalikan.');
    } else {
        if ($tanggal < $p['tanggal_pinjam']) {
            mysqli_rollback($conn);
            flash('danger', 'Tanggal pengembalian tidak boleh sebelum tanggal pinjam.');
            redirect('pengembalian.php');
        }
        $s = mysqli_prepare($conn, 'INSERT INTO pengembalian(id_user,peminjaman_id,tanggal_pengembalian,denda,keterangan) VALUES(?,?,?,?,?)');
        $userId = (int)$p['user_id'];
        mysqli_stmt_bind_param($s, 'iisds', $userId, $id, $tanggal, $denda, $ket);
        $ok = mysqli_stmt_execute($s);
        mysqli_stmt_close($s);
        if ($ok) {
            $s = mysqli_prepare($conn, "UPDATE peminjaman SET status='dikembalikan' WHERE id=?");
            mysqli_stmt_bind_param($s, 'i', $id);
            mysqli_stmt_execute($s);
            mysqli_stmt_close($s);
            $s = mysqli_prepare($conn, 'UPDATE buku SET jumlah_stok=jumlah_stok+1 WHERE id=?');
            mysqli_stmt_bind_param($s, 'i', $p['buku_id']);
            mysqli_stmt_execute($s);
            mysqli_stmt_close($s);
            $late = apply_late_return_sanction($conn, (int)$p['user_id'], calculate_due_date($p['tanggal_pinjam']), $tanggal);
            mysqli_commit($conn);
            if ($late > 0) {
                $until = date('Y-m-d', strtotime($tanggal . ' +14 days'));
                create_notification($conn, (int)$p['user_id'], 'sanksi', 'Terdeteksi Keterlambatan', 'Pengembalian buku kamu terdeteksi terlambat, peminjaman dinonaktifkan selama 14 hari sampai ' . format_date($until) . '.', (int)$id);
                flash('success', 'Pengembalian berhasil. Peminjam terlambat ' . $late . ' hari dan otomatis dikenai sanksi peminjaman selama 14 hari.');
            } else {
                create_notification($conn, (int)$p['user_id'], 'pengembalian', 'Buku Berhasil Dikembalikan', 'Pengembalian buku berhasil dicatat.', (int)$id);
                flash('success', 'Pengembalian berhasil dicatat.');
            }
        } else {
            mysqli_rollback($conn);
            flash('danger', 'Gagal mencatat pengembalian: ' . mysqli_error($conn));
        }
    }
    redirect('pengembalian.php');
}
$rows = mysqli_query($conn, "SELECT p.id,p.tanggal_pinjam,p.tanggal_kembali,u.nama_lengkap,b.judul FROM peminjaman p JOIN users u ON u.id=p.user_id JOIN buku b ON b.id=p.buku_id WHERE p.status='dipinjam' ORDER BY p.tanggal_kembali ASC");
page_start('Pengembalian', 'pengembalian.php', 'admin');
render_flash(); ?><div class="page-heading">
    <div>
        <h2>Pengembalian</h2>
        <p>Jatuh tempo otomatis 14 hari setelah tanggal pinjam. Keterlambatan otomatis dikenai sanksi 14 hari.</p>
    </div>
</div>
<div class="table-card">
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Peminjam</th>
                    <th>Buku</th>
                    <th>Jatuh Tempo</th>
                    <th>Form Pengembalian</th>
                </tr>
            </thead>
            <tbody><?php $no = 1;
                    while ($r = mysqli_fetch_assoc($rows)): ?><tr>
                        <td><?= $no++ ?></td>
                        <td><?= e($r['nama_lengkap']) ?></td>
                        <td><?= e($r['judul']) ?></td>
                        <td><?= format_date(calculate_due_date($r['tanggal_pinjam'])) ?></td>
                        <td>
                            <form method="post" class="return-form" data-due-date="<?= e(calculate_due_date($r['tanggal_pinjam'])) ?>">
                                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                <input type="hidden" name="peminjaman_id" value="<?= $r['id'] ?>">
                                <label class="sr-only">Tanggal Pengembalian</label>
                                <input type="date" name="tanggal_pengembalian" class="return-date-input" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                                <span class="return-sanction-label" aria-live="polite">Sanksi: Tidak ada</span>
                                <input name="keterangan" placeholder="Keterangan">
                                <button class="btn primary small" type="submit">Proses</button>
                            </form>
                        </td>
                    </tr><?php endwhile;
                        if ($no === 1): ?><tr>
                        <td colspan="5" class="empty">Tidak ada buku yang sedang dipinjam.</td>
                    </tr><?php endif; ?></tbody>
        </table>
    </div>
</div>
<script>
document.querySelectorAll('.return-form').forEach(function(form) {
    const input = form.querySelector('.return-date-input');
    const due = form.dataset.dueDate;
    const label = form.querySelector('.return-sanction-label');
    function refreshReturnState() {
        const late = input.value && due && input.value > due;
        input.classList.toggle('late-date', !!late);
        label.textContent = late ? 'Sanksi: Aktif 14 hari' : 'Sanksi: Tidak ada';
        label.classList.toggle('late', !!late);
    }
    input.addEventListener('input', refreshReturnState);
    input.addEventListener('change', refreshReturnState);
    refreshReturnState();
});
</script>
<?php page_end(); ?>