<div class="page-heading">
    <div>
        <h2>Kelola User</h2>
        <p>Daftar akun siswa yang terdaftar.</p>
    </div>
    <a class="btn primary" href="<?= e(url('anggota.form')) ?>"><?= icon('plus', 18) ?> Tambah Anggota</a>
</div>
<form class="filter-bar compact" method="get" action="index.php">
    <input type="hidden" name="page" value="anggota">
    <div class="search-field">
        <?= icon('search', 19) ?>
        <input name="q" value="<?= e($keyword) ?>" placeholder="Cari nama user...">
    </div>
    <button class="btn primary" type="submit">Cari</button>
</form>
<div class="table-card">
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Transaksi</th>
                    <th>Terdaftar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($members as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= e($r['nama_lengkap']) ?></strong></td>
                        <td><?= e($r['gmail']) ?></td>
                        <td><?= e($r['username']) ?></td>
                        <td><?= (int) $r['transaksi'] ?></td>
                        <td><?= e(format_date($r['created_at'])) ?></td>
                        <td class="actions">
                            <a class="icon-btn" href="<?= e(url('anggota.detail', ['id' => $r['id']])) ?>" title="Detail"><?= icon('eye', 17) ?></a>
                            <a class="icon-btn" href="<?= e(url('anggota.form', ['id' => $r['id']])) ?>" title="Edit"><?= icon('edit', 17) ?></a>
                            <form method="post" action="<?= e(url('anggota.delete')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                <button class="icon-btn danger-text" type="submit" title="Hapus" data-confirm="Hapus akun siswa ini?"><?= icon('trash', 17) ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$members): ?>
                    <tr>
                        <td colspan="7" class="empty">Belum ada siswa.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
