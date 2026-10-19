<?php
require_once __DIR__ . '/../models/koneksi.php';
require_once __DIR__ . '/../models/auth.php';
require_once __DIR__ . '/../models/migrate.php';
require_once __DIR__ . '/../models/notifikasi.php';
require_once __DIR__ . '/layout.php';

require_role('siswa');
ensure_due_dates_14_days($conn);
ensure_collection_table($conn);

page_start('Dashboard', 'dashboard_siswa.php', 'siswa');
render_flash();

ensure_sanction_expired_notification($conn, (int)$_SESSION['id']);
$blocked_until = get_borrow_block_until($conn, (int)$_SESSION['id']);

$sanctionNotif = null;
$sn = mysqli_prepare(
    $conn,
    "SELECT id, title, message
     FROM notifikasi
     WHERE user_id=? AND type='sanksi' AND is_read=0
     ORDER BY created_at DESC
     LIMIT 1"
);
mysqli_stmt_bind_param($sn, 'i', $_SESSION['id']);
mysqli_stmt_execute($sn);
$sanctionNotif = mysqli_fetch_assoc(mysqli_stmt_get_result($sn));
mysqli_stmt_close($sn);

$showSanction = !!($sanctionNotif && $blocked_until);

/*
 * Foto menu dashboard dapat diganti langsung di sini.
 * Contoh:
 * 'assets/menu-kategori.jpg'
 *
 * Jika file foto belum tersedia, sistem otomatis menampilkan icon.
 */
$dashboardMenus = [
    [
        'title' => 'Kategori',
        'description' => 'Lihat kategori dan pilih buku yang ingin dibaca.',
        'url' => 'kategori_siswa.php',
        'image' => '',
        'icon' => 'tag'
    ],
    [
        'title' => 'Riwayat',
        'description' => 'Lihat riwayat peminjaman dan status buku.',
        'url' => 'riwayat.php',
        'image' => '',
        'icon' => 'return'
    ],
    [
        'title' => 'Koleksi Saya',
        'description' => 'Lihat buku yang sudah disimpan ke koleksi.',
        'url' => 'koleksi.php',
        'image' => '',
        'icon' => 'heart'
    ],
    [
        'title' => 'Profil',
        'description' => 'Lihat dan kelola informasi profil akun.',
        'url' => 'profile.php',
        'image' => '',
        'icon' => 'user'
    ],
];
?>

<div class="welcome-banner student">
    <div>
        <h2>Halo, <?= e($_SESSION['nama_lengkap']) ?></h2>
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
        <a class="student-dashboard-menu-card" href="<?= e($menu['url']) ?>">
            <div class="student-dashboard-menu-image">
                <?php if (!empty($menu['image'])): ?>
                    <img src="../<?= e($menu['image']) ?>" alt="<?= e($menu['title']) ?>">
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

<?php if ($showSanction && $blocked_until): ?>
    <div class="feature-modal open">
        <div class="feature-modal-backdrop"></div>
        <div class="feature-modal-dialog">
            <div class="feature-modal-icon"><?= icon('alert', 28) ?></div>
            <h3>Terdeteksi Keterlambatan</h3>
            <p>Pengembalian buku kamu terdeteksi terlambat. Untuk menjaga ketertiban peminjaman, fitur peminjaman dinonaktifkan selama 14 hari.</p>
            <div class="sanction-summary">
                <span>Status</span>
                <b>Peminjaman Dinonaktifkan</b>
                <span>Sanksi sampai</span>
                <b><?= format_date($blocked_until) ?></b>
            </div>
            <div class="modal-actions">
                <form method="post" action="notifikasi.php">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="read">
                    <input type="hidden" name="id" value="<?= e($sanctionNotif['id']) ?>">
                    <button class="btn primary" type="submit">Mengerti</button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php page_end(); ?>
