-- Jalankan HANYA jika database db_peminjaman_alat sudah terlanjur di-import dari versi lama.
-- (Kalau import ulang db_peminjaman_alat_fixed.sql yang baru, file ini tidak perlu.)
USE `db_peminjaman_alat`;

ALTER TABLE `tb_pengembalian`
  ADD COLUMN `denda_telat` int(11) NOT NULL DEFAULT 0 AFTER `denda`,
  ADD COLUMN `denda_kerusakan` int(11) NOT NULL DEFAULT 0 AFTER `denda_telat`,
  ADD COLUMN `status_denda` enum('Tidak Ada','Belum Dibayar','Lunas') NOT NULL DEFAULT 'Tidak Ada' AFTER `denda_kerusakan`,
  ADD COLUMN `tanggal_bayar` datetime DEFAULT NULL AFTER `status_denda`,
  ADD COLUMN `id_petugas_bayar` int(11) DEFAULT NULL AFTER `tanggal_bayar`,
  ADD COLUMN `catatan_denda` varchar(150) DEFAULT NULL AFTER `id_petugas_bayar`;

-- Data lama: denda yang sudah tercatat dianggap denda keterlambatan & belum dibayar
UPDATE `tb_pengembalian`
   SET `denda_telat` = `denda`,
       `status_denda` = IF(`denda` > 0, 'Belum Dibayar', 'Tidak Ada')
 WHERE `status` = 'Selesai';
