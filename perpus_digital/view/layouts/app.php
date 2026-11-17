<?php
$isAdmin = $role === 'admin';

// Menu sidebar: route => [ikon, label]
$menu = $isAdmin ? [
    'dashboard'   => ['home', 'Dashboard'],
    'buku'        => ['book', 'Data Buku'],
    'kategori'    => ['tag', 'Kategori Buku'],
    'anggota'     => ['users', 'Anggota'],
    'peminjaman'  => ['swap', 'Peminjaman'],
    'pengembalian' => ['return', 'Pengembalian'],
    'profil'      => ['user', 'Profil'],
    'notifikasi'  => ['bell', 'Notifikasi'],
] : [
    'dashboard'   => ['home', 'Dashboard'],
    'katalog'     => ['tag', 'Peminjaman'],
    'riwayat'     => ['file', 'Riwayat'],
    'pengembalian' => ['return', 'Pengembalian'],
    'koleksi'     => ['heart', 'Koleksi Saya'],
    'profil'      => ['user', 'Profil'],
    'notifikasi'  => ['bell', 'Notifikasi'],
];
$searchRoute = $isAdmin ? 'buku' : 'katalog';
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($title) ?> — Aksaraya</title>
    <link rel="stylesheet" href="<?= e(asset('style.css')) ?>">
</head>

<body class="app-page">
    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand"><img src="<?= e(asset('aksaraya.jpeg')) ?>" alt="Aksaraya">
                <div><strong>Aksaraya</strong><small>Perpustakaan Digital</small></div>
            </div>
            <button class="sidebar-toggle icon-btn" type="button"><?= icon('menu', 22) ?></button>
            <nav>
                <?php foreach ($menu as $route => $item): ?>
                    <a class="nav-item <?= $active === $route ? 'active' : '' ?>" href="<?= e(url($route)) ?>"><?= icon($item[0], 18) ?><span><?= e($item[1]) ?></span></a>
                <?php endforeach; ?>
            </nav>
            <a class="nav-item logout" href="<?= e(url('logout')) ?>"><?= icon('logout', 18) ?><span>Keluar</span></a>
        </aside>
        <main class="main-content">
            <header class="topbar">
                <div class="top-left">
                    <button class="sidebar-toggle icon-btn mobile-only" type="button"><?= icon('menu', 23) ?></button>
                    <h1><?= e($title) ?></h1>
                </div>
                <form class="top-search" action="index.php" method="get">
                    <input type="hidden" name="page" value="<?= e($searchRoute) ?>">
                    <?= icon('search', 18) ?>
                    <input name="q" placeholder="Cari sesuatu…" value="<?= e($_GET['q'] ?? '') ?>">
                </form>
                <div class="top-user">
                    <a class="icon-btn notif-trigger" href="<?= e(url('notifikasi')) ?>" title="Notifikasi"><?= icon('bell', 20) ?>
                        <?php if ($notifCount > 0): ?><span class="notif-badge"><?= min($notifCount, 99) ?></span><?php endif; ?>
                    </a>
                    <span class="avatar">
                        <?php if ($userPhoto): ?>
                            <img src="<?= e(cover_url($userPhoto)) ?>" alt="Foto profil">
                        <?php else: ?>
                            <?= e(\App\View\Format::initial($userName)) ?>
                        <?php endif; ?>
                    </span>
                    <div><strong><?= e($userName) ?></strong></div>
                </div>
            </header>
            <section class="page-content">
                <?= partial('flash', ['flash' => $flash]) ?>
                <?= $content ?>
            </section>
        </main>
    </div>
    <script src="<?= e(asset('app.js')) ?>"></script>
</body>

</html>
