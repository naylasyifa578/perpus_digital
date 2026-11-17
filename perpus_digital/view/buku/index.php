<div class="page-heading">
    <div>
        <h2>Data Buku</h2>
        <p>Kelola koleksi buku perpustakaan.</p>
    </div>
    <a class="btn primary" href="<?= e(url('buku.form')) ?>"><?= icon('plus', 18) ?> Tambah Data</a>
</div>

<form class="filter-bar" method="get" action="index.php">
    <input type="hidden" name="page" value="buku">
    <div class="search-field"><?= icon('search', 19) ?><input name="q" value="<?= e($keyword) ?>" placeholder="Cari nama buku..."></div>
    <select name="kategori">
        <option value="0">Semua Kategori</option>
        <?php foreach ($categories as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= $kategoriId === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['Kategori']) ?></option>
        <?php endforeach; ?>
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
                <?php foreach ($books as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <?php if (!empty($r['gambar'])): ?>
                                <img class="admin-book-thumb" src="<?= e(cover_url($r['gambar'])) ?>" alt="Cover buku">
                            <?php else: ?>
                                <span class="admin-book-placeholder"><?= icon('book', 20) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= e($r['judul']) ?></strong><small class="muted block"><?= e($r['isbn'] ?: '-') ?></small></td>
                        <td><?= e($r['Kategori'] ?? '-') ?></td>
                        <td><?= e($r['pengarang']) ?></td>
                        <td><?= e($r['tahun_terbit']) ?></td>
                        <td><span class="stock"><?= (int) $r['jumlah_stok'] ?></span></td>
                        <td class="actions">
                            <a class="icon-btn" href="<?= e(url('buku.form', ['id' => $r['id']])) ?>" title="Edit"><?= icon('edit', 17) ?></a>
                            <form method="post" action="<?= e(url('buku.delete')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                <button class="icon-btn danger-text" type="submit" title="Hapus" data-confirm="Hapus buku ini?"><?= icon('trash', 17) ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$books): ?>
                    <tr>
                        <td colspan="8" class="empty">Data buku tidak ditemukan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
