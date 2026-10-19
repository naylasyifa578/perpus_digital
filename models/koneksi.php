<?php
mysqli_report(MYSQLI_REPORT_OFF);

$host = 'sql209.infinityfree.com';
$user = 'if0_42752618';
$password = 'nayla578';
$database = 'f0_42752618_db_perpustakaan';
$port = 3306;

$conn = mysqli_connect($host, $user, $password, $database, $port);
if (!$conn) {
    die('Koneksi database gagal: ' . htmlspecialchars(mysqli_connect_error()));
}
mysqli_set_charset($conn, 'utf8mb4');
