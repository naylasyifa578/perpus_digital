<?php $editing = $id > 0; ?>
<div class="form-page-head">
    <div class="section-head"><a href="<?= e(url('anggota')) ?>"><?= icon('back', 20) ?> Kembali ke Anggota</a><span></span></div>
    <h2><?= $editing ? 'Edit Data Anggota' : 'Tambah Data Anggota' ?></h2>
    <p><?= $editing ? 'Perbarui informasi akun anggota yang dipilih.' : 'Masukkan informasi anggota baru yang akan ditambahkan ke sistem.' ?></p>
</div>

<div class="form-card">
    <form method="post" action="<?= e(url('anggota.save')) ?>" class="form-grid" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $id ?>">

        <div>
            <label for="nama_lengkap">Nama Lengkap</label>
            <input id="nama_lengkap" name="nama_lengkap" maxlength="100" value="<?= e($member['nama_lengkap'] ?? '') ?>" placeholder="Masukkan nama lengkap" required autofocus>
        </div>
        <div>
            <label for="username">Username</label>
            <input id="username" name="username" maxlength="50" value="<?= e($member['username'] ?? '') ?>" placeholder="Masukkan username" required>
        </div>
        <div>
            <label for="gmail">Email</label>
            <input id="gmail" name="gmail" type="email" maxlength="200" value="<?= e($member['gmail'] ?? '') ?>" placeholder="Masukkan email" required>
        </div>
        <div>
            <label for="password">Password <?= $editing ? '<span class="muted">(kosongkan jika tidak diubah)</span>' : '' ?></label>
            <input id="password" name="password" type="password" minlength="6" placeholder="<?= $editing ? 'Password baru (opsional)' : 'Minimal 6 karakter' ?>" <?= $editing ? '' : 'required' ?> autocomplete="new-password">
        </div>
        <div>
            <label for="password_confirm">Konfirmasi Password <?= $editing ? '<span class="muted">(jika mengganti password)</span>' : '' ?></label>
            <input id="password_confirm" name="password_confirm" type="password" minlength="6" placeholder="Ulangi password" <?= $editing ? '' : 'required' ?> autocomplete="new-password">
        </div>
        <div class="form-actions">
            <a class="btn ghost" href="<?= e(url('anggota')) ?>">Batal</a>
            <button class="btn primary" type="submit"><?= $editing ? 'Update Data' : 'Simpan Data' ?></button>
        </div>
    </form>
</div>
