<?php
// Migrasi kecil yang dibutuhkan fitur tambahan V3. Aman dijalankan berulang kali.
function ensure_collection_table(mysqli $conn): void
{
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS koleksi_buku (
        id INT NOT NULL AUTO_INCREMENT,
        user_id INT NOT NULL,
        buku_id INT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_koleksi_user_buku (user_id, buku_id),
        KEY idx_koleksi_buku (buku_id),
        CONSTRAINT fk_koleksi_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_koleksi_buku FOREIGN KEY (buku_id) REFERENCES buku(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Database awal hanya mempunyai pengajuan/disetujui/dipinjam/dikembalikan/ditolak.
    // 'dibatalkan' dibutuhkan agar pembatalan siswa tetap tercatat sebagai histori.
    $check = mysqli_query($conn, "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='peminjaman' AND COLUMN_NAME='status'");
    $col = $check ? mysqli_fetch_assoc($check) : null;
    if ($col && strpos($col['COLUMN_TYPE'], "'dibatalkan'") === false) {
        @mysqli_query($conn, "ALTER TABLE peminjaman MODIFY status ENUM('pengajuan','disetujui','dipinjam','dikembalikan','ditolak','dibatalkan') NOT NULL DEFAULT 'pengajuan'");
    }
}

function ensure_book_cover_column(mysqli $conn): void
{
    $check = mysqli_query($conn, "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='buku' AND COLUMN_NAME='gambar' LIMIT 1");
    if (!$check || mysqli_num_rows($check) === 0) {
        @mysqli_query($conn, "ALTER TABLE buku ADD COLUMN gambar VARCHAR(255) NULL AFTER kategori_id");
    }
}


function ensure_category_image_column(mysqli $conn): void
{
    $check = mysqli_query($conn, "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='kategori' AND COLUMN_NAME='gambar' LIMIT 1");
    if (!$check || mysqli_num_rows($check) === 0) @mysqli_query($conn, "ALTER TABLE kategori ADD COLUMN gambar VARCHAR(255) NULL AFTER Deskripsi");
}

function ensure_borrow_sanction(mysqli $conn): void
{
    $check = mysqli_query($conn, "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='blocked_until' LIMIT 1");
    if (!$check || mysqli_num_rows($check) === 0) {
        @mysqli_query($conn, "ALTER TABLE users ADD COLUMN blocked_until DATE NULL AFTER role");
    }
}

function get_borrow_block_until(mysqli $conn, int $userId): ?string
{
    ensure_borrow_sanction($conn);
    $s = mysqli_prepare($conn, 'SELECT blocked_until FROM users WHERE id=? LIMIT 1');
    mysqli_stmt_bind_param($s, 'i', $userId);
    mysqli_stmt_execute($s);
    $r = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
    mysqli_stmt_close($s);
    $until = $r['blocked_until'] ?? null;
    if ($until && $until < date('Y-m-d')) return null;
    return $until ?: null;
}

function calculate_due_date(string $borrowDate): string
{
    return date('Y-m-d', strtotime($borrowDate . ' +14 days'));
}

function ensure_due_dates_14_days(mysqli $conn): void
{
    // Menjaga data lama agar aturan jatuh tempo selalu 14 hari.
    @mysqli_query($conn, "UPDATE peminjaman SET tanggal_kembali = DATE_ADD(tanggal_pinjam, INTERVAL 14 DAY)
        WHERE tanggal_kembali <> DATE_ADD(tanggal_pinjam, INTERVAL 14 DAY)");
}

function apply_late_return_sanction(mysqli $conn, int $userId, string $dueDate, string $returnDate): int
{
    $lateDays = max(0, (int)((strtotime($returnDate) - strtotime($dueDate)) / 86400));
    if ($lateDays <= 0) return 0;

    // Setiap keterlambatan, berapa pun harinya, membuat fitur peminjaman
    // dinonaktifkan selama 14 hari sejak tanggal pengembalian.
    $blockedUntil = date('Y-m-d', strtotime($returnDate . ' +14 days'));
    $s = mysqli_prepare($conn, 'UPDATE users SET blocked_until=? WHERE id=?');
    mysqli_stmt_bind_param($s, 'si', $blockedUntil, $userId);
    mysqli_stmt_execute($s);
    mysqli_stmt_close($s);
    return $lateDays;
}

function ensure_profile_photo(mysqli $conn): void
{
    $check = mysqli_query($conn, "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='foto_profil' LIMIT 1");
    if (!$check || mysqli_num_rows($check) === 0) @mysqli_query($conn, "ALTER TABLE users ADD COLUMN foto_profil VARCHAR(255) NULL AFTER gmail");
}

function ensure_all_features(mysqli $conn): void
{
    ensure_collection_table($conn);
    ensure_book_cover_column($conn);
    ensure_category_image_column($conn);
    ensure_borrow_sanction($conn);
    ensure_profile_photo($conn);
    if (function_exists('ensure_notifikasi_tables')) ensure_notifikasi_tables($conn);
}
