<?php
require_once __DIR__ . '/../models/koneksi.php';
require_once __DIR__ . '/../models/auth.php';
require_once __DIR__ . '/../models/migrate.php';
require_once __DIR__ . '/../models/notifikasi.php';
require_once __DIR__ . '/layout.php';
require_login();
ensure_due_dates_14_days($conn);
ensure_notifikasi_tables($conn);
if (($_SESSION['role'] ?? '') === 'siswa') {
    ensure_collection_table($conn);
    ensure_borrow_sanction($conn);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $role = $_SESSION['role'];
    if ($action === 'collection' && $role === 'siswa' && $id) {
        $mode = $_POST['mode'] ?? 'save';
        if ($mode === 'save') {
            $s = mysqli_prepare($conn, 'INSERT IGNORE INTO koleksi_buku(user_id,buku_id) VALUES(?,?)');
            mysqli_stmt_bind_param($s, 'ii', $_SESSION['id'], $id);
            $ok = mysqli_stmt_execute($s);
            mysqli_stmt_close($s);
            flash($ok ? 'success' : 'danger', $ok ? 'Buku disimpan ke koleksi.' : 'Gagal menyimpan buku ke koleksi.');
        } else {
            $s = mysqli_prepare($conn, 'DELETE FROM koleksi_buku WHERE user_id=? AND buku_id=?');
            mysqli_stmt_bind_param($s, 'ii', $_SESSION['id'], $id);
            $ok = mysqli_stmt_execute($s);
            mysqli_stmt_close($s);
            flash($ok ? 'success' : 'danger', $ok ? 'Buku dihapus dari koleksi.' : 'Gagal menghapus buku dari koleksi.');
        }
        redirect($_POST['redirect'] ?? 'kategori_siswa.php');
    }
    if ($action === 'cancel' && $role === 'siswa' && $id) {
        $s = mysqli_prepare($conn, "SELECT status FROM peminjaman WHERE id=? AND user_id=? LIMIT 1");
        mysqli_stmt_bind_param($s, 'ii', $id, $_SESSION['id']);
        mysqli_stmt_execute($s);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
        mysqli_stmt_close($s);
        if (!$row) {
            flash('danger', 'Transaksi tidak ditemukan.');
        } elseif (!in_array($row['status'], ['pengajuan', 'disetujui'], true)) {
            flash('danger', 'Peminjaman ini tidak dapat dibatalkan.');
        } else {
            $s = mysqli_prepare($conn, "UPDATE peminjaman SET status='dibatalkan' WHERE id=? AND user_id=? AND status IN ('pengajuan','disetujui')");
            mysqli_stmt_bind_param($s, 'ii', $id, $_SESSION['id']);
            $ok = mysqli_stmt_execute($s);
            mysqli_stmt_close($s);
            flash($ok ? 'success' : 'danger', $ok ? 'Pengajuan peminjaman berhasil dibatalkan.' : 'Gagal membatalkan peminjaman.');
        }
        redirect('riwayat.php');
    }
    if ($action === 'request' && $role === 'siswa') {
        $buku = (int)($_POST['buku_id'] ?? 0);
        $tanggalPinjam = $_POST['tanggal_pinjam'] ?? date('Y-m-d');
        $today = date('Y-m-d');
        $kembali = calculate_due_date($tanggalPinjam);
        $blocked = get_borrow_block_until($conn, (int)$_SESSION['id']);
        if ($blocked && $blocked >= $today) {
            flash('danger', 'Peminjaman Anda sedang dibatasi sampai ' . format_date($blocked) . '.');
            redirect('riwayat.php');
        }
        if ($tanggalPinjam < $today) {
            flash('danger', 'Tanggal pinjam tidak boleh sebelum hari ini.');
            redirect('kategori_siswa.php');
        }
        if ($kembali <= $tanggalPinjam) {
            flash('danger', 'Tanggal jatuh tempo tidak valid.');
            redirect('kategori_siswa.php');
        }
        $s = mysqli_prepare($conn, "SELECT jumlah_stok FROM buku WHERE id=?");
        mysqli_stmt_bind_param($s, 'i', $buku);
        mysqli_stmt_execute($s);
        $book = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
        mysqli_stmt_close($s);
        if (!$book || $book['jumlah_stok'] < 1) {
            flash('danger', 'Stok buku tidak tersedia.');
        } else {
            $s = mysqli_prepare($conn, "SELECT id FROM peminjaman WHERE user_id=? AND buku_id=? AND status IN ('pengajuan','disetujui','dipinjam') LIMIT 1");
            mysqli_stmt_bind_param($s, 'ii', $_SESSION['id'], $buku);
            mysqli_stmt_execute($s);
            $exists = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
            mysqli_stmt_close($s);
            if ($exists) flash('danger', 'Anda sudah memiliki pengajuan aktif untuk buku ini.');
            else {
                $ket = trim($_POST['keterangan'] ?? '');
                $s = mysqli_prepare($conn, "INSERT INTO peminjaman(user_id,buku_id,tanggal_pinjam,tanggal_kembali,status) VALUES(?,?,?,?, 'pengajuan')");
                mysqli_stmt_bind_param($s, 'iiss', $_SESSION['id'], $buku, $tanggalPinjam, $kembali);
                if (mysqli_stmt_execute($s)) {
                    $newLoanId = mysqli_insert_id($conn);
                    create_notification($conn, (int)$_SESSION['id'], 'peminjaman', 'Pengajuan Peminjaman Dikirim', 'Pengajuan peminjaman buku berhasil dikirim dan menunggu proses admin.', (int)$newLoanId);
                    notify_admins($conn, 'peminjaman', 'Pengajuan Peminjaman Baru', 'Ada pengajuan peminjaman baru dari ' . ($_SESSION['nama_lengkap'] ?? 'siswa') . '.', (int)$newLoanId);
                    flash('success', 'Pengajuan peminjaman dikirim.');
                } else flash('danger', 'Pengajuan gagal: ' . mysqli_stmt_error($s));
                mysqli_stmt_close($s);
            }
        }
    }
    if ($action === 'return' && $role === 'siswa' && $id) {
        $tanggal = date('Y-m-d');
        mysqli_begin_transaction($conn);
        $s = mysqli_prepare($conn, "SELECT * FROM peminjaman WHERE id=? AND user_id=? AND status='dipinjam' FOR UPDATE");
        mysqli_stmt_bind_param($s, 'ii', $id, $_SESSION['id']);
        mysqli_stmt_execute($s);
        $p = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
        mysqli_stmt_close($s);
        if (!$p) {
            mysqli_rollback($conn);
            flash('danger', 'Transaksi tidak ditemukan atau sudah dikembalikan.');
        } else {
            if ($tanggal < $p['tanggal_pinjam']) {
                mysqli_rollback($conn);
                flash('danger', 'Buku belum masuk tanggal pinjam yang dipilih.');
                redirect('riwayat.php');
            }
            $denda = 0.0;
            $s = mysqli_prepare($conn, 'INSERT INTO pengembalian(id_user,peminjaman_id,tanggal_pengembalian,denda,keterangan) VALUES(?,?,?,?,?)');
            $ket = 'Pengembalian oleh siswa';
            $userId = (int)$_SESSION['id'];
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
                    $_SESSION['sanction_notice'] = true;
                    $until = date('Y-m-d', strtotime($tanggal . ' +14 days'));
                    create_notification($conn, (int)$p['user_id'], 'sanksi', 'Terdeteksi Keterlambatan', 'Pengembalian buku kamu terdeteksi terlambat, peminjaman dinonaktifkan selama 14 hari sampai ' . format_date($until) . '.', (int)$id);
                    flash('success', 'Buku berhasil dikembalikan. Sanksi peminjaman aktif selama 14 hari.');
                } else {
                    create_notification($conn, (int)$p['user_id'], 'pengembalian', 'Buku Berhasil Dikembalikan', 'Pengembalian buku berhasil dicatat.', (int)$id);
                    flash('success', 'Buku berhasil dikembalikan.');
                }
            } else {
                mysqli_rollback($conn);
                flash('danger', 'Gagal mencatat pengembalian.');
            }
        }
    }
    if ($action === 'status' && $role === 'admin' && $id) {
        $new = $_POST['status'] ?? '';
        $allowed = ['disetujui', 'dipinjam', 'ditolak'];
        $s = mysqli_prepare($conn, 'SELECT * FROM peminjaman WHERE id=? FOR UPDATE');
        mysqli_begin_transaction($conn);
        mysqli_stmt_bind_param($s, 'i', $id);
        mysqli_stmt_execute($s);
        $p = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
        mysqli_stmt_close($s);
        $ok = true;
        if (!$p || !in_array($new, $allowed, true)) $ok = false;
        $transitions = ['pengajuan' => ['disetujui', 'dipinjam', 'ditolak'], 'disetujui' => ['dipinjam', 'ditolak']];
        if ($ok && !in_array($new, $transitions[$p['status']] ?? [], true)) $ok = false;
        if ($ok && $new === 'dipinjam' && $p['status'] !== 'dipinjam' && $p['tanggal_pinjam'] > date('Y-m-d')) {
            $ok = false;
        }
        if ($ok && $new === 'dipinjam' && $p['status'] !== 'dipinjam') {
            $u = mysqli_prepare($conn, 'UPDATE buku SET jumlah_stok=jumlah_stok-1 WHERE id=? AND jumlah_stok>0');
            mysqli_stmt_bind_param($u, 'i', $p['buku_id']);
            mysqli_stmt_execute($u);
            $ok = mysqli_stmt_affected_rows($u) === 1;
            mysqli_stmt_close($u);
        }
        if ($ok) {
            $u = mysqli_prepare($conn, 'UPDATE peminjaman SET status=? WHERE id=?');
            mysqli_stmt_bind_param($u, 'si', $new, $id);
            mysqli_stmt_execute($u);
            mysqli_stmt_close($u);
            mysqli_commit($conn);
            if ($new === 'disetujui') create_notification($conn, (int)$p['user_id'], 'peminjaman', 'Peminjaman Disetujui', 'Pengajuan peminjaman kamu telah disetujui.', (int)$id);
            elseif ($new === 'ditolak') create_notification($conn, (int)$p['user_id'], 'peminjaman', 'Peminjaman Ditolak', 'Pengajuan peminjaman kamu ditolak oleh admin.', (int)$id);
            elseif ($new === 'dipinjam') create_notification($conn, (int)$p['user_id'], 'peminjaman', 'Buku Ditandai Dipinjam', 'Status peminjaman buku kamu telah diperbarui menjadi dipinjam.', (int)$id);
            flash('success', 'Status peminjaman diperbarui.');
        } else {
            mysqli_rollback($conn);
            if ($p && $new === 'dipinjam' && $p['tanggal_pinjam'] > date('Y-m-d')) {
                flash('danger', 'Buku baru dapat ditandai dipinjam mulai tanggal pinjam.');
            } else {
                flash('danger', 'Perubahan status tidak dapat dilakukan.');
            }
        }
    }
    redirect($role === 'admin' ? 'peminjaman.php' : 'riwayat.php');
}
$role = $_SESSION['role'];
$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$where = $role === 'admin' ? '1' : 'p.user_id=?';
$types = $role === 'admin' ? '' : 'i';
$params = $role === 'admin' ? [] : [$_SESSION['id']];
if ($q !== '') {
    $where .= " AND (u.nama_lengkap LIKE CONCAT('%',?,'%') OR b.judul LIKE CONCAT('%',?,'%'))";
    $types .= 'ss';
    array_push($params, $q, $q);
}
if (in_array($status, ['pengajuan', 'disetujui', 'dipinjam', 'dikembalikan', 'ditolak', 'dibatalkan'], true)) {
    $where .= ' AND p.status=?';
    $types .= 's';
    $params[] = $status;
}
$sql = "SELECT p.*,u.nama_lengkap,b.judul, pg.tanggal_pengembalian AS tanggal_pengembalian_aktual FROM peminjaman p JOIN users u ON u.id=p.user_id JOIN buku b ON b.id=p.buku_id LEFT JOIN (SELECT peminjaman_id, MAX(tanggal_pengembalian) AS tanggal_pengembalian FROM pengembalian GROUP BY peminjaman_id) pg ON pg.peminjaman_id=p.id WHERE $where ORDER BY p.id DESC";
$s = mysqli_prepare($conn, $sql);
if ($types) stmt_bind($s, $types, $params);
mysqli_stmt_execute($s);
$rows = mysqli_stmt_get_result($s);
page_start($role === 'admin' ? 'Peminjaman' : 'Riwayat', $role === 'admin' ? 'peminjaman.php' : 'riwayat.php', $role);
?>
<?php render_flash(); ?><div class="page-heading">
    <div>
        <h2><?= $role === 'admin' ? 'Peminjaman' : 'Riwayat Peminjaman' ?></h2>
        <p><?= $role === 'admin' ? 'Kelola pengajuan dan status peminjaman.' : 'Lihat status buku yang Anda pinjam.' ?></p>
    </div>
