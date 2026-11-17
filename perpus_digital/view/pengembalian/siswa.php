<div class="page-heading">
    <div>
        <h2>Pengembalian Buku</h2>
        <p>Buku yang sedang Anda pinjam dapat dikembalikan kapan saja, tanpa harus menunggu jatuh tempo (<?= (int) $loanDays ?> hari). Pengembalian setelah jatuh tempo membuat peminjaman dinonaktifkan sementara.</p>
    </div>
</div>

<div class="table-card">
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Buku</th>
                    <th>Tanggal Pinjam</th>
                    <th>Jatuh Tempo</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($borrowed as $i => $r): ?>
                    <?php $due = due_date($r['tanggal_pinjam']); ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><a class="icon-btn" href="<?= e(url('peminjaman.detail', ['id' => $r['id']])) ?>" title="Detail"><?= icon('eye', 17) ?></a> <?= e($r['judul']) ?></td>
                        <td><?= e(format_date_with_day($r['tanggal_pinjam'])) ?></td>
                        <td>
                            <?= e(format_date_with_day($due)) ?>
                            <?php if ($due < date('Y-m-d')): ?><span class="badge danger">Terlambat</span><?php endif; ?>
                        </td>
                        <td>
                            <form method="post" action="<?= e(url('peminjaman.return')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                <input type="hidden" name="back" value="pengembalian">
                                <button class="btn primary small" type="submit" data-confirm="Kembalikan buku ini sekarang?">Kembalikan Buku</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$borrowed): ?>
                    <tr>
                        <td colspan="5" class="empty">Tidak ada buku yang sedang dipinjam.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="section-head">
    <h3>Riwayat pengembalian</h3><a href="<?= e(url('riwayat')) ?>">Lihat semua riwayat</a>
</div>
<div class="table-card">
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Buku</th>
                    <th>Tanggal Pinjam</th>
                    <th>Tanggal Dikembalikan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($returned as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><a class="icon-btn" href="<?= e(url('peminjaman.detail', ['id' => $r['id']])) ?>" title="Detail"><?= icon('eye', 17) ?></a> <?= e($r['judul']) ?></td>
                        <td><?= e(format_date_with_day($r['tanggal_pinjam'])) ?></td>
                        <td><?= e(format_date_with_day($r['tanggal_pengembalian_aktual'] ?? null)) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$returned): ?>
                    <tr>
                        <td colspan="4" class="empty">Belum ada buku yang dikembalikan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
