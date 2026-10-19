<?php
require_once __DIR__ . '/../models/koneksi.php';
require_once __DIR__ . '/../models/auth.php';
require_once __DIR__ . '/../models/migrate.php';
require_once __DIR__ . '/../views/layout.php';
require_role('siswa');
ensure_due_dates_14_days($conn);
ensure_collection_table($conn);
ensure_borrow_sanction($conn);
ensure_category_image_column($conn);
$blocked_until = get_borrow_block_until($conn, (int)$_SESSION['id']);

$q = trim($_GET['q'] ?? '');
$cat = (int)($_GET['kategori'] ?? 0);

$cats = [];
$catResult = mysqli_query($conn, "SELECT k.id, k.Kategori, k.Deskripsi, k.gambar, COUNT(b.id) AS jumlah
    FROM kategori k
    LEFT JOIN buku b ON b.kategori_id=k.id
    GROUP BY k.id, k.Kategori, k.Deskripsi, k.gambar
    ORDER BY k.Kategori");
while ($c = mysqli_fetch_assoc($catResult)) $cats[] = $c;

$selectedCategory = '';
foreach ($cats as $c) {
    if ((int)$c['id'] === $cat) {
        $selectedCategory = $c['Kategori'];
        break;
    }
}

$sql = 'SELECT b.*, k.Kategori, CASE WHEN kb.id IS NULL THEN 0 ELSE 1 END AS in_collection
        FROM buku b
        LEFT JOIN kategori k ON k.id=b.kategori_id
        LEFT JOIN koleksi_buku kb ON kb.buku_id=b.id AND kb.user_id=?
        WHERE 1';
$types = 'i';
$params = [$_SESSION['id']];
if ($q !== '') {
    $sql .= ' AND b.judul LIKE CONCAT("%", ?, "%")';
    $types .= 's';
    $params[] = $q;
}
if ($cat) {
    $sql .= ' AND b.kategori_id = ?';
    $types .= 'i';
    $params[] = $cat;
}
$sql .= ' ORDER BY b.judul ASC';
$s = mysqli_prepare($conn, $sql);
stmt_bind($s, $types, $params);
mysqli_stmt_execute($s);
$books = mysqli_stmt_get_result($s);

$coverMap = [
    'laskar pelangi' => 'laskar-pelangi.jpg',
    'hujan' => 'hujan.jpg',
    'laut bercerita' => 'laut-bercerita.jpg',
    'cinta yang mekar' => 'cinta-yang-mekar.jpg',
    'konsep ayah sepatu ibu' => 'konsep-ayah-sepatu-ibu.jpg',
    'kala itu langit biru' => 'kala-itu-langit-biru.jpg',
    'bandung after rain' => 'bandung-after-rain.jpg',
    'senja' => 'senja.jpg',
    'angin berhembus' => 'angin-berhembus.jpg',
    'seporis mie ayam' => 'seporis-mie-ayam.jpg',
];

page_start('Kategori', 'kategori_siswa.php', 'siswa');
render_flash();
?>
<div class="page-heading">
    <div>
        <h2><?= $selectedCategory ? 'Buku Kategori: ' . e($selectedCategory) : 'Kategori Buku' ?></h2>
        <p>Klik kotak kategori untuk melihat buku yang ada di dalamnya.</p>
    </div>
</div>

<div class="category-bar-grid">
    <?php foreach ($cats as $c): ?>
        <a class="category-bar-card <?= $cat === (int)$c['id'] ? 'active' : '' ?>" href="kategori_siswa.php?kategori=<?= (int)$c['id'] ?>">
            <div class="category-bar-image">
                <?php if (!empty($c['gambar'])): ?>
                    <img src="../assets/<?= e($c['gambar']) ?>" alt="<?= e($c['Kategori']) ?>">
                <?php else: ?>
                    <span><?= icon('book', 26) ?></span>
                <?php endif; ?>
            </div>
            <strong><?= e($c['Kategori']) ?></strong><span><?= (int)$c['jumlah'] ?> buku</span>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($cat): ?>
<form class="filter-bar" method="get">
    <div class="search-field"><?= icon('search', 19) ?><input name="q" value="<?= e($q) ?>" placeholder="Cari nama buku dalam kategori"></div>
    <input type="hidden" name="kategori" value="<?= $cat ?>">
    <button class="btn primary" type="submit">Cari</button>
</form>

<div class="book-grid student-book-grid">
    <?php $bookCount = 0;
    while ($b = mysqli_fetch_assoc($books)): $bookCount++;
        $titleKey = strtolower(trim($b['judul']));
        $cover = $b['gambar'] ?? null;
        if (!$cover) $cover = $coverMap[$titleKey] ?? null;
        $available = (int)$b['jumlah_stok'] > 0;
        $modalId = 'bookModal' . $b['id'];
    ?>
        <article class="book-card student-book-card" tabindex="0" data-modal-target="#<?= $modalId ?>" aria-label="Lihat detail <?= e($b['judul']) ?>">
            <div class="book-cover">
                <?php if ($cover): ?>
                    <img src="<?= preg_match('~^https?://~i', $cover) ? e($cover) : '../assets/' . e($cover) ?>" alt="<?= e($b['judul']) ?>">
                <?php else: ?>
                    <span><?= e(mb_substr($b['judul'], 0, 1)) ?></span>
                <?php endif; ?>
            </div>
            <div class="book-body">
                <span class="book-category"><?= e($b['Kategori'] ?? '-') ?></span>
                <h4><?= e($b['judul']) ?></h4>
                <p><?= e($b['pengarang']) ?></p>
                <div class="book-meta-row">
                    <small>Stok: <b><?= $b['jumlah_stok'] ?></b></small>
                    <span class="badge <?= $available ? 'success' : 'danger' ?>"><?= $available ? 'Tersedia' : 'Habis' ?></span>
                </div>
                <div class="book-action-row">
                    <button type="button" class="btn primary book-select-btn flex-1" data-modal-target="#<?= $modalId ?>"><?= icon('book', 16) ?> Pilih Buku</button>
                    <form method="post" action="peminjaman.php" class="collection-form">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="collection"><input type="hidden" name="id" value="<?= $b['id'] ?>"><input type="hidden" name="mode" value="<?= $b['in_collection'] ? 'remove' : 'save' ?>"><input type="hidden" name="redirect" value="kategori_siswa.php">
                        <button class="icon-btn collection-btn <?= $b['in_collection'] ? 'saved' : '' ?>" type="submit" title="<?= $b['in_collection'] ? 'Hapus dari koleksi' : 'Simpan ke koleksi' ?>"><?= icon('heart', 18) ?></button>
                    </form>
                </div>
            </div>
        </article>

        <div class="book-modal" id="<?= $modalId ?>" aria-hidden="true">
            <div class="book-modal-backdrop" data-close-modal></div>
            <div class="book-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="<?= $modalId ?>Title">
                <button type="button" class="modal-close icon-btn" data-close-modal aria-label="Tutup">&times;</button>
                <div class="loan-detail-head">
                    <div class="loan-cover">
                        <?php if ($cover): ?><img src="<?= preg_match('~^https?://~i', $cover) ? e($cover) : '../assets/' . e($cover) ?>" alt="<?= e($b['judul']) ?>"><?php else: ?><span><?= e(mb_substr($b['judul'], 0, 1)) ?></span><?php endif; ?>
                    </div>
                    <div>
                        <span class="book-category"><?= e($b['Kategori'] ?? '-') ?></span>
                        <h3 id="<?= $modalId ?>Title"><?= e($b['judul']) ?></h3>
                        <p><?= e($b['pengarang']) ?></p>
                        <small>Stok tersedia: <b><?= $b['jumlah_stok'] ?></b></small>
                    </div>
                </div>
                <div class="loan-info-box">
                    <strong>Ingin meminjam buku ini?</strong>
                    <span>Tanggal pinjam dapat dipilih mulai hari ini. Jatuh tempo otomatis 14 hari setelah tanggal pinjam.</span>
                </div>
                <?php if ($available && !$blocked_until): ?>
                    <form method="post" action="peminjaman.php" class="loan-form">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="request">
                        <input type="hidden" name="buku_id" value="<?= $b['id'] ?>">
                        <label>Tanggal Pinjam</label>
                        <input type="date" name="tanggal_pinjam" class="borrow-date-input" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
                        <label>Jatuh Tempo</label>
                        <input type="date" class="due-date-output" value="<?= date('Y-m-d', strtotime('+14 days')) ?>" readonly aria-readonly="true">
                        <label>Keterangan <span class="muted">(Opsional)</span></label>
                        <textarea name="keterangan" rows="3" placeholder="Masukkan keterangan..." maxlength="500"></textarea>
                        <div class="modal-actions">
                            <button type="button" class="btn ghost" data-close-modal>Batal</button>
                            <button type="submit" class="btn primary"><?= icon('book', 18) ?> Ajukan Peminjaman</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="empty loan-empty"><?= $blocked_until ? 'Peminjaman sedang dinonaktifkan sampai ' . e(format_date($blocked_until)) . '.' : 'Maaf, stok buku sedang habis.' ?></div>
                    <div class="modal-actions"><button type="button" class="btn ghost" data-close-modal>Tutup</button></div>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<?php if ($bookCount === 0): ?>
    <div class="table-card empty">Buku dalam kategori ini tidak ditemukan.</div>
<?php endif; ?>
<?php else: ?>
    <div class="category-select-empty">Pilih salah satu kategori di atas untuk melihat buku.</div>
<?php endif; ?>

<script>
document.querySelectorAll('.loan-form').forEach(function(form) {
    const borrow = form.querySelector('.borrow-date-input');
    const due = form.querySelector('.due-date-output');
    if (!borrow || !due) return;
    function addDays(dateValue, days) {
        const d = new Date(dateValue + 'T00:00:00');
        d.setDate(d.getDate() + days);
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }
    function refreshDue() {
        if (borrow.value) due.value = addDays(borrow.value, 14);
    }
    borrow.addEventListener('change', refreshDue);
    borrow.addEventListener('input', refreshDue);
    refreshDue();
});
</script>
<?php page_end(); ?>