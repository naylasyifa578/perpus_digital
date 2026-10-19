<?php
require_once __DIR__ . '/../models/koneksi.php';
require_once __DIR__ . '/../models/auth.php';
require_once __DIR__ . '/../models/migrate.php';
ensure_category_image_column($conn);
require_once __DIR__ . '/layout.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'save') {
        $nama = trim($_POST['Kategori'] ?? '');
        $desc = trim($_POST['Deskripsi'] ?? '');
        $gambar = trim($_POST['gambar_lama'] ?? '');
        if (!empty($_FILES['gambar']['name']) && is_uploaded_file($_FILES['gambar']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];
            if (in_array($ext, $allowed, true)) {
                $safe = 'kategori_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                $target = __DIR__ . '/../assets/' . $safe;
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target)) $gambar = $safe;
            }
        }

        if ($nama === '') {
            flash('danger', 'Nama kategori wajib diisi.');
        } else {
            if ($id) {
                $s = mysqli_prepare($conn, 'UPDATE kategori SET Kategori=?,Deskripsi=?,gambar=? WHERE id=?');

                mysqli_stmt_bind_param($s, 'sssi', $nama, $desc, $gambar, $id);
            } else {
                $s = mysqli_prepare($conn, 'INSERT INTO kategori (Kategori,Deskripsi,gambar) VALUES (?,?,?)');

                mysqli_stmt_bind_param($s, 'sss', $nama, $desc, $gambar);
            }
            mysqli_stmt_execute($s);
            flash(mysqli_stmt_errno($s) ? 'danger' : 'success', mysqli_stmt_errno($s) ? mysqli_stmt_error($s) : ($id ? 'Kategori diperbarui.' : 'Kategori ditambahkan.'));
            mysqli_stmt_close($s);
        }
    }
    if ($action === 'delete' && $id) {
        $s = mysqli_prepare($conn, 'DELETE FROM kategori WHERE id=?');
        mysqli_stmt_bind_param($s, 'i', $id);
        if (mysqli_stmt_execute($s)) flash('success', 'Kategori dihapus.');
        else flash('danger', 'Kategori masih dipakai oleh buku.');
        mysqli_stmt_close($s);
    }
    redirect('kategori.php');
}
$q = trim($_GET['q'] ?? '');

$sql = 'SELECT k.*,COUNT(b.id) 
jumlah_buku FROM kategori k LEFT JOIN buku b ON b.kategori_id=k.id WHERE k.Kategori LIKE CONCAT("%",?,"%") GROUP BY k.id ORDER BY k.id';

$s = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($s, 's', $q);
mysqli_stmt_execute($s);
$rows = mysqli_stmt_get_result($s);
$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $s2 = mysqli_prepare($conn, 'SELECT * FROM kategori WHERE id=?');
    mysqli_stmt_bind_param($s2, 'i', $id);
    mysqli_stmt_execute($s2);
    $edit = mysqli_fetch_assoc(mysqli_stmt_get_result($s2));
    mysqli_stmt_close($s2);
}

page_start('Kategori', 'kategori.php', 'admin');
render_flash();
?>

<div class="page-heading">
    <div>
        <h2>Kategori</h2>
        <p>Kelola kategori buku.</p>
    </div>
</div>
<div class="two-col">
    <div>
        <form class="filter-bar compact" method="get">
            <div class="search-field">
                <?= icon('search', 19) ?>
                <input name="q" value="<?= e($q) ?>" placeholder="Cari kategori...">
            </div>
            <button class="btn primary" type="submit">Cari</button>
        </form>
        <div class="table-card">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Foto</th>
                            <th>Kategori</th>
                            <th>Deskripsi</th>
                            <th>Jumlah Buku</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                        while ($r = mysqli_fetch_assoc($rows)): ?><tr>
                                <td><?= $no++ ?></td>
                                <td><?php if (!empty($r['gambar'])): ?><img class="category-admin-thumb" src="../assets/<?= e($r['gambar']) ?>" alt="Foto kategori"><?php else: ?>—<?php endif; ?></td>
                                <td><strong><?= e($r['Kategori']) ?></strong></td>
                                <td><?= e($r['Deskripsi']) ?>
                                </td>
                                <td><?= $r['jumlah_buku'] ?></td>
                                <td class="actions">
                                    <a class="icon-btn" href="?edit=<?= $r['id'] ?>"><?= icon('edit', 17) ?></a>
                                    <form method="post" onsubmit="return confirm('Hapus kategori ini?')">
                                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                        <button class="icon-btn danger-text"><?= icon('trash', 17)
                                                                                ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="form-card">
        <div class="form-card-title">
            <h3><?= $edit ? 'Edit Kategori' : 'Tambah Kategori' ?></h3>
        </div>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">
            <input type="hidden" name="gambar_lama" value="<?= e($edit['gambar'] ?? "") ?>">
            <label>Nama Kategori</label>
            <input name="Kategori" value="<?= e($edit['Kategori'] ?? '') ?>" required>
            <label>Foto Kategori <span class="muted">(Opsional)</span></label>
            <input type="file" name="gambar" accept="image/jpeg,image/png,image/webp">
            <small class="muted">Contoh: kategori Agama dapat menggunakan foto masjid. Kamu bisa menggantinya kapan saja.</small>
            <label>Deskripsi</label>
            <textarea name="Deskripsi" rows="5" placeholder="Deskripsi kategori">
                    <?= e($edit['Deskripsi'] ?? '') ?>
                </textarea>
            <div class="form-actions">
                <a class="btn ghost" href="kategori.php">Batal</a>
                <button class="btn primary">
                    <?= $edit ? 'Update' : 'Simpan' ?>
                </button>
            </div>
        </form>
    </div>
</div>
<?php page_end();
?>