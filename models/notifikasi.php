<?php

require_once __DIR__ . '/auth.php';

/**
 * Membuat tabel notifikasi jika belum tersedia.
 */
function ensure_notifikasi_tables(mysqli $conn): void
{
    $query = "
        CREATE TABLE IF NOT EXISTS notifikasi (
            id INT NOT NULL AUTO_INCREMENT,
            user_id INT NOT NULL,
            type VARCHAR(40) NOT NULL,
            ref_id INT NULL,
            title VARCHAR(160) NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_notif_user (user_id, is_read, created_at)
        ) ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ";

    mysqli_query($conn, $query);
}

/**
 * Membuat notifikasi untuk pengguna.
 */
function create_notification(
    mysqli $conn,
    int $userId,
    string $type,
    string $title,
    string $message,
    ?int $refId = null
): void {
    $query = "
        INSERT INTO notifikasi
            (user_id, type, ref_id, title, message)
        VALUES (?, ?, ?, ?, ?)
    ";

    $stmt = mysqli_prepare($conn, $query);

    if (!$stmt) {
        error_log(
            'Gagal menyiapkan query notifikasi: ' .
            mysqli_error($conn)
        );
        return;
    }

    mysqli_stmt_bind_param(
        $stmt,
        'isiss',
        $userId,
        $type,
        $refId,
        $title,
        $message
    );

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/**
 * Mengirim notifikasi kepada semua admin.
 */
function notify_admins(
    mysqli $conn,
    string $type,
    string $title,
    string $message,
    ?int $refId = null
): void {
    $query = "SELECT id FROM users WHERE role = 'admin'";

    $result = mysqli_query($conn, $query);

    if (!$result) {
        error_log(
            'Gagal mengambil data admin: ' .
            mysqli_error($conn)
        );
        return;
    }

    while ($user = mysqli_fetch_assoc($result)) {
        create_notification(
            $conn,
            (int) $user['id'],
            $type,
            $title,
            $message,
            $refId
        );
    }

    mysqli_free_result($result);
}

/**
 * Menghitung jumlah notifikasi yang belum dibaca.
 */
function unread_notification_count(
    mysqli $conn,
    int $userId
): int {
    $query = "
        SELECT COUNT(*) AS total
        FROM notifikasi
        WHERE user_id = ? AND is_read = 0
    ";

    $stmt = mysqli_prepare($conn, $query);

    if (!$stmt) {
        error_log(
            'Gagal menghitung notifikasi: ' .
            mysqli_error($conn)
        );
        return 0;
    }

    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return (int) ($data['total'] ?? 0);
}

/**
 * Menandai satu notifikasi sebagai sudah dibaca.
 */
function mark_notification_read(
    mysqli $conn,
    int $id,
    int $userId
): void {
    $query = "
        UPDATE notifikasi
        SET is_read = 1
        WHERE id = ? AND user_id = ?
    ";

    $stmt = mysqli_prepare($conn, $query);

    if (!$stmt) {
        error_log(
            'Gagal menandai notifikasi: ' .
            mysqli_error($conn)
        );
        return;
    }

    mysqli_stmt_bind_param($stmt, 'ii', $id, $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/**
 * Menandai semua notifikasi pengguna sebagai sudah dibaca.
 */
function mark_all_notifications_read(
    mysqli $conn,
    int $userId
): void {
    $query = "
        UPDATE notifikasi
        SET is_read = 1
        WHERE user_id = ?
    ";

    $stmt = mysqli_prepare($conn, $query);

    if (!$stmt) {
        error_log(
            'Gagal menandai semua notifikasi: ' .
            mysqli_error($conn)
        );
        return;
    }

    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/**
 * Memeriksa apakah masa sanksi pengguna telah berakhir.
 * Jika sudah berakhir, buat notifikasi satu kali.
 */
function ensure_sanction_expired_notification(
    mysqli $conn,
    int $userId
): void {
    /*
     * Ambil tanggal berakhirnya sanksi.
     */
    $query = "
        SELECT blocked_until
        FROM users
        WHERE id = ?
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $query);

    if (!$stmt) {
        error_log(
            'Gagal memeriksa sanksi pengguna: ' .
            mysqli_error($conn)
        );
        return;
    }

    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if (!$user) {
        return;
    }

    $blockedUntil = $user['blocked_until'] ?? null;

    /*
     * Tidak ada sanksi atau sanksi belum berakhir.
     */
    if (
        empty($blockedUntil) ||
        $blockedUntil >= date('Y-m-d')
    ) {
        return;
    }

    /*
     * Gunakan tanggal sanksi sebagai identitas notifikasi.
     */
    $refId = (int) strtotime($blockedUntil);

    /*
     * Pastikan notifikasi untuk sanksi tersebut
     * belum pernah dibuat.
     */
    $query = "
        SELECT id
        FROM notifikasi
        WHERE user_id = ?
          AND type = 'sanksi_berakhir'
          AND ref_id = ?
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $query);

    if (!$stmt) {
        error_log(
            'Gagal memeriksa notifikasi sanksi: ' .
            mysqli_error($conn)
        );
        return;
    }

    mysqli_stmt_bind_param($stmt, 'ii', $userId, $refId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $notification = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    /*
     * Jika sudah ada, tidak perlu membuat ulang.
     */
    if ($notification) {
        return;
    }

    /*
     * Buat notifikasi bahwa sanksi telah berakhir.
     */
    create_notification(
        $conn,
        $userId,
        'sanksi_berakhir',
        'Sanksi Berakhir',
        'Masa sanksi telah berakhir. Anda sudah dapat meminjam buku kembali.',
        $refId
    );
}