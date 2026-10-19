<?php
require_once __DIR__ . '/../models/koneksi.php';
require_once __DIR__ . '/../models/auth.php';
require_once __DIR__ . '/../models/migrate.php';
require_once __DIR__ . '/layout.php';

require_role('siswa');
ensure_collection_table($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);

    $s = mysqli_prepare($conn, 'DELETE FROM koleksi_buku WHERE user_id=? AND buku_id=?');
    mysqli_stmt_bind_param($s, 'ii', $_SESSION['id'], $id);
    mysqli_stmt_execute($s);
    mysqli_stmt_close($s);

    flash('success', 'Buku dihapus dari koleksi.');
    redirect('koleksi.php');
}

$q = trim($_GET['q'] ?? '');
$s = mysqli_prepare($conn, "SELECT b.*,k.Kategori,kb.created_at AS saved_at FROM koleksi_buku kb JOIN buku b ON b.id=kb.buku_id LEFT JOIN kategori k ON k.id=b.kategori_id WHERE kb.user_id=? AND (b.judul LIKE CONCAT('%',?,'%') OR b.pengarang LIKE CONCAT('%',?,'%')) ORDER BY kb.created_at DESC");
mysqli_stmt_bind_param($s, 'iss', $_SESSION['id'], $q, $q);
mysqli_stmt_execute($s);

$rows = mysqli_stmt_get_result($s);
page_start('Koleksi Saya', 'koleksi.php', 'siswa');
render_flash();
?>
<div class="page-heading">
    <div>
        <h2>Koleksi Saya</h2>
        <p>Daftar buku yang Anda simpan.</p>
    </div>
</div>

<form class="filter-bar" method="get">
    <div class="search-field">
        <?= icon('search', 19) ?>
        <input name="q" value="<?= e($q) ?>" placeholder="Cari buku tersimpan...">
    </div>
    <button class="btn primary">Cari</button>
</form>

<div class="book-grid">
    <?php while ($b = mysqli_fetch_assoc($rows)): $cover = $b['gambar'] ?? null; ?>
        <article class="book-card">
            <div class="book-cover">
                <?php if ($cover): ?>
                    <img src="<?= preg_match('~^https?://~i', $cover) ? e($cover) : '../assets/' . e($cover) ?>" alt="<?= e($b['judul']) ?>">
                <?php else: ?>
                    <span>
                        <?= e(mb_substr($b['judul'], 0, 1)) ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="book-body">
                <span class="book-category"><?= e($b['Kategori'] ?? 'Tanpa kategori') ?></span>
                <h4><?= e($b['judul']) ?></h4>
                <p><?= e($b['pengarang']) ?></p>
                <small>Stok: <b><?= e($b['jumlah_stok']) ?></b>
                </small>
                <div class="book-action-row">
                    <a class="btn primary small flex-1" href="kategori_siswa.php?q=<?= urlencode($b['judul']) ?>"> Lihat Buku</a>
                    <form method="post" class="collection-form">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <input type="hidden" name="id" value="<?= $b['id'] ?>">
                        <button class="icon-btn danger-outline" type="submit" title="Hapus dari koleksi">
                            <?= icon('heart', 18) ?>
                        </button>
                    </form>
                </div>
            </div>
        </article>
    <?php endwhile;
    if (mysqli_num_rows($rows) === 0): ?>
        <div class="empty loan-empty">Belum ada buku tersimpan.</div>
    <?php endif; ?>
</div><?php page_end();
        ?>