-- =====================================================================
-- db_peminjaman_alat (FIXED / disesuaikan dengan SPK Paket 1 - RPL)
-- Perubahan dari file lama:
--   1. Tabel tb_transaksi dihapus (tidak ada di daftar fitur SPK, redundan
--      dengan tb_peminjaman & bikin query jadi ganda untuk info yang sama).
--   2. tb_peminjaman.status_peminjaman ditambah nilai 'Ditolak' (alur
--      persetujuan oleh Petugas: Diajukan -> Dipinjam / Ditolak -> Selesai).
--   3. tb_pengembalian ditambah kolom `status` (Menunggu/Selesai) dan
--      `denda` supaya alur "Peminjam mengembalikan alat" (mengajukan) dan
--      "Petugas CRUD/memantau pengembalian" (memverifikasi + hitung denda)
--      bisa dibedakan.
--   4. Tabel tb_log_aktivitas baru, untuk fitur "Log Aktifitas" (Admin).
--   5. Semua tabel pakai utf8mb4 + InnoDB, FOREIGN KEY ditambahkan supaya
--      relasi data konsisten (ON DELETE RESTRICT/CASCADE sesuai kebutuhan).
-- =====================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `db_peminjaman_alat` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `db_peminjaman_alat`;

-- --------------------------------------------------------
DROP TABLE IF EXISTS `tb_transaksi`;
DROP TABLE IF EXISTS `tb_pengembalian`;
DROP TABLE IF EXISTS `tb_detail_peminjaman`;
DROP TABLE IF EXISTS `tb_peminjaman`;
DROP TABLE IF EXISTS `tb_alat`;
DROP TABLE IF EXISTS `tb_kategori`;
DROP TABLE IF EXISTS `tb_log_aktivitas`;
DROP TABLE IF EXISTS `tb_user`;

-- --------------------------------------------------------
CREATE TABLE `tb_user` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `level_user` enum('Admin','Petugas','Peminjam') NOT NULL,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password masih plaintext ('12345' / '123') seperti data asli. login.php dibuat agar
-- otomatis MENERIMA plaintext ini lalu meng-upgrade ke hash aman (password_hash) begitu
-- user tersebut berhasil login pertama kali -- jadi data ini langsung bisa dipakai login
-- tanpa perlu reset manual, dan setelah dipakai sekali kolom password sudah otomatis ter-hash.
INSERT INTO `tb_user` (`id_user`, `username`, `password`, `nama_lengkap`, `level_user`) VALUES
(1, 'admin', '12345', 'Administrator', 'Admin'),
(2, 'petugas1', '12345', 'Petugas Lab', 'Petugas'),
(3, 'andi_h', '12345', 'Andi Hidayat', 'Peminjam'),
(4, 'rina_w', '12345', 'Rina Wati', 'Peminjam'),
(5, 'budi_s', '12345', 'Budi Santoso', 'Peminjam'),
(6, 'Abdul', '123', 'Abdul Rano', 'Peminjam');

