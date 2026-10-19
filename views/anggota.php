<?php
require_once __DIR__ . '/../models/koneksi.php';
require_once __DIR__ . '/../models/auth.php';
require_once __DIR__ . '/layout.php';
require_role('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($action === 'delete' && $id) {
        $s = mysqli_prepare($conn, "SELECT COUNT(*) c 

FROM peminjaman 
WHERE user_id=? 
AND status IN ('pengajuan','disetujui','dipinjam')");
        mysqli_stmt_bind_param($s, 'i', $id);
        mysqli_stmt_execute($s);
        $active = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($s))['c'] ?? 0);

        mysqli_stmt_close($s);
        if ($active) {
            flash('danger', 'Anggota masih memiliki transaksi aktif.');
        } else {
            $s = mysqli_prepare($conn, "DELETE FROM users WHERE id=? AND role='siswa'");
            mysqli_stmt_bind_param($s, 'i', $id);
            mysqli_stmt_execute($s);
            flash('success', 'Anggota dihapus.');
            mysqli_stmt_close($s);
        }
    }
    redirect('anggota.php');
}
$q = trim($_GET['q'] ?? '');
$s = mysqli_prepare($conn, "SELECT u.id,u.username,u.nama_lengkap,u.gmail,u.created_at,COUNT(p.id) transaksi 

FROM users u 
LEFT JOIN peminjaman p 
ON p.user_id=u.id 
WHERE u.role='siswa' 
AND (u.nama_lengkap LIKE CONCAT('%',?,'%') OR u.username LIKE CONCAT('%',?,'%') OR u.gmail LIKE CONCAT('%',?,'%')) 
GROUP BY u.id ORDER BY u.id DESC");

mysqli_stmt_bind_param($s, 'sss', $q, $q, $q);
mysqli_stmt_execute($s);
$rows = mysqli_stmt_get_result($s);

page_start('Kelola User', 'anggota.php', 'admin');
render_flash();

?>
<div class="page-heading">
    <div>
        <h2>Kelola User</h2>
        <p>Daftar akun siswa yang terdaftar.</p>
    </div>
    <a class="btn primary" href="anggota_form.php"><?= icon('plus', 18) ?> Tambah Anggota</a>
</div>
<form class="filter-bar compact" method="get">
    <div class="search-field"><?= icon('search', 19)

                                ?>
        <input name="q" value="<?= e($q) ?>" placeholder="Cari nama user...">
    </div>
    <button class="btn primary">Cari</button>
</form>
<div class="table-card">
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Transaksi</th>
                    <th>Terdaftar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>

                <?php
                $no = 1;
                while ($r = mysqli_fetch_assoc($rows)): ?><tr>
                        <td><?= $no++ ?></td>
                        <td><strong><?= e($r['nama_lengkap']) ?></strong></td>
                        <td>
                            <?= e($r['gmail']) ?></td>
                        <td><?= e($r['username']) ?></td>
                        <td><?= $r['transaksi'] ?></td>
                        <td><?= format_date($r['created_at']) ?></td>
                        <td class="actions">
                            <a class="icon-btn" href="anggota_detail.php?id=<?= $r['id'] ?>" title="Detail"><?= icon('eye', 17) ?></a>
                            <a class="icon-btn" href="anggota_form.php?edit=<?= $r['id'] ?>" title="Edit"><?= icon('edit', 17) ?></a>
                            <form method="post" onsubmit="return confirm('Hapus akun siswa ini?')">
                                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button class="icon-btn danger-text"><?= icon('trash', 17) ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile;
                if ($no === 1): ?>
                    <tr>
                        <td colspan="7" class="empty">Belum ada siswa.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div><?php page_end();
        ?>