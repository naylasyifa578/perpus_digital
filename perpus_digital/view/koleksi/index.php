<div class="page-heading">
    <div>
        <h2>Koleksi Saya</h2>
        <p>Daftar buku yang Anda simpan.</p>
    </div>
</div>

<form class="filter-bar" method="get" action="index.php">
    <input type="hidden" name="page" value="koleksi">
    <div class="search-field">
        <?= icon('search', 19) ?>
        <input name="q" value="<?= e($keyword) ?>" placeholder="Cari buku tersimpan...">
    </div>
    <button class="btn primary" type="submit">Cari</button>
</form>

<div class="book-grid">
    <?php foreach ($books as $b): ?>
        <?php $cover = book_cover($b); ?>
        <article class="book-card">
            <div class="book-cover">
                <?php if ($cover): ?>
                    <img src="<?= e(cover_url($cover)) ?>" alt="<?= e($b['judul']) ?>">
                <?php else: ?>
                    <span><?= e(\App\View\Format::initial($b['judul'])) ?></span>
                <?php endif; ?>
            </div>
            <div class="book-body">
                <span class="book-category"><?= e($b['Kategori'] ?? 'Tanpa kategori') ?></span>
                <h4><?= e($b['judul']) ?></h4>
                <p><?= e($b['pengarang']) ?></p>
                <small>Stok: <b><?= (int) $b['jumlah_stok'] ?></b></small>
                <div class="book-action-row">
                    <a class="btn primary small flex-1" href="<?= e(url('katalog', ['q' => $b['judul']])) ?>"> Lihat Buku</a>
                    <form method="post" action="<?= e(url('koleksi.toggle')) ?>" class="collection-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                        <input type="hidden" name="mode" value="remove">
                        <input type="hidden" name="redirect" value="koleksi">
                        <button class="icon-btn danger-outline" type="submit" title="Hapus dari koleksi"><?= icon('heart', 18) ?></button>
                    </form>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if (!$books): ?>
        <div class="empty loan-empty">Belum ada buku tersimpan.</div>
    <?php endif; ?>
</div>
