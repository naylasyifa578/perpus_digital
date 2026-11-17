# Aksaraya — Perpustakaan Digital (OOP + MVC)

Aplikasi perpustakaan digital berbasis PHP + MySQL dengan pola **MVC** dan **OOP**.

## Cara menjalankan (XAMPP)

1. Salin folder `perpus_digital` ke `htdocs`.
2. Buka phpMyAdmin, pilih tab **Import**, lalu import `database/database.sql`
   (database `db_perpustakaan` dibuat otomatis oleh file tersebut).
3. Sesuaikan `database/config.php` bila perlu (default: `localhost`, `root`, tanpa password).
4. Buka `http://localhost/perpus_digital/` (otomatis menuju halaman login).

PHP yang dibutuhkan: **8.1 atau lebih baru**, dengan ekstensi `mysqli` (mysqlnd).

## Struktur folder

```
perpus_digital/
├── index.php        Pintu masuk: autoloader, session, router
├── assets/          CSS, JS, gambar, cover buku, foto profil
├── controllers/     Controller + Router, Request, Auth, Csrf, routes.php
├── models/          Model (query), service (aturan bisnis), Database, Config, Session
├── view/            Template tampilan per fitur + View, Format, Icon, functions.php
└── database/        database.sql dan config.php
```

Isi tiap folder:

- **controllers/** — `AuthController`, `DashboardController`, `BukuController`,
  `KategoriController`, `AnggotaController`, `PeminjamanController`,
  `PengembalianController`, `KatalogController`, `KoleksiController`,
  `NotifikasiController`, `ProfilController`, `UnduhController`; kelas pendukung
  `Controller` (induk), `Router`, `Request`, `Auth`, `Csrf`; dan `routes.php`.
- **models/** — model: `User`, `Buku`, `Kategori`, `Peminjaman`, `Pengembalian`,
  `Koleksi`, `Notifikasi` (hanya query). Service: `PeminjamanService`,
  `SanctionService`, `AuthService`, `ImageUploader`, `SvgDocument` (aturan bisnis).
  Dasar: `Model`, `Database`, `Schema`, `Config`, `Session`, `Result`.
- **view/** — template (`layouts/`, `partials/`, `auth/`, `dashboard/`, `buku/`,
  `kategori/`, `anggota/`, `peminjaman/`, `pengembalian/`, `katalog/`, `koleksi/`,
  `notifikasi/`, `profil/`), serta `View`, `Format`, `Icon`, `functions.php`.

## Alur sebuah request

`index.php?page=buku.save` → `Router` mencari route di `controllers/routes.php` →
`BukuController::save()` → memakai model `Buku` dan `ImageUploader` →
redirect, atau render template di `view/buku/`.

## Berjalan offline

Seluruh aplikasi berjalan lewat localhost tanpa internet: tidak ada font, script, atau gambar
yang dimuat dari internet. Cover buku memakai file di `assets/covers/`; buku tanpa cover
menampilkan gambar bawaan `assets/no-cover.svg`.

## Akun

| Peran | Username | Password |
|-------|----------|----------|
| Admin | `admin`  | `12345678` |
| Siswa | `users`  | `whenya100` |

Pada halaman masuk pilih peran (**Siswa** / **Admin**) sesuai akun. Akun baru dapat dibuat lewat
halaman **Daftar**; mendaftar sebagai **Admin** memerlukan kode admin yang diatur di
`database/config.php` (`app.admin_code`, bawaan `ADMIN-AKSARAYA`; ganti sebelum dipakai).
Password lama yang masih berupa teks biasa tetap bisa login dan otomatis di-hash saat login pertama.

## Fitur sesuai soal

| Fitur | Admin | Siswa |
|-------|:-----:|:-----:|
| Daftar | ✔ (dengan kode admin) | ✔ |
| Login (pilih peran) | ✔ | ✔ |
| Pemilihan menu | ✔ | ✔ |
| CRUD Transaksi | ✔ tambah, lihat, ubah status, hapus | ✔ ajukan, lihat, batalkan, hapus riwayat batal/ditolak |
| CRUD Data Buku | ✔ | ✘ |
| CRUD Kelola Anggota | ✔ | ✘ |
| Pencarian | ✔ | ✔ |
| Pengembalian buku | ✔ (catat pengembalian) | ✔ (menu Pengembalian) |
