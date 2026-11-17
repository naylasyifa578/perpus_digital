<?php $editing = is_array($edit); ?>
<div class="page-heading">
    <div>
        <h2>Kategori</h2>
        <p>Kelola kategori buku.</p>
    </div>
</div>
<div class="two-col">
    <div>
        <form class="filter-bar compact" method="get" action="index.php">
            <input type="hidden" name="page" value="kategori">
            <div class="search-field">
                <?= icon('search', 19) ?>
                <input name="q" value="<?= e($keyword) ?>" placeholder="Cari kategori...">
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
                        <?php foreach ($categories as $i => $r): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <?php if (!empty($r['gambar'])): ?>
                                        <img class="category-admin-thumb" src="<?= e(cover_url($r['gambar'])) ?>" alt="Foto kategori">
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td><strong><?= e($r['Kategori']) ?></strong></td>
                                <td><?= e($r['Deskripsi']) ?></td>
                                <td><?= (int) $r['jumlah_buku'] ?></td>
                                <td class="actions">
                                    <a class="icon-btn" href="<?= e(url('kategori', ['edit' => $r['id']])) ?>" title="Edit"><?= icon('edit', 17) ?></a>
                                    <form method="post" action="<?= e(url('kategori.delete')) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                        <button class="icon-btn danger-text" type="submit" title="Hapus" data-confirm="Hapus kategori ini?"><?= icon('trash', 17) ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$categories): ?>
                            <tr>
                                <td colspan="6" class="empty">Kategori tidak ditemukan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="form-card">
        <div class="form-card-title">
            <h3><?= $editing ? 'Edit Kategori' : 'Tambah Kategori' ?></h3>
        </div>
        <form method="post" action="<?= e(url('kategori.save')) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $editing ? (int) $edit['id'] : 0 ?>">
            <label>Nama Kategori</label>
            <input name="Kategori" value="<?= e($edit['Kategori'] ?? '') ?>" required>
            <label>Foto Kategori <span class="muted">(Opsional)</span></label>
            <input type="file" name="gambar" accept="image/jpeg,image/png,image/webp">
            <small class="muted">Contoh: kategori Agama dapat menggunakan foto masjid. Kamu bisa menggantinya kapan saja.</small>
            <label>Deskripsi</label>
            <textarea name="Deskripsi" rows="5" placeholder="Deskripsi kategori"><?= e($edit['Deskripsi'] ?? '') ?></textarea>
            <div class="form-actions">
                <a class="btn ghost" href="<?= e(url('kategori')) ?>">Batal</a>
                <button class="btn primary" type="submit"><?= $editing ? 'Update' : 'Simpan' ?></button>
            </div>
        </form>
    </div>
</div>
