<?php
function getUserByUsername(mysqli $conn, string $username): ?array
{
    $stmt = mysqli_prepare($conn, 'SELECT id, username, password, nama_lengkap, role, gmail FROM users WHERE username = ? OR gmail = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'ss', $username, $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result) ?: null;
    mysqli_stmt_close($stmt);
    return $user;
}
