<?php
session_start();
require_once __DIR__ . '/../models/koneksi.php';
require_once __DIR__ . '/../models/auth.php';
if (!empty($_SESSION['id'])) redirect($_SESSION['role'] === 'admin' ? '../views/dashboard_admin.php' : '../views/dashboard_siswa.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_lengkap'] ?? '');
    $gmail = trim($_POST['gmail'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    if ($nama === '' || !filter_var($gmail, FILTER_VALIDATE_EMAIL) || $username === '' || strlen($password) < 6) {
        $error = 'Lengkapi data dengan benar. Password minimal 6 karakter.';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE username = ? OR gmail = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'ss', $username, $gmail);
        mysqli_stmt_execute($stmt);
        $exists = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if ($exists) {
            $error = 'Username atau email sudah digunakan.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $role = 'siswa';
            $stmt = mysqli_prepare($conn, 'INSERT INTO users (username,password,nama_lengkap,role,gmail) VALUES (?,?,?,?,?)');
            mysqli_stmt_bind_param($stmt, 'sssss', $username, $hash, $nama, $role, $gmail);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                flash('success', 'Pendaftaran berhasil. Silakan masuk.');
                redirect('login.php');
            }
            $error = 'Pendaftaran gagal: ' . mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Daftar — Aksaraya</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>

<body class="auth-page">
    <div class="auth-wrap">
        <section class="auth-brand">
            <img src="../assets/aksaraya.jpeg" alt="Aksaraya">
        </section>
        <section class="auth-panel">
            <h1>Selamat Datang!</h1>
            <p class="auth-subtitle">Silahkan buat akun untuk melanjutkan</p>

            <?php if ($error): ?>
                <div class="alert danger">
                    <?= e($error) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group"><label>Nama Lengkap</label><input name="nama_lengkap" placeholder="Masukkan nama lengkap" value="<?= e($_POST['nama_lengkap'] ?? '') ?>" required></div>
                <div class="form-group"><label>Email</label><input name="gmail" type="email" placeholder="Masukkan email" value="<?= e($_POST['gmail'] ?? '') ?>" required></div>
                <div class="form-group"><label>Username</label><input name="username" placeholder="Masukkan username" value="<?= e($_POST['username'] ?? '') ?>" required></div>
                <div class="form-group"><label>Password</label>
                    <div class="password-wrap"><input id="password" name="password" type="password" placeholder="Buat password" required>
                        <button type="button" class="icon-btn password-toggle" data-target="password" aria-label="Tampilkan password"><?= icon('eye', 20) ?></button>
                    </div>
                </div>
                <button class="btn primary full" type="submit">Daftar</button>
                <p class="auth-bottom">Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
            </form>
        </section>
    </div>
    <script src="../assets/app.js"></script>
</body>

</html>