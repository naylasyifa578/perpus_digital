<?php $editing = $id > 0; ?>
<div class="form-page-head">
    <div class="section-head"><a href="<?= e(url('buku')) ?>"><?= icon('back', 20) ?> Kembali ke Data Buku</a><span></span></div>
    <h2><?= $editing ? 'Edit Data Buku' : 'Tambah Data Buku' ?></h2>
    <p><?= $editing ? 'Perbarui seluruh informasi buku dan ganti cover bila diperlukan.' : 'Masukkan informasi lengkap buku yang akan ditambahkan ke perpustakaan.' ?></p>
</div>

<div class="form-card book-form-page">
    <form method="post" action="<?= e(url('buku.save')) ?>" enctype="multipart/form-data" class="form-grid">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $id ?>">

        <div><label>Nama Buku</label><input name="judul" value="<?= e($book['judul'] ?? '') ?>" placeholder="Masukkan nama buku" required></div>
        <div><label>Kategori</label>
            <select name="kategori_id" required>
                <option value="">Pilih kategori</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= (int) ($book['kategori_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['Kategori']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div><label>Pengarang</label><input name="pengarang" value="<?= e($book['pengarang'] ?? '') ?>" placeholder="Masukkan nama pengarang" required></div>
        <div><label>Penerbit</label><input name="penerbit" value="<?= e($book['penerbit'] ?? '') ?>" placeholder="Masukkan penerbit" required></div>
        <div><label>Tahun Terbit</label><input type="number" name="tahun_terbit" min="1000" max="2100" value="<?= e($book['tahun_terbit'] ?? date('Y')) ?>" required></div>
        <div><label>ISBN</label><input name="isbn" value="<?= e($book['isbn'] ?? '') ?>" placeholder="Masukkan ISBN"></div>
        <div><label>Jumlah Stok</label><input type="number" min="0" name="jumlah_stok" value="<?= e($book['jumlah_stok'] ?? 0) ?>" required></div>
        <div>
            <label>Cover Buku <span class="muted">(JPG, PNG, WEBP, maks. 2 MB)</span></label>
            <input type="file" name="cover" id="coverInput" accept="image/jpeg,image/png,image/webp">
            <div class="cover-preview-wrap" id="coverPreviewWrap" style="<?= !empty($book['gambar']) ? '' : 'display:none' ?>">
                <img id="coverPreview" src="<?= !empty($book['gambar']) ? e(cover_url($book['gambar'])) : '' ?>" alt="Preview cover">
            </div>
        </div>
        <div class="form-actions">
            <a class="btn ghost" href="<?= e(url('buku')) ?>">Batal</a>
            <button class="btn primary" type="submit"><?= $editing ? 'Update Data' : 'Simpan Data' ?></button>
        </div>
    </form>
</div>
