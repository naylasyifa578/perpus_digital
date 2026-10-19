<?php
require_once __DIR__ . '/../models/koneksi.php';
require_once __DIR__ . '/../models/auth.php';
require_once __DIR__ . '/../models/notifikasi.php';
require_once __DIR__ . '/../models/migrate.php';
require_once __DIR__ . '/layout.php';
require_login();
ensure_notifikasi_tables($conn);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'read') {
        mark_notification_read($conn, (int)$_POST['id'], (int)$_SESSION['id']);
    }
    if (($_POST['action'] ?? '') === 'read_all') {
        mark_all_notifications_read($conn, (int)$_SESSION['id']);
    }
    redirect('notifikasi.php');
}
$s = mysqli_prepare($conn, 'SELECT * FROM notifikasi WHERE user_id=? ORDER BY created_at DESC LIMIT 100');
mysqli_stmt_bind_param($s, 'i', $_SESSION['id']);
mysqli_stmt_execute($s);
$rows = mysqli_stmt_get_result($s);
page_start('Notifikasi', 'notifikasi.php', $_SESSION['role']);
render_flash();
?><div class="page-heading">
    <div>
        <h2>Notifikasi</h2>
        <p>Informasi terbaru dari aktivitas perpustakaan.</p>
    </div>
    <form method="post"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="read_all"><button class="btn ghost" type="submit">Tandai Semua Dibaca</button></form>
</div>
<div class="notification-list"><?php while ($n = mysqli_fetch_assoc($rows)): ?><div class="notification-item <?= $n['is_read'] ? 'read' : 'unread' ?>">
            <div class="notification-icon"><?= icon($n['type'] === 'sanksi' ? 'alert' : ($n['type'] === 'peminjaman' ? 'swap' : 'bell'), 20) ?></div>
            <div class="notification-content"><strong><?= e($n['title']) ?></strong>
                <p><?= e($n['message']) ?></p><small><?= format_date($n['created_at']) ?> · <?= date('H:i', strtotime($n['created_at'])) ?></small>
            </div><?php if (!$n['is_read']): ?><form method="post"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="read"><input type="hidden" name="id" value="<?= $n['id'] ?>"><button class="btn ghost small">Tandai Dibaca</button></form><?php endif; ?>
        </div><?php endwhile;
                                if (mysqli_num_rows($rows) === 0): ?><div class="empty">Belum ada notifikasi.</div><?php endif; ?></div><?php page_end(); ?>