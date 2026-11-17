<div class="form-page-head">
    <div class="section-head"><a href="<?= e(url('peminjaman')) ?>"><?= icon('back', 20) ?> Kembali ke Peminjaman</a><span></span></div>
    <h2>Tambah Transaksi Peminjaman</h2>
    <p>Catat peminjaman buku untuk seorang anggota. Jatuh tempo otomatis <?= (int) $loanDays ?> hari setelah tanggal pinjam. Jika tanggal pinjam hari ini atau sebelumnya, status langsung <b>Dipinjam</b> dan stok buku berkurang.</p>
</div>

<div class="form-card">
    <form method="post" action="<?= e(url('peminjaman.save')) ?>" class="form-grid loan-form" data-days="<?= (int) $loanDays ?>">
        <?= csrf_field() ?>
        <div>
            <label for="user_id">Anggota</label>
            <select id="user_id" name="user_id" required>
                <option value="">Pilih anggota</option>
                <?php foreach ($members as $m): ?>
                    <option value="<?= (int) $m['id'] ?>" <?= (int) $old['user_id'] === (int) $m['id'] ? 'selected' : '' ?>><?= e($m['nama_lengkap']) ?> (<?= e($m['username']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="buku_id">Buku <span class="muted">(hanya yang stoknya tersedia)</span></label>
            <select id="buku_id" name="buku_id" required>
                <option value="">Pilih buku</option>
                <?php foreach ($books as $b): ?>
                    <option value="<?= (int) $b['id'] ?>" <?= (int) $old['buku_id'] === (int) $b['id'] ? 'selected' : '' ?>><?= e($b['judul']) ?> — stok <?= (int) $b['jumlah_stok'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="tanggal_pinjam">Tanggal Pinjam</label>
            <input id="tanggal_pinjam" type="date" name="tanggal_pinjam" class="borrow-date-input" value="<?= e($old['tanggal_pinjam']) ?>" required>
        </div>
        <div>
            <label for="jatuh_tempo">Jatuh Tempo</label>
            <input id="jatuh_tempo" type="date" class="due-date-output" value="<?= e(due_date($old['tanggal_pinjam'])) ?>" readonly aria-readonly="true">
        </div>
        <div class="form-actions">
            <a class="btn ghost" href="<?= e(url('peminjaman')) ?>">Batal</a>
            <button class="btn primary" type="submit">Simpan Transaksi</button>
        </div>
    </form>
</div>
