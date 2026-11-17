<div class="profile-layout">
    <div class="profile-card">
        <div class="profile-avatar large">
            <?php if (!empty($user['foto_profil'])): ?>
                <img src="<?= e(cover_url($user['foto_profil'])) ?>" alt="Foto profil">
            <?php else: ?>
                <?= e(\App\View\Format::initial($user['nama_lengkap'])) ?>
            <?php endif; ?>
        </div>
        <h2><?= e($user['nama_lengkap']) ?></h2>
        <p><?= e($user['gmail']) ?></p>
        <a class="btn danger full" href="<?= e(url('logout')) ?>">Keluar</a>
    </div>
    <div class="profile-panels">
        <div class="form-card">
            <div class="form-card-title">
                <h3>Foto Profil</h3>
            </div>
            <form method="post" action="<?= e(url('profil.foto')) ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="file" name="foto_profil" accept="image/jpeg,image/png,image/webp" required>
                <small class="muted">JPG, PNG, WEBP · maksimal 2 MB</small>
                <button class="btn primary" type="submit">Simpan Foto</button>
            </form>
        </div>
        <div class="form-card">
            <div class="form-card-title">
                <h3><?= icon('user', 20) ?> Info Akun</h3>
            </div>
            <form method="post" action="<?= e(url('profil.update')) ?>">
                <?= csrf_field() ?>
                <label>Nama Lengkap</label>
                <input name="nama_lengkap" value="<?= e($user['nama_lengkap']) ?>" required>
                <label>Email</label>
                <input type="email" name="gmail" value="<?= e($user['gmail']) ?>" required>
                <label>Username</label>
                <input value="<?= e($user['username']) ?>" disabled>
                <button class="btn primary" type="submit">Update Profil</button>
            </form>
        </div>
        <div class="form-card">
            <div class="form-card-title">
                <h3>Ubah Password</h3>
            </div>
            <form method="post" action="<?= e(url('profil.password')) ?>">
                <?= csrf_field() ?>
                <label>Password Lama</label>
                <input type="password" name="old_password" required>
                <label>Password Baru</label>
                <input type="password" name="new_password" minlength="6" required>
                <button class="btn primary" type="submit">Simpan Password</button>
            </form>
        </div>
    </div>
</div>
