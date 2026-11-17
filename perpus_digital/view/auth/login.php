<?php if ($error !== ''): ?><div class="alert danger"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="<?= e(url('login')) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="form-group">
        <label>Masuk sebagai</label>
        <div class="role-switch">
            <label class="role-option"><input type="radio" name="peran" value="siswa" <?= $peran === 'siswa' ? 'checked' : '' ?>><span>Siswa</span></label>
            <label class="role-option"><input type="radio" name="peran" value="admin" <?= $peran === 'admin' ? 'checked' : '' ?>><span>Admin</span></label>
        </div>
    </div>
    <div class="form-group"><label for="username">Email / Username</label><input id="username" name="username" type="text" placeholder="Masukan email atau username" value="<?= e($login) ?>" required autofocus></div>
    <div class="form-group"><label for="password">Password</label>
        <div class="password-wrap"><input id="password" name="password" type="password" placeholder="Masukan password" required>
            <button type="button" class="icon-btn password-toggle" data-target="password" aria-label="Tampilkan password"><?= icon('eye', 20) ?></button>
        </div>
    </div>
    <div class="auth-forgot"><a href="#" onclick="alert('Silakan hubungi admin untuk reset password.');return false;">Lupa password?</a></div>
    <button class="btn primary full" type="submit">Masuk</button>
    <p class="auth-bottom">Belum punya akun? <a href="<?= e(url('register')) ?>">Daftar di sini</a></p>
</form>
