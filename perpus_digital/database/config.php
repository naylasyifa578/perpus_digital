<?php

/**
 * Konfigurasi aplikasi (database + pengaturan umum).
 *
 * Nilai koneksi dapat ditimpa lewat environment variable (DB_HOST, DB_USER,
 * DB_PASS, DB_NAME, DB_PORT) sehingga kredensial tidak perlu ditulis di sini.
 * Default di bawah cocok untuk XAMPP / Laragon (lokal).
 * Jangan commit kredensial asli ke repository publik.
 */
return [
    // Koneksi database
    'database' => [
        'host'     => getenv('DB_HOST') ?: 'localhost',
        'user'     => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASS') !== false ? (string) getenv('DB_PASS') : '',
        'database' => getenv('DB_NAME') ?: 'db_perpustakaan',
        'port'     => (int) (getenv('DB_PORT') ?: 3306),
        'charset'  => 'utf8mb4',
    ],

    // Pengaturan umum aplikasi
    'app' => [
        'name'          => 'Aksaraya',
        'tagline'       => 'Perpustakaan Digital',
        'timezone'      => 'Asia/Jakarta',
        'loan_days'     => 14,       // lama peminjaman (hari)
        'sanction_days' => 14,       // lama sanksi bila terlambat (hari)
        'max_upload'    => 2097152,  // batas upload gambar: 2 MB
        'admin_code'    => 'ADMIN-AKSARAYA67', // kode rahasia untuk mendaftar sebagai admin (ubah sebelum dipakai)
        'debug'         => false,    // true = tampilkan detail error (hanya saat pengembangan)
    ],
];
