<?php
/*
 * Foto menu dashboard dapat diganti lewat kunci 'image' (path relatif dari folder proyek,
 * mis. 'assets/menu-kategori.jpg'). Jika kosong, ikon yang ditampilkan.
 */
$dashboardMenus = [
    ['title' => 'Peminjaman Buku', 'description' => 'Pilih kategori, cari buku, lalu ajukan peminjaman.', 'route' => 'katalog', 'image' => '', 'icon' => 'tag'],
    ['title' => 'Pengembalian Buku', 'description' => 'Kembalikan buku yang sedang Anda pinjam.', 'route' => 'pengembalian', 'image' => '', 'icon' => 'return'],
    ['title' => 'Riwayat', 'description' => 'Lihat riwayat peminjaman dan status buku.', 'route' => 'riwayat', 'image' => '', 'icon' => 'file'],
    ['title' => 'Koleksi Saya', 'description' => 'Lihat buku yang sudah disimpan ke koleksi.', 'route' => 'koleksi', 'image' => '', 'icon' => 'heart'],
    ['title' => 'Profil', 'description' => 'Lihat dan kelola informasi profil akun.', 'route' => 'profil', 'image' => '', 'icon' => 'user'],
];
?>
<div class="welcome-banner student">
    <div>
        <h2>Halo, <?= e($userName) ?></h2>
        <p>Selamat datang di Aksaraya</p>
    </div>
</div>

<div class="student-dashboard-heading">
    <div>
        <h3>Menu Aksaraya</h3>
        <p>Pilih menu yang ingin kamu gunakan.</p>
    </div>
</div>

<div class="student-dashboard-menu-grid">
    <?php foreach ($dashboardMenus as $menu): ?>
        <a class="student-dashboard-menu-card" href="<?= e(url($menu['route'])) ?>">
            <div class="student-dashboard-menu-image">
                <?php if ($menu['image'] !== ''): ?>
                    <img src="<?= e($menu['image']) ?>" alt="<?= e($menu['title']) ?>">
                <?php else: ?>
                    <?= icon($menu['icon'], 34) ?>
                <?php endif; ?>
            </div>
            <div class="student-dashboard-menu-body">
                <h4><?= e($menu['title']) ?></h4>
                <p><?= e($menu['description']) ?></p>
                <span class="student-dashboard-menu-link">Buka menu <?= icon('arrow-right', 15) ?></span>
            </div>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($sanctionNotif !== null && $blockedUntil !== null): ?>
    <div class="feature-modal open">
        <div class="feature-modal-backdrop"></div>
        <div class="feature-modal-dialog">
            <div class="feature-modal-icon"><?= icon('alert', 28) ?></div>
            <h3>Terdeteksi Keterlambatan</h3>
            <p>Pengembalian buku kamu terdeteksi terlambat. Untuk menjaga ketertiban peminjaman, fitur peminjaman dinonaktifkan selama <?= (int) $sanctionDays ?> hari.</p>
            <div class="sanction-summary">
                <span>Status</span>
                <b>Peminjaman Dinonaktifkan</b>
                <span>Sanksi sampai</span>
                <b><?= e(format_date($blockedUntil)) ?></b>
            </div>
            <div class="modal-actions">
                <form method="post" action="<?= e(url('notifikasi.read')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $sanctionNotif['id'] ?>">
                    <button class="btn primary" type="submit">Mengerti</button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>
