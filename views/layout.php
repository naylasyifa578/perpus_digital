<?php
require_once __DIR__ . '/../models/auth.php';
require_once __DIR__ . '/../models/migrate.php';
require_once __DIR__ . '/../models/notifikasi.php';
function page_start(string $title, string $active, string $role): void
{
    global $conn;
    if (isset($conn)) {
        ensure_profile_photo($conn);
        ensure_notifikasi_tables($conn);
    }
    $isAdmin = $role === 'admin';
    $items = $isAdmin ? [
        'dashboard_admin.php' => ['home', 'Dashboard'],
        'buku.php' => ['book', 'Data Buku'],
        'kategori.php' => ['tag', 'Kategori Buku'],
        'anggota.php' => ['users', 'Anggota'],
        'peminjaman.php' => ['swap', 'Peminjaman'],
        'pengembalian.php' => ['return', 'Pengembalian'],
        'profile.php' => ['user', 'Profil'],
        'notifikasi.php' => ['bell', 'Notifikasi'],
    ] : [
        'dashboard_siswa.php' => ['home', 'Dashboard'],
        'kategori_siswa.php' => ['tag', 'Kategori'],
        'riwayat.php' => ['return', 'Riwayat'],
        'koleksi.php' => ['heart', 'Koleksi Saya'],
        'profile.php' => ['user', 'Profil'],
        'notifikasi.php' => ['bell', 'Notifikasi'],
    ];
    $base = basename($_SERVER['PHP_SELF']);
?>
    <!doctype html>
    <html lang="id">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title><?= e($title) ?> — Aksaraya</title>
        <link rel="stylesheet" href="../assets/style.css">
    </head>

    <body class="app-page">
        <div class="app-shell">
            <aside class="sidebar">
                <div class="brand"><img src="../assets/aksaraya.jpeg" alt="Aksaraya">
                    <div><strong>Aksaraya</strong><small>Perpustakaan Digital</small></div>
                </div><button class="sidebar-toggle icon-btn" type="button"><?= icon('menu', 22) ?></button>
                <nav>
                    <?php foreach ($items as $file => $item): ?><a class="nav-item <?= $active === $file ? 'active' : '' ?>" href="<?= $file ?>"><?= icon($item[0], 18) ?><span><?= e($item[1]) ?></span></a><?php endforeach; ?></nav><a class="nav-item logout" href="../controller/logout.php"><?= icon('logout', 18) ?><span>Keluar</span></a>
            </aside>
            <main class="main-content">
                <header class="topbar">
                    <div class="top-left"><button class="sidebar-toggle icon-btn mobile-only" type="button"><?= icon('menu', 23) ?></button>
                        <h1><?= e($title) ?></h1>
                    </div>
                    <form class="top-search" action="<?= $isAdmin ? 'buku.php' : 'kategori_siswa.php' ?>" method="get"><?= icon('search', 18) ?><input name="q" placeholder="Cari sesuatu…" value="<?= e($_GET['q'] ?? '') ?>"></form>
                    <div class="top-user"><a class="icon-btn notif-trigger" href="notifikasi.php" title="Notifikasi"><?= icon('bell', 20) ?><?php $notifCount = unread_notification_count($conn, (int)($_SESSION['id'] ?? 0));
                                                                                                                                            if ($notifCount): ?><span class="notif-badge"><?= min($notifCount, 99) ?></span><?php endif; ?></a><span class="avatar"><?php if (!empty($_SESSION['foto_profil'])): ?><img src="../assets/<?= e($_SESSION['foto_profil']) ?>" alt="Foto profil"><?php else: ?><?= strtoupper(substr($_SESSION['nama_lengkap'] ?? 'U', 0, 1)) ?><?php endif; ?></span>
                        <div><strong><?= e($_SESSION['nama_lengkap'] ?? 'User') ?></strong></div>
                    </div>
                </header>
                <section class="page-content">
                    <?php
                }
                function page_end(): void
                {
                    echo '</section></main></div><script src="../assets/app.js"></script></body></html>';
                }
                function render_flash(): void
                {
                    if ($f = consume_flash()): ?><div class="alert <?= $f['type'] === 'success' ? 'success' : 'danger' ?>"><?= e($f['message']) ?></div><?php endif;
                                                                                                                                                }