</div>
<form class="filter-bar" method="get">
    <div class="search-field"><?= icon('search', 19) ?><input name="q" value="<?= e($q) ?>" placeholder="Cari nama buku..."></div><select name="status">
        <option value="">Semua Status</option><?php foreach (['pengajuan', 'disetujui', 'dipinjam', 'dikembalikan', 'ditolak', 'dibatalkan'] as $st): ?><option value="<?= $st ?>" <?= $status === $st ? 'selected' : '' ?>><?= e(status_label($st)) ?></option><?php endforeach; ?>
    </select><button class="btn primary">Cari</button>
</form>
<div class="table-card">
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>No</th><?php if ($role === 'admin'): ?><th>Nama Peminjam</th><?php endif; ?><th>Nama Buku</th>
                    <th>Tanggal Pinjam</th>
                    <th>Tanggal Pengembalian</th>
                    <th>Status</th><?php if ($role === 'siswa'): ?><th>Aksi</th><?php endif; ?><?php if ($role === 'admin'): ?><th>Aksi</th><?php endif; ?>
                </tr>
            </thead>
            <tbody><?php $no = 1;
                    while ($r = mysqli_fetch_assoc($rows)): ?><tr>
                        <td><?= $no++ ?></td><?php if ($role === 'admin'): ?><td><?= e($r['nama_lengkap']) ?></td><?php endif; ?><td><a class="icon-btn" href="detail_peminjaman.php?id=<?= $r['id'] ?>" title="Detail"><?= icon('eye', 17) ?></a> <?= e($r['judul']) ?></td>
                        <td><?= format_date($r['tanggal_pinjam']) ?></td>
                        <td><?= !empty($r['tanggal_pengembalian_aktual']) ? format_date($r['tanggal_pengembalian_aktual']) : format_date(calculate_due_date($r['tanggal_pinjam'])) ?></td>
                        <td><span class="badge <?= status_class($r['status']) ?>"><?= e(status_label($r['status'])) ?></span></td><?php if ($role === 'siswa'): ?><td>
                                <div class="actions">
                                    <?php if (in_array($r['status'], ['pengajuan', 'disetujui'], true)): ?><form method="post" onsubmit="return confirm('Batalkan pengajuan peminjaman ini?')"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn danger-outline small" type="submit">Batalkan</button></form><?php endif; ?>
                                    <?php if ($r['status'] === 'dipinjam'): ?><form method="post" onsubmit="return confirm('Kembalikan buku ini?')"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="return"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn primary small" type="submit">Kembalikan</button></form><?php endif; ?>
                                    <?php if (!in_array($r['status'], ['pengajuan', 'disetujui', 'dipinjam'], true)): ?>—<?php endif; ?></div>
                            </td><?php endif; ?><?php if ($role === 'admin'): ?><td>
                                <form method="post" class="inline-form"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= $r['id'] ?>"><select name="status">
                                        <option value="">Pilih aksi</option><?php foreach (['disetujui', 'dipinjam', 'ditolak'] as $st): ?><option value="<?= $st ?>"><?= e(status_label($st)) ?></option><?php endforeach; ?>
                                    </select><button class="btn primary small">Update</button></form>
                            </td><?php endif; ?>
                    </tr><?php endwhile;
                        if ($no === 1): ?><tr>
                        <td colspan="8" class="empty">Belum ada transaksi.</td>
                    </tr><?php endif; ?></tbody>
        </table>
    </div>
</div><?php page_end(); ?>