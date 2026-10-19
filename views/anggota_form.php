<?php
require_once __DIR__ . '/../models/koneksi.php';
require_once __DIR__ . '/../models/auth.php';
require_once __DIR__ . '/layout.php';
require_role('admin');

$id = (int)($_GET['edit'] ?? $_POST['id'] ?? 0);
$edit = null;

if ($id) {
    $stmt = mysqli_prepare($conn, 'SELECT id, username, nama_lengkap, gmail FROM users WHERE id=? AND role=\'siswa\' LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $edit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
    mysqli_stmt_close($stmt);
    if (!$edit) {
        flash('danger', 'Data anggota tidak ditemukan.');
        redirect('anggota.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $id = (int)($_POST['id'] ?? 0);
    $nama = trim($_POST['nama_lengkap'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $gmail = trim($_POST['gmail'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $passwordConfirm = (string)($_POST['password_confirm'] ?? '');

    $edit = [
        'id' => $id,
        'nama_lengkap' => $nama,
        'username' => $username,
        'gmail' => $gmail,
    ];

    if ($nama === '' || $username === '' || !filter_var($gmail, FILTER_VALIDATE_EMAIL)) {
        flash('danger', 'Nama lengkap, username, dan email wajib diisi dengan benar.');
    } elseif (strlen($username) > 50) {
        flash('danger', 'Username maksimal 50 karakter.');
    } elseif ($id === 0 && strlen($password) < 6) {
        flash('danger', 'Password anggota baru minimal 6 karakter.');
    } elseif ($password !== '' && strlen($password) < 6) {
        flash('danger', 'Password minimal 6 karakter.');
    } elseif ($password !== '' && $password !== $passwordConfirm) {
        flash('danger', 'Konfirmasi password tidak sama.');
    } else {
        // Username dan email harus unik. Saat edit, data milik anggota yang sedang diedit dikecualikan.
        if ($id) {
            $check = mysqli_prepare($conn, 'SELECT id FROM users WHERE (username=? OR gmail=?) AND id<>? LIMIT 1');
            mysqli_stmt_bind_param($check, 'ssi', $username, $gmail, $id);
        } else {
            $check = mysqli_prepare($conn, 'SELECT id FROM users WHERE username=? OR gmail=? LIMIT 1');
            mysqli_stmt_bind_param($check, 'ss', $username, $gmail);
        }
        mysqli_stmt_execute($check);
        $exists = mysqli_fetch_assoc(mysqli_stmt_get_result($check));
        mysqli_stmt_close($check);

        if ($exists) {
            flash('danger', 'Username atau email sudah digunakan oleh anggota lain.');
        } else {
            if ($id) {
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = mysqli_prepare($conn, 'UPDATE users SET nama_lengkap=?, username=?, gmail=?, password=? WHERE id=? AND role=\'siswa\'');
                    mysqli_stmt_bind_param($stmt, 'ssssi', $nama, $username, $gmail, $hash, $id);
                } else {
                    $stmt = mysqli_prepare($conn, 'UPDATE users SET nama_lengkap=?, username=?, gmail=? WHERE id=? AND role=\'siswa\'');
                    mysqli_stmt_bind_param($stmt, 'sssi', $nama, $username, $gmail, $id);
                }
                $okMessage = 'Data anggota berhasil diperbarui.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $role = 'siswa';
                $stmt = mysqli_prepare($conn, 'INSERT INTO users (username, password, nama_lengkap, role, gmail) VALUES (?,?,?,?,?)');
                mysqli_stmt_bind_param($stmt, 'sssss', $username, $hash, $nama, $role, $gmail);
                $okMessage = 'Anggota berhasil ditambahkan.';
            }

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                flash('success', $okMessage);
                redirect('anggota.php');
            }

            $error = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            flash('danger', 'Data anggota gagal disimpan: ' . $error);
        }
    }
}

page_start($id ? 'Edit Anggota' : 'Tambah Anggota', 'anggota.php', 'admin');
render_flash();
?>
<div class="form-page-head">
    <div class="section-head"><a href="anggota.php"><?= icon('back', 20) ?> Kembali ke Anggota</a><span></span></div>
    <h2><?= $id ? 'Edit Data Anggota' : 'Tambah Data Anggota' ?></h2>
    <p><?= $id ? 'Perbarui informasi akun anggota yang dipilih.' : 'Masukkan informasi anggota baru yang akan ditambahkan ke sistem.' ?></p>
</div>

<div class="form-card">
    <form method="post" class="form-grid" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= $id ?>">

        <div>
            <label for="nama_lengkap">Nama Lengkap</label>
            <input id="nama_lengkap" name="nama_lengkap" maxlength="100" value="<?= e($edit['nama_lengkap'] ?? '') ?>" placeholder="Masukkan nama lengkap" required autofocus>
        </div>
        <div>
            <label for="username">Username</label>
            <input id="username" name="username" maxlength="50" value="<?= e($edit['username'] ?? '') ?>" placeholder="Masukkan username" required>
        </div>
        <div>
            <label for="gmail">Email</label>
            <input id="gmail" name="gmail" type="email" maxlength="200" value="<?= e($edit['gmail'] ?? '') ?>" placeholder="Masukkan email" required>
        </div>
        <div>
            <label for="password">Password <?= $id ? '<span class="muted">(kosongkan jika tidak diubah)</span>' : '' ?></label>
            <input id="password" name="password" type="password" minlength="6" placeholder="<?= $id ? 'Password baru (opsional)' : 'Minimal 6 karakter' ?>" <?= $id ? '' : 'required' ?> autocomplete="new-password">
        </div>
        <div>
            <label for="password_confirm">Konfirmasi Password <?= $id ? '<span class="muted">(jika mengganti password)</span>' : '' ?></label>
            <input id="password_confirm" name="password_confirm" type="password" minlength="6" placeholder="Ulangi password" <?= $id ? '' : 'required' ?> autocomplete="new-password">
        </div>
        <div class="form-actions">
            <a class="btn ghost" href="anggota.php">Batal</a>
            <button class="btn primary" type="submit"><?= $id ? 'Update Data' : 'Simpan Data' ?></button>
        </div>
    </form>
</div>
<?php page_end(); ?>