<?php
require_once __DIR__ . '/../models/koneksi.php';
require_once __DIR__ . '/../models/auth.php';
require_once __DIR__ . '/../models/uploads.php';
require_once __DIR__ . '/layout.php';
require_role('admin');

$id = (int)($_GET['edit'] ?? $_POST['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($action === 'save') {
        $judul = trim($_POST['judul'] ?? '');
        $pengarang = trim($_POST['pengarang'] ?? '');
        $penerbit = trim($_POST['penerbit'] ?? '');
        $tahun = (int)($_POST['tahun_terbit'] ?? 0);
        $isbn = trim($_POST['isbn'] ?? '');
        $stok = max(0, (int)($_POST['jumlah_stok'] ?? 0));
        $kategori = (int)($_POST['kategori_id'] ?? 0);
        $oldCover = null;
        if ($id) {
            $get = mysqli_prepare($conn, 'SELECT gambar FROM buku WHERE id=?');
            mysqli_stmt_bind_param($get, 'i', $id);
            mysqli_stmt_execute($get);
            $oldRow = mysqli_fetch_assoc(mysqli_stmt_get_result($get));
            mysqli_stmt_close($get);
            if (!$oldRow) {
                flash('danger', 'Data buku tidak ditemukan.');
                redirect('buku.php');
            }
            $oldCover = $oldRow['gambar'] ?? null;
        }
        if ($judul === '' || $pengarang === '' || $penerbit === '' || $tahun < 1000 || $kategori < 1) {
            flash('danger', 'Data buku belum lengkap.');
        } else {
            try {
                $newCover = $oldCover;
                if (!empty($_FILES['cover']['name'])) $newCover = upload_book_cover($_FILES['cover'], null);
                if ($id) {
                    $st = mysqli_prepare($conn, 'UPDATE buku SET judul=?,pengarang=?,penerbit=?,tahun_terbit=?,isbn=?,jumlah_stok=?,kategori_id=?,gambar=? WHERE id=?');
                    mysqli_stmt_bind_param($st, 'sssisiisi', $judul, $pengarang, $penerbit, $tahun, $isbn, $stok, $kategori, $newCover, $id);
                } else {
                    $st = mysqli_prepare($conn, 'INSERT INTO buku (judul,pengarang,penerbit,tahun_terbit,isbn,jumlah_stok,kategori_id,gambar) VALUES (?,?,?,?,?,?,?,?)');
                    mysqli_stmt_bind_param($st, 'sssisiis', $judul, $pengarang, $penerbit, $tahun, $isbn, $stok, $kategori, $newCover);
                }
                if (mysqli_stmt_execute($st)) {
                    if ($id && $newCover !== $oldCover && !empty($oldCover)) delete_uploaded_image($oldCover, __DIR__ . '/../assets/covers');
                    flash('success', $id ? 'Data buku berhasil diperbarui.' : 'Data buku berhasil ditambahkan.');
                    mysqli_stmt_close($st);
                    redirect('buku.php');
                }
                flash('danger', 'Gagal menyimpan buku: ' . mysqli_stmt_error($st));
                mysqli_stmt_close($st);
                if ($newCover !== $oldCover && !empty($newCover)) delete_uploaded_image($newCover, __DIR__ . '/../assets/covers');
            } catch (Throwable $ex) {
                flash('danger', $ex->getMessage());
            }
        }
    }
}

$edit = null;
if ($id) {
    $s = mysqli_prepare($conn, 'SELECT * FROM buku WHERE id=?');
    mysqli_stmt_bind_param($s, 'i', $id);
    mysqli_stmt_execute($s);
    $edit = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
    mysqli_stmt_close($s);
    if (!$edit) {
        flash('danger', 'Data buku tidak ditemukan.');
        redirect('buku.php');
    }
}

$cats = mysqli_query($conn, 'SELECT id,Kategori FROM kategori ORDER BY Kategori');
page_start($edit ? 'Edit Buku' : 'Tambah Buku', 'buku.php', 'admin');
render_flash();
?>
<div class="form-page-head">
    <div class="section-head"><a href="buku.php"><?= icon('back', 20) ?> Kembali ke Data Buku</a><span></span></div>
    <h2><?= $edit ? 'Edit Data Buku' : 'Tambah Data Buku' ?></h2>
    <p><?= $edit ? 'Perbarui seluruh informasi buku dan ganti cover bila diperlukan.' : 'Masukkan informasi lengkap buku yang akan ditambahkan ke perpustakaan.' ?></p>
</div>

<div class="form-card book-form-page">
    <form method="post" enctype="multipart/form-data" class="form-grid">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">

        <div><label>Nama Buku</label><input name="judul" value="<?= e($edit['judul'] ?? '') ?>" placeholder="Masukkan nama buku" required></div>
        <div><label>Kategori</label><select name="kategori_id" required>
                <option value="">Pilih kategori</option><?php while ($c = mysqli_fetch_assoc($cats)): ?><option value="<?= $c['id'] ?>" <?= ((int)($edit['kategori_id'] ?? 0) === $c['id']) ? 'selected' : '' ?>><?= e($c['Kategori']) ?></option><?php endwhile; ?>
            </select></div>
        <div><label>Pengarang</label><input name="pengarang" value="<?= e($edit['pengarang'] ?? '') ?>" placeholder="Masukkan nama pengarang" required></div>
        <div><label>Penerbit</label><input name="penerbit" value="<?= e($edit['penerbit'] ?? '') ?>" placeholder="Masukkan penerbit" required></div>
        <div><label>Tahun Terbit</label><input type="number" name="tahun_terbit" min="1000" max="2100" value="<?= e($edit['tahun_terbit'] ?? date('Y')) ?>" required></div>
        <div><label>ISBN</label><input name="isbn" value="<?= e($edit['isbn'] ?? '') ?>" placeholder="Masukkan ISBN"></div>
        <div><label>Jumlah Stok</label><input type="number" min="0" name="jumlah_stok" value="<?= e($edit['jumlah_stok'] ?? 0) ?>" required></div>
        <div>
            <label>Cover Buku <span class="muted">(JPG, PNG, WEBP, maks. 2 MB)</span></label>
            <input type="file" name="cover" id="coverInput" accept="image/jpeg,image/png,image/webp">
            <div class="cover-preview-wrap" id="coverPreviewWrap" style="<?= !empty($edit['gambar']) ? '' : 'display:none' ?>">
                <img id="coverPreview" src="<?= !empty($edit['gambar']) ? (preg_match('~^https?://~i', $edit['gambar']) ? e($edit['gambar']) : '../assets/' . e($edit['gambar'])) : '' ?>" alt="Preview cover">
            </div>
        </div>
        <div class="form-actions"><a class="btn ghost" href="buku.php">Batal</a><button class="btn primary" type="submit"><?= $edit ? 'Update Data' : 'Simpan Data' ?></button></div>
    </form>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('coverInput'),
            wrap = document.getElementById('coverPreviewWrap'),
            preview = document.getElementById('coverPreview');
        if (input) input.addEventListener('change', function() {
            const file = this.files && this.files[0];
            if (!file) return;
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                alert('Format cover harus JPG, PNG, atau WEBP.');
                this.value = '';
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                alert('Ukuran cover maksimal 2 MB.');
                this.value = '';
                return;
            }
            preview.src = URL.createObjectURL(file);
            wrap.style.display = 'block';
        });
    });
</script>
<?php page_end(); ?>