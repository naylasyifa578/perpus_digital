<div class="page-heading">
    <div>
        <h2>Pengembalian</h2>
        <p>Buku dapat dikembalikan kapan saja setelah status peminjaman aktif, tanpa harus menunggu jatuh tempo <?= (int) $loanDays ?> hari. Keterlambatan tetap dikenai sanksi peminjaman <?= (int) $sanctionDays ?> hari.</p>
    </div>
</div>
<div class="table-card">
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Peminjam</th>
                    <th>Buku</th>
                    <th>Jatuh Tempo</th>
                    <th>Form Pengembalian</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($borrowed as $i => $r): ?>
                    <?php $due = due_date($r['tanggal_pinjam']); ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= e($r['nama_lengkap']) ?></td>
                        <td><?= e($r['judul']) ?></td>
                        <td><?= e(format_date_with_day($due)) ?></td>
                        <td>
                            <form method="post" action="<?= e(url('pengembalian.process')) ?>" class="return-form" data-due-date="<?= e($due) ?>" data-sanction-days="<?= (int) $sanctionDays ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="peminjaman_id" value="<?= (int) $r['id'] ?>">
                                <label class="sr-only">Tanggal Pengembalian</label>
                                <input type="date" name="tanggal_pengembalian" class="return-date-input" value="<?= e(date('Y-m-d')) ?>" max="<?= e(date('Y-m-d')) ?>">
                                <small class="return-date-day" aria-live="polite"></small>
                                <span class="return-sanction-label" aria-live="polite">Sanksi: Tidak ada</span>
                                <input name="keterangan" placeholder="Keterangan">
                                <button class="btn primary small" type="submit">Proses</button>
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
