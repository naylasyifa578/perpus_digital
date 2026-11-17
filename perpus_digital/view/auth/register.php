<?php if ($error !== ''): ?><div class="alert danger"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="<?= e(url('register')) ?>">
    <?= csrf_field() ?>
    <div class="form-group">
        <label>Daftar sebagai</label>
        <div class="role-switch">
            <label class="role-option"><input type="radio" name="peran" value="siswa" <?= $old['peran'] === 'siswa' ? 'checked' : '' ?>><span>Siswa</span></label>
            <label class="role-option"><input type="radio" name="peran" value="admin" <?= $old['peran'] === 'admin' ? 'checked' : '' ?>><span>Admin</span></label>
        </div>
    </div>
    <div class="form-group"><label>Nama Lengkap</label><input name="nama_lengkap" placeholder="Masukkan nama lengkap" value="<?= e($old['nama_lengkap']) ?>" required></div>
    <div class="form-group"><label>Email</label><input name="gmail" type="email" placeholder="Masukkan email" value="<?= e($old['gmail']) ?>" required></div>
    <div class="form-group"><label>Username</label><input name="username" placeholder="Masukkan username" value="<?= e($old['username']) ?>" required></div>
    <div class="form-group"><label>Password</label>
        <div class="password-wrap"><input id="password" name="password" type="password" placeholder="Buat password" required>
            <button type="button" class="icon-btn password-toggle" data-target="password" aria-label="Tampilkan password"><?= icon('eye', 20) ?></button>
        </div>
    </div>
    <div class="form-group admin-code-group" id="adminCodeGroup">
        <label for="kode_admin">Kode Admin <span class="muted">(hanya diisi jika mendaftar sebagai Admin)</span></label>
        <input id="kode_admin" name="kode_admin" type="password" placeholder="Masukkan kode admin" autocomplete="off">
    </div>
    <button class="btn primary full" type="submit">Daftar</button>
    <p class="auth-bottom">Sudah punya akun? <a href="<?= e(url('login')) ?>">Masuk di sini</a></p>
</form>
