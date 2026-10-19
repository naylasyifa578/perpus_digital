<?php
require_once __DIR__ . '/../models/koneksi.php';
require_once __DIR__ . '/../models/auth.php';
require_once __DIR__ . '/../models/migrate.php';
require_once __DIR__ . '/../models/uploads.php';
require_once __DIR__ . '/../views/layout.php';
require_login();
ensure_profile_photo($conn);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'photo') {
        $s = mysqli_prepare($conn, 'SELECT foto_profil FROM users WHERE id=?');
        mysqli_stmt_bind_param($s, 'i', $_SESSION['id']);
        mysqli_stmt_execute($s);
        $old = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
        mysqli_stmt_close($s);
        try {
            $photo = upload_profile_photo($_FILES['foto_profil'] ?? null, $old['foto_profil'] ?? null);
            if ($photo) {
                $u2 = mysqli_prepare($conn, 'UPDATE users SET foto_profil=? WHERE id=?');
                mysqli_stmt_bind_param($u2, 'si', $photo, $_SESSION['id']);
                mysqli_stmt_execute($u2);
                mysqli_stmt_close($u2);
                $_SESSION['foto_profil'] = $photo;
                flash('success', 'Foto profil berhasil diperbarui.');
            } else flash('danger', 'Pilih foto profil terlebih dahulu.');
        } catch (Throwable $ex) {
            flash('danger', $ex->getMessage());
        }
    } elseif ($action === 'profile') {
        $nama = trim($_POST['nama_lengkap'] ?? '');
        $gmail = trim($_POST['gmail'] ?? '');
        if ($nama === '' || !filter_var($gmail, FILTER_VALIDATE_EMAIL)) {
            flash('danger', 'Nama dan email harus valid.');
        } else {
            $s = mysqli_prepare($conn, 'UPDATE users SET nama_lengkap=?,gmail=? WHERE id=?');
            mysqli_stmt_bind_param($s, 'ssi', $nama, $gmail, $_SESSION['id']);
            mysqli_stmt_execute($s);
            mysqli_stmt_close($s);
            $_SESSION['nama_lengkap'] = $nama;
            flash('success', 'Profil diperbarui.');
        }
    } elseif ($action === 'password') {
        $old = $_POST['old_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $s = mysqli_prepare($conn, 'SELECT password FROM users WHERE id=?');
        mysqli_stmt_bind_param($s, 'i', $_SESSION['id']);
        mysqli_stmt_execute($s);
        $u = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
        mysqli_stmt_close($s);
        $stored = trim($u['password'] ?? '');
        if (!password_verify($old, $stored) && $old !== $stored) {
            flash('danger', 'Password lama salah.');
        } elseif (strlen($new) < 6) {
            flash('danger', 'Password baru minimal 6 karakter.');
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $s = mysqli_prepare($conn, 'UPDATE users SET password=? WHERE id=?');
            mysqli_stmt_bind_param($s, 'si', $hash, $_SESSION['id']);
            mysqli_stmt_execute($s);
            mysqli_stmt_close($s);
            flash('success', 'Password berhasil diubah.');
        }
    }
    redirect('profile.php');
}
$s = mysqli_prepare($conn, 'SELECT username,nama_lengkap,gmail,role,created_at,foto_profil FROM users WHERE id=?');
mysqli_stmt_bind_param($s, 'i', $_SESSION['id']);
mysqli_stmt_execute($s);
$u = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
mysqli_stmt_close($s);
page_start('Profile', 'profile.php', $_SESSION['role']);
render_flash(); ?><div class="profile-layout">
    <div class="profile-card">
        <div class="profile-avatar large"><?php if (!empty($u['foto_profil'])): ?><img src="../assets/<?= e($u['foto_profil']) ?>" alt="Foto profil"><?php else: ?><?= strtoupper(substr($u['nama_lengkap'], 0, 1)) ?><?php endif; ?></div>
        <h2><?= e($u['nama_lengkap']) ?></h2>
        <p><?= e($u['gmail']) ?></p><a class="btn danger full" href="../controller/logout.php">Keluar</a>
    </div>
    <div class="profile-panels">
        <div class="form-card">
            <div class="form-card-title">
                <h3>Foto Profil</h3>
            </div>
            <form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="photo"><input type="file" name="foto_profil" accept="image/jpeg,image/png,image/webp" required><small class="muted">JPG, PNG, WEBP · maksimal 2 MB</small><button class="btn primary" type="submit">Simpan Foto</button></form>
        </div>
        <div class="form-card">
            <div class="form-card-title">
                <h3><?= icon('user', 20) ?> Info Akun</h3>
            </div>
            <form method="post"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="profile"><label>Nama Lengkap</label><input name="nama_lengkap" value="<?= e($u['nama_lengkap']) ?>" required><label>Email</label><input type="email" name="gmail" value="<?= e($u['gmail']) ?>" required><label>Username</label><input value="<?= e($u['username']) ?>" disabled><button class="btn primary">Update Profil</button></form>
        </div>
        <div class="form-card">
            <div class="form-card-title">
                <h3>Ubah Password</h3>
            </div>
            <form method="post"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="password"><label>Password Lama</label><input type="password" name="old_password" required><label>Password Baru</label><input type="password" name="new_password" minlength="6" required><button class="btn primary">Simpan Password</button></form>
        </div>
    </div>
</div><?php page_end(); ?>