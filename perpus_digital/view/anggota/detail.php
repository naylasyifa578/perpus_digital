<div class="section-head"><a href="<?= e(url('anggota')) ?>"><?= icon('back', 20) ?> Kembali</a>
    <h3>Detail User</h3><span></span>
</div>
<div class="profile-layout">
    <div class="profile-card">
        <div class="profile-avatar large">
            <?php if (!empty($member['foto_profil'])): ?>
                <img src="<?= e(cover_url($member['foto_profil'])) ?>" alt="Foto profil">
            <?php else: ?>
                <?= e(\App\View\Format::initial($member['nama_lengkap'])) ?>
            <?php endif; ?>
        </div>
        <h2><?= e($member['nama_lengkap']) ?></h2>
        <p><?= e($member['username']) ?></p>
    </div>
    <div class="profile-panels">
        <div class="detail-card">
            <div class="detail-body">
                <div><b>Nama Lengkap</b> <span><?= e($member['nama_lengkap']) ?></span></div>
                <div><b>Username</b><span><?= e($member['username']) ?></span></div>
                <div><b>Email</b><span><?= e($member['gmail'] ?? '-') ?></span></div>
                <div><b>Terdaftar</b><span><?= e(format_date($member['created_at'])) ?></span></div>
                <div><b>Status Peminjaman</b><span><?= $isBlocked ? 'Dibatasi sampai ' . e(format_date($member['blocked_until'])) : 'Aktif' ?></span></div>
            </div>
        </div>
        <div class="detail-actions">
            <a class="btn outline" href="<?= e(url('anggota.form', ['id' => $member['id']])) ?>"><?= icon('edit', 17) ?> Edit Anggota</a>
            <a class="btn primary" href="<?= e(url('unduh', ['type' => 'user', 'id' => $member['id']])) ?>"><?= icon('save', 17) ?> Simpan ke Perangkat</a>
        </div>
    </div>
</div>
<div class="table-card user-history-card">
    <div class="section-head">
        <h3>Riwayat Peminjaman</h3><span></span>
    </div>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Buku</th>
                    <th>Tanggal Pinjam</th>
                    <th>Jatuh Tempo</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $h): ?>
                    <tr>
                        <td><?= e($h['judul']) ?></td>
                        <td><?= e(format_date_with_day($h['tanggal_pinjam'])) ?></td>
                        <td><?= e(format_date_with_day(due_date($h['tanggal_pinjam']))) ?></td>
                        <td><span class="badge <?= e(status_class($h['status'])) ?>"><?= e(status_label($h['status'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$history): ?>
                    <tr>
                        <td colspan="4" class="empty">Belum ada riwayat peminjaman.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
