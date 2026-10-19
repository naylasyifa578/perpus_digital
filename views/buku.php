<?php
require_once __DIR__ . '/../models/koneksi.php';
require_once __DIR__ . '/../models/auth.php';
require_once __DIR__ . '/../models/uploads.php';
require_once __DIR__ . '/layout.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($action === 'delete' && $id) {
        $get = mysqli_prepare($conn, 'SELECT gambar FROM buku WHERE id=?');
        mysqli_stmt_bind_param($get, 'i', $id);
        mysqli_stmt_execute($get);
        $oldRow = mysqli_fetch_assoc(mysqli_stmt_get_result($get));
        mysqli_stmt_close($get);
        $st = mysqli_prepare($conn, 'DELETE FROM buku WHERE id=?');
        mysqli_stmt_bind_param($st, 'i', $id);
        if (mysqli_stmt_execute($st)) {
            if (!empty($oldRow['gambar'])) delete_uploaded_image($oldRow['gambar'], __DIR__ . '/../assets/covers');
            flash('success', 'Buku berhasil dihapus.');
        } else flash('danger', 'Buku tidak dapat dihapus.');
        mysqli_stmt_close($st);
    }
    redirect('buku.php');
}

$q = trim($_GET['q'] ?? '');
$cat = (int)($_GET['kategori'] ?? 0);
$cats = mysqli_query($conn, 'SELECT id,Kategori FROM kategori ORDER BY Kategori');
$sql = 'SELECT b.*,k.Kategori FROM buku b LEFT JOIN kategori k ON k.id=b.kategori_id WHERE 1';
$types = '';
$params = [];
if ($q !== '') {
    $sql .= ' AND (b.judul LIKE CONCAT("%",? ,"%") OR b.pengarang LIKE CONCAT("%",? ,"%") OR b.isbn LIKE CONCAT("%",? ,"%"))';
    $types .= 'sss';
    array_push($params, $q, $q, $q);
}
if ($cat) {
    $sql .= ' AND b.kategori_id=?';
    $types .= 'i';
    $params[] = $cat;
}
$sql .= ' ORDER BY b.id DESC';
$st = mysqli_prepare($conn, $sql);
if ($types) stmt_bind($st, $types, $params);
mysqli_stmt_execute($st);
$rows = mysqli_stmt_get_result($st);

page_start('Data Buku', 'buku.php', 'admin');
render_flash();
?>
<div class="page-heading">
    <div>
        <h2>Data Buku</h2>
        <p>Kelola koleksi buku perpustakaan.</p>
    </div>
    <a class="btn primary" href="buku_form.php"><?= icon('plus', 18) ?> Tambah Data</a>
</div>

<form class="filter-bar" method="get">
    <div class="search-field"><?= icon('search', 19) ?><input name="q" value="<?= e($q) ?>" placeholder="Cari nama buku..."></div>
    <select name="kategori">
        <option value="0">Semua Kategori</option><?php while ($c = mysqli_fetch_assoc($cats)): ?><option value="<?= $c['id'] ?>" <?= $cat === $c['id'] ? 'selected' : '' ?>><?= e($c['Kategori']) ?></option><?php endwhile; ?>
    </select>
    <button class="btn primary" type="submit">Cari</button>
</form>

<div class="table-card">
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Cover</th>
                    <th>Nama Buku</th>
                    <th>Kategori</th>
                    <th>Pengarang</th>
                    <th>Tahun</th>
                    <th>Stok</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1;
                while ($r = mysqli_fetch_assoc($rows)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?php if (!empty($r['gambar'])): ?><img class="admin-book-thumb" src="<?= preg_match('~^https?://~i', $r['gambar']) ? e($r['gambar']) : '../assets/' . e($r['gambar']) ?>" alt="Cover buku"><?php else: ?><span class="admin-book-placeholder"><?= icon('book', 20) ?></span><?php endif; ?></td>
                        <td><strong><?= e($r['judul']) ?></strong><small class="muted block"><?= e($r['isbn'] ?: '-') ?></small></td>
                        <td><?= e($r['Kategori'] ?? '-') ?></td>
                        <td><?= e($r['pengarang']) ?></td>
                        <td><?= e($r['tahun_terbit']) ?></td>
                        <td><span class="stock"><?= $r['jumlah_stok'] ?></span></td>
                        <td class="actions">
                            <a class="icon-btn" href="buku_form.php?edit=<?= $r['id'] ?>" title="Edit"><?= icon('edit', 17) ?></a>
                            <form method="post" onsubmit="return confirm('Hapus buku ini?')"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button class="icon-btn danger-text" title="Hapus"><?= icon('trash', 17) ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile;
                if ($no === 1): ?><tr>
                        <td colspan="8" class="empty">Data buku tidak ditemukan.</td>
                    </tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
page_end();
?>