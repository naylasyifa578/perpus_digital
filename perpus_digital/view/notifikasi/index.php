<div class="page-heading">
    <div>
        <h2>Notifikasi</h2>
        <p>Informasi terbaru dari aktivitas perpustakaan.</p>
    </div>
    <form method="post" action="<?= e(url('notifikasi.readall')) ?>">
        <?= csrf_field() ?>
        <button class="btn ghost" type="submit">Tandai Semua Dibaca</button>
    </form>
</div>
<div class="notification-list">
    <?php foreach ($notifications as $n): ?>
        <?php
        $isRead = (int) $n['is_read'] === 1;
        $iconName = $n['type'] === 'sanksi' ? 'alert' : ($n['type'] === 'peminjaman' ? 'swap' : 'bell');
        ?>
        <div class="notification-item <?= $isRead ? 'read' : 'unread' ?>">
            <div class="notification-icon"><?= icon($iconName, 20) ?></div>
            <div class="notification-content">
                <strong><?= e($n['title']) ?></strong>
                <p><?= e($n['message']) ?></p>
                <small><?= e(format_date($n['created_at'])) ?> · <?= e(date('H:i', strtotime($n['created_at']))) ?></small>
            </div>
            <?php if (!$isRead): ?>
                <form method="post" action="<?= e(url('notifikasi.read')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
                    <button class="btn ghost small" type="submit">Tandai Dibaca</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <?php if (!$notifications): ?>
        <div class="empty">Belum ada notifikasi.</div>
    <?php endif; ?>
</div>
