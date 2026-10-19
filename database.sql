-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 10 Sep 2026 pada 02.29
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `notifikasi`;
DROP TABLE IF EXISTS `koleksi_buku`;
DROP TABLE IF EXISTS `pengembalian`;
DROP TABLE IF EXISTS `peminjaman`;
DROP TABLE IF EXISTS `buku`;
DROP TABLE IF EXISTS `kategori`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS=1;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_perpustakaan`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `buku`
--

CREATE TABLE `buku` (
  `id` int(11) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `pengarang` varchar(100) NOT NULL,
  `penerbit` varchar(100) NOT NULL,
  `tahun_terbit` year(4) NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `jumlah_stok` int(11) NOT NULL DEFAULT 0,
  `kategori_id` int(11) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data untuk tabel `buku`
--
-- Seed ini menetapkan koleksi awal tepat 50 buku (5 buku per kategori).
-- Jika database sudah berisi data lama, hapus/bersihkan data buku terlebih dahulu sebelum import seed ini.

INSERT INTO `buku` (`id`, `judul`, `pengarang`, `penerbit`, `tahun_terbit`, `isbn`, `jumlah_stok`, `kategori_id`, `gambar`, `created_at`) VALUES
(1, 'Laskar Pelangi', 'Andrea Hirata', 'Bentang Pustaka', '2005', '979-3062-79-7', 5, 1, 'covers/LaskarPelangi.jpeg', '2026-08-20 01:22:20'),
(2, 'Bumi', 'Tere Liye', 'Gramedia Pustaka Utama', '2014', '9786020301120', 5, 1, 'covers/Bumi.jpeg', '2026-09-03 03:29:01'),
(3, 'Bulan', 'Tere Liye', 'Gramedia Pustaka Utama', '2015', '9786020314113', 4, 1, 'covers/Bulan.jpeg', '2026-09-03 03:29:01'),
(4, 'Matahari', 'Tere Liye', 'Gramedia Pustaka Utama', '2016', '9786020385939', 3, 1, 'covers/Matahari.jpeg', '2026-09-03 03:29:01'),
(5, 'Bintang', 'Tere Liye', 'Gramedia Pustaka Utama', '2017', '9786020385946', 5, 1, 'covers/Bintang.jpeg', '2026-09-03 03:29:01'),
(6, 'Cantik Itu Luka', 'Eka Kurniawan', 'Gramedia Pustaka Utama', '2002', '978-602-03-1389-4', 3, 2, 'covers/cantikituluka.jpeg', '2026-08-20 01:22:20'),
(7, 'Saman', 'Ayu Utami', 'Kepustakaan Populer Gramedia', '1998', '9789799023240', 4, 2, 'covers/saman.jpeg', '2026-09-03 03:29:01'),
(8, 'Larung', 'Ayu Utami', 'Kepustakaan Populer Gramedia', '2001', '9789799023295', 3, 2, 'covers/larung.jpeg', '2026-09-03 03:29:01'),
(9, 'Pulang', 'Leila S. Chudori', 'Kepustakaan Populer Gramedia', '2012', '9789799109920', 5, 2, 'covers/pulang.jpeg', '2026-09-03 03:29:01'),
(10, 'Laut Bercerita', 'Leila S. Chudori', 'Kepustakaan Populer Gramedia', '2017', '9786024246945', 5, 2, 'covers/lautbercerita.jpeg', '2026-09-03 03:29:01'),
(11, 'Negeri 5 Menara', 'Ahmad Fuadi', 'Gramedia Pustaka Utama', '2009', '978-979-22-4860-0', 5, 3, 'covers/Negri5Menara.jpeg', '2026-08-20 01:22:20'),
(12, 'Matematika untuk SMK Kelas X', 'Sukino', 'Erlangga', '2021', '9786022418757', 5, 3, 'covers/Matematika10.jpeg', '2026-09-03 03:29:01'),
(13, 'Matematika untuk SMK Kelas XI', 'Sukino', 'Erlangga', '2021', '9786022418764', 4, 3, 'https://covers.openlibrary.org/b/isbn/9786022418764-L.jpg', '2026-09-03 03:29:01'),
(14, 'Matematika untuk SMK Kelas XII', 'Sukino', 'Erlangga', '2022', '9786022418771', 5, 3, 'covers/matematikakelas12.jpeg', '2026-09-03 03:29:01'),
(15, 'Bahasa Indonesia untuk SMK', 'E. Kosasih', 'Erlangga', '2020', '9786022418894', 4, 3, 'covers/BahasaIndonesia.jpeg', '2026-09-03 03:29:01'),
(16, 'Sapiens', 'Yuval Noah Harari', 'Harper', '2011', '978-0062316097 ', 3, 4, 'https://covers.openlibrary.org/b/isbn/9780062316097-L.jpg', '2026-08-20 01:22:20'),
(17, 'Sejarah Indonesia Modern', 'M.C. Ricklefs', 'Serambi', '2008', '9789790241151', 4, 4, 'https://covers.openlibrary.org/b/isbn/9789790241151-L.jpg', '2026-09-03 03:29:01'),
(18, 'Sejarah Dunia', 'J.M. Roberts', 'Universitas Indonesia Press', '2015', '9789794565988', 3, 4, 'https://covers.openlibrary.org/b/isbn/9789794565988-L.jpg', '2026-09-03 03:29:01'),
(19, 'Sejarah Nasional Indonesia', 'Sartono Kartodirdjo', 'Balai Pustaka', '1975', '9789794071448', 5, 4, 'https://covers.openlibrary.org/b/isbn/9789794071448-L.jpg', '2026-09-03 03:29:01'),
(20, 'Indonesia dalam Arus Sejarah', 'Taufik Abdullah', 'Ichtiar Baru', '2012', '9789794618179', 4, 4, 'https://covers.openlibrary.org/b/isbn/9789794618179-L.jpg', '2026-09-03 03:29:01'),
(21, 'Steve Jobs', 'Walter Isaacson', 'Simon & Schuster', '2008', '978-1451648539', 3, 5, 'covers/SteveJobs.jpeg', '2026-08-20 01:22:20'),
(22, 'Soekarno: An Autobiography', 'Cindy Adams', 'Media Pressindo', '2014', '9789799116045', 5, 5, 'covers/Soekarno.jpeg', '2026-09-03 03:29:01'),
(23, 'Bung Hatta: Pribadinya dalam Kenangan', 'Nugroho Notosusanto', 'UI Press', '2012', '9789794563229', 4, 5, 'covers/BungHatta.jpeg', '2026-09-03 03:29:01'),
(24, 'Biografi BJ Habibie', 'A. Makmur Makka', 'THC Mandiri', '2018', '9786025112345', 3, 5, 'covers/BiografiBJHABIBI.jpeg', '2026-09-03 03:29:01'),
(25, 'Habibie & Ainun', 'Bacharuddin Jusuf Habibie', 'THC Mandiri', '2010', '9789791250054', 5, 5, 'covers/Habini&Ainun.jpeg', '2026-09-03 03:29:01'),
(26, 'A Brief History of Time', 'Stephen Hawking ', 'Bantam', '2014', '978-0553380163', 3, 6, 'covers/abriefhistorioftime.jpeg', '2026-08-20 01:22:20'),
(27, 'Cosmos', 'Carl Sagan', 'Random House', '1980', '9780345539434', 5, 6, 'covers/cosmos.jpeg', '2026-09-03 03:29:01'),
(28, 'The Selfish Gene', 'Richard Dawkins', 'Oxford University Press', '1976', '9780198788607', 4, 6, 'covers/theselfishgene.jpeg', '2026-09-03 03:29:01'),
(29, 'The Origin of Species', 'Charles Darwin', 'Penguin Classics', '2009', '9780140432053', 3, 6, 'covers/theoriginofpecies.jpeg', '2026-09-03 03:29:01'),
(30, 'The Gene', 'Siddhartha Mukherjee', 'Scribner', '2016', '9781476733500', 4, 6, 'covers/thegene.jpeg', '2026-09-03 03:29:01'),
(31, 'Clean Code', 'Robert C. Martin', 'Prentice Hall ', '2008', '978-0132350884 ', 4, 7, 'https://covers.openlibrary.org/b/isbn/9780132350884-L.jpg', '2026-08-20 01:22:20'),
(32, 'The Pragmatic Programmer', 'Andrew Hunt', 'Addison-Wesley', '1999', '9780135957059', 5, 7, 'https://covers.openlibrary.org/b/isbn/9780135957059-L.jpg', '2026-09-03 03:29:01'),
(33, 'Design Patterns', 'Erich Gamma', 'Addison-Wesley', '1994', '9780201633610', 4, 7, 'https://covers.openlibrary.org/b/isbn/9780201633610-L.jpg', '2026-09-03 03:29:01'),
(34, 'Introduction to Algorithms', 'Thomas H. Cormen', 'MIT Press', '2009', '9780262033848', 3, 7, 'https://covers.openlibrary.org/b/isbn/9780262033848-L.jpg', '2026-09-03 03:29:01'),
(35, 'JavaScript: The Good Parts', 'Douglas Crockford', 'OReilly Media', '2008', '9780596517748', 4, 7, 'https://covers.openlibrary.org/b/isbn/9780596517748-L.jpg', '2026-09-03 03:29:01'),
(36, 'Ayat-Ayat Cinta', 'Habiburrahman El Shirazy', 'Republika', '2004', '979-3210-16-1', 3, 8, 'covers/ayatayatcinta.jpeg', '2026-08-20 01:22:20'),
(37, 'Tafsir Al-Misbah', 'M. Quraish Shihab', 'Lentera Hati', '2002', '9789799048813', 4, 8, 'covers/TafsirAlMisbah.jpeg', '2026-09-03 03:29:01'),
(38, 'Membumikan Al-Quran', 'M. Quraish Shihab', 'Mizan', '1992', '9789794330135', 5, 8, 'covers/MembumikanAlquran.jpeg', '2026-09-03 03:29:01'),
(39, 'Wawasan Al-Quran', 'M. Quraish Shihab', 'Mizan', '1996', '9789794330920', 3, 8, 'covers/WawasanAlQuran.jpeg', '2026-09-03 03:29:01'),
(40, 'Fiqih Islam', 'Sulaiman Rasjid', 'Sinar Baru Algensindo', '2019', '9789797301744', 4, 8, 'covers/fiqihIslam.jpeg', '2026-09-03 03:29:01'),
(41, 'Thinking, Fast and Slow', 'Daniel Kahneman Farrar', 'Straus and Giroux', '2011', '978-0374533557', 3, 9, 'covers/thinkingfastandslow.jpeg', '2026-08-20 01:22:20'),
(42, 'Psikologi Kepribadian', 'Alwisol', 'UMM Press', '2019', '9789797961453', 5, 9, 'covers/PsikologiKepribadian.jpeg', '2026-09-03 03:29:01'),
(43, 'Psikologi Pendidikan', 'John W. Santrock', 'Salemba Humanika', '2018', '9786028555685', 4, 9, 'covers/PsikologiPendidikan.jpeg', '2026-09-03 03:29:01'),
(44, 'Psikologi Sosial', 'Robert A. Baron', 'Erlangga', '2012', '9789790158478', 3, 9, 'covers/psikologisosial.jpeg', '2026-09-03 03:29:01'),
(45, 'Thinking, Fast and Slow', 'Daniel Kahneman', 'Farrar Straus and Giroux', '2011', '9780374533557', 5, 9, 'covers/thinkingfastandslow2.jpeg', '2026-09-03 03:29:01'),
(46, 'The Psychology of Money', 'Morgan Housel', 'Harriman House', '2020', '978-0857197689', 3, 10, 'covers/ThePshycologyOf Money.jpeg', '2026-08-20 01:22:20'),
(47, 'Ekonomi Mikro', 'Sadono Sukirno', 'Rajawali Pers', '2018', '9789797696328', 4, 10, 'covers/MikroEkonomi.jpeg', '2026-09-03 03:29:01'),
(48, 'Ekonomi Makro', 'Sadono Sukirno', 'Rajawali Pers', '2019', '9789797696335', 3, 10, 'covers/MakroEkonomi.jpeg', '2026-09-03 03:29:01'),
(49, 'Pengantar Ekonomi', 'N. Gregory Mankiw', 'Salemba Empat', '2018', '9789790618341', 5, 10, 'covers/PengantarEkonomi.jpeg', '2026-09-03 03:29:01'),
(50, 'Principles of Economics', 'N. Gregory Mankiw', 'Cengage Learning', '2020', '9780357541596', 4, 10, 'covers/PrinciplesOfEconomics.jpeg', '2026-09-03 03:29:01');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori`
--

