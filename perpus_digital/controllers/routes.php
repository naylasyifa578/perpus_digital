<?php

use App\Controllers\AnggotaController;
use App\Controllers\AuthController;
use App\Controllers\BukuController;
use App\Controllers\DashboardController;
use App\Controllers\KatalogController;
use App\Controllers\KategoriController;
use App\Controllers\KoleksiController;
use App\Controllers\NotifikasiController;
use App\Controllers\PeminjamanController;
use App\Controllers\PengembalianController;
use App\Controllers\ProfilController;
use App\Controllers\UnduhController;

/**
 * Daftar route: 'nama.route' => [Controller::class, 'method'].
 * Diakses lewat index.php?page=nama.route
 */
return [
    // Autentikasi
    'login'    => [AuthController::class, 'login'],
    'register' => [AuthController::class, 'register'],
    'logout'   => [AuthController::class, 'logout'],

    // Dashboard
    'dashboard' => [DashboardController::class, 'index'],

    // Data buku (admin)
    'buku'        => [BukuController::class, 'index'],
    'buku.form'   => [BukuController::class, 'form'],
    'buku.save'   => [BukuController::class, 'save'],
    'buku.delete' => [BukuController::class, 'delete'],

    // Kategori (admin)
    'kategori'        => [KategoriController::class, 'index'],
    'kategori.save'   => [KategoriController::class, 'save'],
    'kategori.delete' => [KategoriController::class, 'delete'],

    // Anggota (admin)
    'anggota'        => [AnggotaController::class, 'index'],
    'anggota.detail' => [AnggotaController::class, 'detail'],
    'anggota.form'   => [AnggotaController::class, 'form'],
    'anggota.save'   => [AnggotaController::class, 'save'],
    'anggota.delete' => [AnggotaController::class, 'delete'],

    // Peminjaman
    'peminjaman'         => [PeminjamanController::class, 'admin'],
    'riwayat'            => [PeminjamanController::class, 'riwayat'],
    'peminjaman.detail'  => [PeminjamanController::class, 'detail'],
    'peminjaman.request' => [PeminjamanController::class, 'request'],
    'peminjaman.cancel'  => [PeminjamanController::class, 'cancel'],
    'peminjaman.return'  => [PeminjamanController::class, 'giveBack'],
    'peminjaman.status'  => [PeminjamanController::class, 'status'],
    'peminjaman.form'    => [PeminjamanController::class, 'form'],
    'peminjaman.save'    => [PeminjamanController::class, 'save'],
    'peminjaman.delete'  => [PeminjamanController::class, 'delete'],

    // Pengembalian (admin: catat pengembalian; siswa: kembalikan buku yang dipinjam)
    'pengembalian'         => [PengembalianController::class, 'index'],
    'pengembalian.process' => [PengembalianController::class, 'process'],

    // Siswa: katalog dan koleksi
    'katalog'        => [KatalogController::class, 'index'],
    'koleksi'        => [KoleksiController::class, 'index'],
    'koleksi.toggle' => [KoleksiController::class, 'toggle'],

    // Notifikasi
    'notifikasi'         => [NotifikasiController::class, 'index'],
    'notifikasi.read'    => [NotifikasiController::class, 'read'],
    'notifikasi.readall' => [NotifikasiController::class, 'readAll'],

    // Profil
    'profil'          => [ProfilController::class, 'index'],
    'profil.foto'     => [ProfilController::class, 'foto'],
    'profil.update'   => [ProfilController::class, 'update'],
    'profil.password' => [ProfilController::class, 'password'],

    // Unduh dokumen SVG
    'unduh' => [UnduhController::class, 'index'],
];
