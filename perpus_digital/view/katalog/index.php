<div class="page-heading">
    <div>
        <h2><?= $selectedName !== '' ? 'Buku Kategori: ' . e($selectedName) : 'Kategori Buku' ?></h2>
        <p>Klik kotak kategori untuk melihat buku yang ada di dalamnya.</p>
    </div>
</div>

<div class="category-bar-grid">
    <?php foreach ($categories as $c): ?>
        <a class="category-bar-card <?= $kategoriId === (int) $c['id'] ? 'active' : '' ?>" href="<?= e(url('katalog', ['kategori' => $c['id']])) ?>">
            <div class="category-bar-image">
                <?php if (!empty($c['gambar'])): ?>
                    <img src="<?= e(cover_url($c['gambar'])) ?>" alt="<?= e($c['Kategori']) ?>">
                <?php else: ?>
                    <span><?= icon('book', 26) ?></span>
                <?php endif; ?>
            </div>
            <strong><?= e($c['Kategori']) ?></strong><span><?= (int) $c['jumlah'] ?> buku</span>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($showBooks): ?>
    <form class="filter-bar" method="get" action="index.php">
        <input type="hidden" name="page" value="katalog">
        <div class="search-field"><?= icon('search', 19) ?><input name="q" value="<?= e($keyword) ?>" placeholder="Cari nama buku dalam kategori"></div>
        <?php if ($kategoriId > 0): ?><input type="hidden" name="kategori" value="<?= (int) $kategoriId ?>"><?php endif; ?>
        <button class="btn primary" type="submit">Cari</button>
    </form>

    <div class="book-grid student-book-grid">
        <?php foreach ($books as $b): ?>
            <?php
            $cover = book_cover($b);
            $available = (int) $b['jumlah_stok'] > 0;
            $modalId = 'bookModal' . (int) $b['id'];
            $inCollection = (int) $b['in_collection'] === 1;
            ?>
            <article class="book-card student-book-card" tabindex="0" data-modal-target="#<?= e($modalId) ?>" aria-label="Lihat detail <?= e($b['judul']) ?>">
                <div class="book-cover">
                    <?php if ($cover): ?>
                        <img src="<?= e(cover_url($cover)) ?>" alt="<?= e($b['judul']) ?>">
                    <?php else: ?>
                        <span><?= e(\App\View\Format::initial($b['judul'])) ?></span>
                    <?php endif; ?>
                </div>
                <div class="book-body">
                    <span class="book-category"><?= e($b['Kategori'] ?? '-') ?></span>
                    <h4><?= e($b['judul']) ?></h4>
                    <p><?= e($b['pengarang']) ?></p>
                    <div class="book-meta-row">
                        <small>Stok: <b><?= (int) $b['jumlah_stok'] ?></b></small>
                        <span class="badge <?= $available ? 'success' : 'danger' ?>"><?= $available ? 'Tersedia' : 'Habis' ?></span>
                    </div>
                    <div class="book-action-row">
                        <button type="button" class="btn primary book-select-btn flex-1" data-modal-target="#<?= e($modalId) ?>"><?= icon('book', 16) ?> Pilih Buku</button>
                        <form method="post" action="<?= e(url('koleksi.toggle')) ?>" class="collection-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                            <input type="hidden" name="mode" value="<?= $inCollection ? 'remove' : 'save' ?>">
                            <input type="hidden" name="kategori" value="<?= (int) $kategoriId ?>">
                            <input type="hidden" name="q" value="<?= e($keyword) ?>">
                            <button class="icon-btn collection-btn <?= $inCollection ? 'saved' : '' ?>" type="submit" title="<?= $inCollection ? 'Hapus dari koleksi' : 'Simpan ke koleksi' ?>"><?= icon('heart', 18) ?></button>
                        </form>
                    </div>
                </div>
            </article>

            <div class="book-modal" id="<?= e($modalId) ?>" aria-hidden="true">
                <div class="book-modal-backdrop" data-close-modal></div>
                <div class="book-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="<?= e($modalId) ?>Title">
                    <button type="button" class="modal-close icon-btn" data-close-modal aria-label="Tutup">&times;</button>
                    <div class="loan-detail-head">
                        <div class="loan-cover">
                            <?php if ($cover): ?><img src="<?= e(cover_url($cover)) ?>" alt="<?= e($b['judul']) ?>"><?php else: ?><span><?= e(\App\View\Format::initial($b['judul'])) ?></span><?php endif; ?>
                        </div>
                        <div>
                            <span class="book-category"><?= e($b['Kategori'] ?? '-') ?></span>
                            <h3 id="<?= e($modalId) ?>Title"><?= e($b['judul']) ?></h3>
                            <p><?= e($b['pengarang']) ?></p>
                            <small>Stok tersedia: <b><?= (int) $b['jumlah_stok'] ?></b></small>
                        </div>
                    </div>
                    <div class="loan-info-box">
                        <strong>Ingin meminjam buku ini?</strong>
                        <span>Tanggal pinjam dapat dipilih mulai hari ini. Jatuh tempo otomatis <?= (int) $loanDays ?> hari setelah tanggal pinjam.</span>
                    </div>
                    <?php if ($available && $blockedUntil === null): ?>
                        <form method="post" action="<?= e(url('peminjaman.request')) ?>" class="loan-form" data-days="<?= (int) $loanDays ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="buku_id" value="<?= (int) $b['id'] ?>">
                            <label>Tanggal Pinjam</label>
                            <input type="date" name="tanggal_pinjam" class="borrow-date-input" min="<?= e(date('Y-m-d')) ?>" value="<?= e(date('Y-m-d')) ?>" required>
                            <label>Jatuh Tempo</label>
                            <input type="date" class="due-date-output" value="<?= e(due_date(date('Y-m-d'))) ?>" readonly aria-readonly="true">
                            <label>Keterangan <span class="muted">(Opsional)</span></label>
                            <textarea name="keterangan" rows="3" placeholder="Masukkan keterangan..." maxlength="500"></textarea>
                            <div class="modal-actions">
                                <button type="button" class="btn ghost" data-close-modal>Batal</button>
                                <button type="submit" class="btn primary"><?= icon('book', 18) ?> Ajukan Peminjaman</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="empty loan-empty"><?= $blockedUntil !== null ? 'Peminjaman sedang dinonaktifkan sampai ' . e(format_date($blockedUntil)) . '.' : 'Maaf, stok buku sedang habis.' ?></div>
                        <div class="modal-actions"><button type="button" class="btn ghost" data-close-modal>Tutup</button></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!$books): ?>
        <div class="table-card empty">Buku tidak ditemukan.</div>
    <?php endif; ?>
<?php else: ?>
    <div class="category-select-empty">Pilih salah satu kategori di atas untuk melihat buku.</div>
<?php endif; ?>