CREATE TABLE `kategori` (
  `id` int(11) NOT NULL,
  `Kategori` varchar(50) NOT NULL,
  `Deskripsi` varchar(200) NOT NULL,
  `gambar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data untuk tabel `kategori`
--

INSERT INTO `kategori` (`id`, `Kategori`, `Deskripsi`) VALUES
(1, 'Fiksi', 'Buku berupa cerita rekaan'),
(2, 'Sastra', 'Buku yang berisi karya cerita, puisi, atau tulisan yang mengandung nilai seni dan kehidupan'),
(3, 'Pendidikan', 'Buku untuk pembelajaran'),
(4, 'Sejarah', 'Buku tentang peristiwa sejarah'),
(5, 'Biografi', 'Buku tentang kehidupan seseorang	'),
(6, 'Sains', 'Buku tentang ilmu pengetahuan'),
(7, 'Teknologi', 'Buku tentang ilmu teknologi dan komputer'),
(8, 'Agama', 'Buku tentang keagamaan'),
(9, 'Psikologi', 'Buku tentang pikiran dan perilaku manusia'),
(10, 'Ekonomi', 'Buku tentang Ekonomi');

-- --------------------------------------------------------

--
-- Struktur dari tabel `peminjaman`
--

CREATE TABLE `peminjaman` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `buku_id` int(11) NOT NULL,
  `tanggal_pinjam` date NOT NULL,
  `tanggal_kembali` date NOT NULL,
  `status` enum('pengajuan','disetujui','dipinjam','dikembalikan','ditolak') NOT NULL DEFAULT 'pengajuan',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengembalian`
--

CREATE TABLE `pengembalian` (
  `id_user` int(11) NOT NULL,
  `peminjaman_id` int(11) NOT NULL,
  `tanggal_pengembalian` date NOT NULL,
  `denda` decimal(10,2) DEFAULT 0.00,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('admin','siswa') NOT NULL DEFAULT 'siswa',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `gmail` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `role`, `created_at`, `gmail`) VALUES
(1, 'admin', '12345678\r\n', 'Administrator', 'admin', '2026-04-14 09:22:16', 'user@gmail.com'),
(4, 'users', 'whenya100', 'lala', 'siswa', '2026-06-22 10:18:41', 'lala@gmail.com'),
(5, 'nayluw', 'malingpangsit', 'nanayyo', 'siswa', '2026-06-22 14:31:36', 'nayluw@gmail.com'),
(6, 'nayla', '$2y$10$s19eHM.zNggfvM.MoTpjhuDdL4xdAxjR7LP3OpYRpjXyUFv12ejYq', 'Nayla', 'siswa', '2026-08-24 02:26:15', 'nayla@gmail.com'),
(7, 'ayukinar', '$2y$10$koyqTyu/k/.dCts4Ye1r2OtApYQdfMyzwLjQ1dY/DwVSwLbavo1Z.', 'kinar ayu', 'siswa', '2026-08-25 23:40:54', 'kinarrayya@gmail.com'),
(8, 'peminjam', '$2y$10$crLKDo5SMUFoZowKtr6iReSFdiDL.9N4uHWkFbOdrqEj7gTPCvJ26', 'kirani', 'siswa', '2026-08-26 02:35:56', 'kirani@gmail.com');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `buku`
--
ALTER TABLE `buku`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kategori_id` (`kategori_id`);

--
-- Indeks untuk tabel `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `buku_id` (`buku_id`);

--
-- Indeks untuk tabel `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD PRIMARY KEY (`id_user`),
  ADD KEY `peminjaman_id` (`peminjaman_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `buku`
--
ALTER TABLE `buku`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT untuk tabel `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `peminjaman`
--
ALTER TABLE `peminjaman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `pengembalian`
--
ALTER TABLE `pengembalian`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `buku`
--
ALTER TABLE `buku`
  ADD CONSTRAINT `buku_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD CONSTRAINT `peminjaman_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `peminjaman_ibfk_2` FOREIGN KEY (`buku_id`) REFERENCES `buku` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD CONSTRAINT `pengembalian_ibfk_1` FOREIGN KEY (`peminjaman_id`) REFERENCES `peminjaman` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- Aksaraya: struktur terbaru
ALTER TABLE `peminjaman`
  MODIFY `status` enum('pengajuan','disetujui','dipinjam','dikembalikan','ditolak','dibatalkan') NOT NULL DEFAULT 'pengajuan';

ALTER TABLE `buku`
  ADD COLUMN `gambar` varchar(255) DEFAULT NULL AFTER `kategori_id`;

ALTER TABLE `users`
  ADD COLUMN `blocked_until` date DEFAULT NULL AFTER `role`,
  ADD COLUMN `foto_profil` varchar(255) DEFAULT NULL AFTER `gmail`;

CREATE TABLE `koleksi_buku` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `buku_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_koleksi_user_buku` (`user_id`,`buku_id`),
  KEY `idx_koleksi_buku` (`buku_id`),
  CONSTRAINT `fk_koleksi_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_koleksi_buku` FOREIGN KEY (`buku_id`) REFERENCES `buku` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notifikasi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(40) NOT NULL,
  `ref_id` int(11) DEFAULT NULL,
  `title` varchar(160) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`,`is_read`,`created_at`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
