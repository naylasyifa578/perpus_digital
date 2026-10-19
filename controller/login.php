<?php
session_start();
require_once __DIR__ . '/../models/koneksi.php';
require_once __DIR__ . '/../models/auth.php';
require_once __DIR__ . '/../models/migrate.php';
ensure_profile_photo($conn);
require_once __DIR__ . '/../models/user.php';

if (!empty($_SESSION['id'])) {
    redirect($_SESSION['role'] === 'admin' ? '../views/dashboard_admin.php' : '../views/dashboard_siswa.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $user = getUserByUsername($conn, $username);

    if ($user) {
        $stored = trim((string)$user['password']);
        $valid = password_verify($password, $stored) || hash_equals($stored, $password);
        if ($valid) {
            session_regenerate_id(true);
            $_SESSION['id'] = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];
            if (!password_verify($password, $stored)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, 'UPDATE users SET password = ? WHERE id = ?');
                mysqli_stmt_bind_param($stmt, 'si', $newHash, $user['id']);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
            redirect($user['role'] === 'admin' ? '../views/dashboard_admin.php' : '../views/dashboard_siswa.php');
        }
    }
    $error = 'Username atau password salah.';
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Masuk — Aksaraya</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>

<body class="auth-page">
    <div class="auth-wrap">
        <section class="auth-brand"><img src="../assets/aksaraya.jpeg" alt="Aksaraya"></section>
        <section class="auth-panel">
            <h1>Selamat Datang!</h1>
            <p class="auth-subtitle">Silahkan masuk untuk melanjutkan</p>
            <?php if ($f = consume_flash()): ?><div class="alert <?= $f['type'] === 'success' ? 'success' : 'danger' ?>"><?= e($f['message']) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert danger"><?= e($error) ?></div><?php endif; ?>
            <form method="post" novalidate>
                <div class="form-group"><label for="username">Email / Username</label><input id="username" name="username" type="text" placeholder="Masukan email atau username" value="<?= e($_POST['username'] ?? '') ?>" required autofocus></div>
                <div class="form-group"><label for="password">Password</label>
                    <div class="password-wrap"><input id="password" name="password" type="password" placeholder="Masukan password" required>
                        <button type="button" class="icon-btn password-toggle" data-target="password" aria-label="Tampilkan password"><?= icon('eye', 20) ?></button>
                    </div>
                </div>
                <div class="auth-forgot"><a href="#" onclick="alert('Silakan hubungi admin untuk reset password.');return false;">Lupa password?</a></div>
                <button class="btn primary full" type="submit">Masuk</button>
                <p class="auth-bottom">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
            </form>
        </section>
    </div>
    <script src="../assets/app.js"></script>
</body>

</html>