-- --------------------------------------------------------
CREATE TABLE `tb_kategori` (
  `id_kategori` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(50) NOT NULL,
  `lokasi_penyimpanan` varchar(50) NOT NULL,
  `penanggung_jawab` varchar(50) NOT NULL,
  `keterangan` text NOT NULL,
  PRIMARY KEY (`id_kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `tb_kategori` (`id_kategori`, `nama_kategori`, `lokasi_penyimpanan`, `penanggung_jawab`, `keterangan`) VALUES
(1, 'Elektronik', 'Rak A1', 'Andi', 'Peralatan berbasis listrik aktif'),
(2, 'Jaringan', 'Rak A2', 'Budi', 'Kabel dan perangkat networking'),
(3, 'Multimedia', 'Rak B1', 'Siti', 'Kamera dan alat rekam suara'),
(4, 'Perkakas', 'Rak C1', 'Joko', 'Alat tangan mekanik'),
(5, 'Pengukuran', 'Rak C2', 'Dewi', 'Alat ukur kelistrikan');

-- --------------------------------------------------------
CREATE TABLE `tb_alat` (
  `id_alat` int(11) NOT NULL AUTO_INCREMENT,
  `id_kategori` int(11) NOT NULL,
  `nama_alat` varchar(100) NOT NULL,
  `jumlah_stok` int(11) NOT NULL,
  `kondisi` varchar(30) NOT NULL,
  PRIMARY KEY (`id_alat`),
  KEY `id_kategori` (`id_kategori`),
  CONSTRAINT `fk_alat_kategori` FOREIGN KEY (`id_kategori`) REFERENCES `tb_kategori` (`id_kategori`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `tb_alat` (`id_alat`, `id_kategori`, `nama_alat`, `jumlah_stok`, `kondisi`) VALUES
(1, 1, 'Proyektor Epson', 10, 'Baik'),
(2, 2, 'Tang Crimping', 15, 'Baik'),
(3, 2, 'Kabel UTP (Roll)', 8, 'Baik'),
(4, 3, 'Kamera DSLR Canon', 5, 'Baik'),
(5, 1, 'Laptop Lenovo', 12, 'Baik'),
(8, 1, 'Laptop Lenovo Asus', 10, 'Baik'),
(9, 2, 'Kamera DSLR Canon Nikon', 3, 'Baik'),
(11, 5, 'Meteran', 20, 'Baik'),
(12, 4, 'Kunci Inggris', 6, 'Baik'),
(13, 4, 'Obeng', 3, 'Baik'),
(14, 5, 'Penggaris', 6, 'Baik');

-- --------------------------------------------------------
CREATE TABLE `tb_peminjaman` (
  `id_peminjaman` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `id_petugas` int(11) DEFAULT NULL COMMENT 'petugas yang menyetujui/menolak',
  `tanggal_pinjam` date NOT NULL,
  `tanggal_rencana_kembali` date NOT NULL,
  `status_peminjaman` enum('Diajukan','Dipinjam','Ditolak','Selesai') NOT NULL DEFAULT 'Diajukan',
  `catatan_petugas` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id_peminjaman`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `fk_peminjaman_user` FOREIGN KEY (`id_user`) REFERENCES `tb_user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `tb_peminjaman` (`id_peminjaman`, `id_user`, `id_petugas`, `tanggal_pinjam`, `tanggal_rencana_kembali`, `status_peminjaman`) VALUES
(1, 3, 2, '2026-08-01', '2026-08-03', 'Selesai'),
(2, 4, 2, '2026-08-05', '2026-08-07', 'Selesai'),
(3, 3, 2, '2026-08-10', '2026-08-12', 'Dipinjam'),
(4, 4, NULL, '2026-08-15', '2026-08-17', 'Diajukan'),
(5, 5, 2, '2026-08-19', '2026-08-25', 'Dipinjam'),
(7, 2, NULL, '2026-09-21', '2026-09-21', 'Diajukan'),
(8, 2, NULL, '2026-09-21', '2026-09-22', 'Diajukan');

-- --------------------------------------------------------
CREATE TABLE `tb_detail_peminjaman` (
  `id_detail` int(11) NOT NULL AUTO_INCREMENT,
  `id_peminjaman` int(11) NOT NULL,
  `id_alat` int(11) NOT NULL,
  `jumlah_pinjam` int(11) NOT NULL,
  `keterangan_keperluan` varchar(100) NOT NULL,
  PRIMARY KEY (`id_detail`),
  KEY `id_peminjaman` (`id_peminjaman`),
  KEY `id_alat` (`id_alat`),
  CONSTRAINT `fk_detail_peminjaman` FOREIGN KEY (`id_peminjaman`) REFERENCES `tb_peminjaman` (`id_peminjaman`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_detail_alat` FOREIGN KEY (`id_alat`) REFERENCES `tb_alat` (`id_alat`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `tb_detail_peminjaman` (`id_detail`, `id_peminjaman`, `id_alat`, `jumlah_pinjam`, `keterangan_keperluan`) VALUES
(1, 1, 1, 1, 'Praktikum Proyektor'),
(2, 2, 2, 2, 'Instalasi Jaringan LAN'),
(3, 3, 5, 1, 'Pemrograman Web'),
(4, 4, 4, 1, 'Dokumentasi Acara'),
(5, 5, 3, 1, 'Praktikum Jaringan'),
(6, 7, 1, 1, 'Acara');

-- --------------------------------------------------------
CREATE TABLE `tb_pengembalian` (
  `id_pengembalian` int(11) NOT NULL AUTO_INCREMENT,
  `id_peminjaman` int(11) NOT NULL,
  `id_user` int(11) NOT NULL COMMENT 'yang mengajukan pengembalian (peminjam)',
  `id_petugas` int(11) DEFAULT NULL COMMENT 'petugas yang memverifikasi',
  `tanggal_kembali` date NOT NULL,
  `kondisi_alat_kembali` varchar(30) NOT NULL,
  `denda` int(11) NOT NULL DEFAULT 0 COMMENT 'total denda = denda_telat + denda_kerusakan',
  `denda_telat` int(11) NOT NULL DEFAULT 0,
  `denda_kerusakan` int(11) NOT NULL DEFAULT 0,
  `status_denda` enum('Tidak Ada','Belum Dibayar','Lunas') NOT NULL DEFAULT 'Tidak Ada',
  `tanggal_bayar` datetime DEFAULT NULL,
  `id_petugas_bayar` int(11) DEFAULT NULL COMMENT 'petugas yang mengonfirmasi pembayaran denda',
  `catatan_denda` varchar(150) DEFAULT NULL,
  `status` enum('Menunggu','Selesai') NOT NULL DEFAULT 'Menunggu',
  PRIMARY KEY (`id_pengembalian`),
  KEY `id_peminjaman` (`id_peminjaman`),
  CONSTRAINT `fk_pengembalian_peminjaman` FOREIGN KEY (`id_peminjaman`) REFERENCES `tb_peminjaman` (`id_peminjaman`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `tb_pengembalian` (`id_pengembalian`, `id_peminjaman`, `id_user`, `id_petugas`, `tanggal_kembali`, `kondisi_alat_kembali`, `denda`, `denda_telat`, `denda_kerusakan`, `status_denda`, `status`) VALUES
(1, 1, 3, 2, '2026-08-03', 'Baik', 0, 0, 0, 'Tidak Ada', 'Selesai'),
(2, 2, 4, 2, '2026-08-07', 'Baik', 0, 0, 0, 'Tidak Ada', 'Selesai');

-- --------------------------------------------------------
CREATE TABLE `tb_log_aktivitas` (
  `id_log` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `aktivitas` varchar(255) NOT NULL,
  `waktu` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_log`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`id_user`) REFERENCES `tb_user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
