-- =====================================================================
-- Migrasi database aplikasi_penduduk (17 Oktober 2026)
--
-- Untuk database yang dibuat dari versi awal (commit 23ad636) supaya
-- strukturnya sama dengan database/schema.sql terbaru.
--
-- Aman dijalankan lebih dari sekali: kolom, index, relasi, dan tabel yang
-- sudah ada dilewati, dan data yang sudah dimigrasi tidak berubah.
-- Butuh MariaDB 10.4+ (XAMPP).
--
-- Cara pakai (backup dulu!):
--   mysqldump -uroot aplikasi_penduduk > backup_sebelum_migrasi.sql
--   mysql -uroot aplikasi_penduduk < database/migrasi_2026-10-17.sql
-- atau impor file ini lewat phpMyAdmin pada database aplikasi_penduduk.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- 1. users: status aktif/tidak aktif
--    Pengguna lama diberi status 'active' supaya tetap bisa login.
-- ---------------------------------------------------------------------
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `status_cd` enum('active','inactive') NOT NULL DEFAULT 'active';
ALTER TABLE `users`
  MODIFY `status_cd` enum('active','inactive') NOT NULL;

-- ---------------------------------------------------------------------
-- 2. wilayah: nama ketua RT/RW dan status
--    Wilayah lama otomatis berstatus 'active'.
-- ---------------------------------------------------------------------
ALTER TABLE `wilayah`
  ADD COLUMN IF NOT EXISTS `ketua_rt` varchar(100) DEFAULT NULL AFTER `rt`,
  ADD COLUMN IF NOT EXISTS `ketua_rw` varchar(100) DEFAULT NULL AFTER `ketua_rt`,
  ADD COLUMN IF NOT EXISTS `status_cd` enum('active','inactive') NOT NULL DEFAULT 'active' AFTER `ketua_rw`;

-- ---------------------------------------------------------------------
-- 3. penduduk: status_cd tinyint (1 = Aktif, 0 = Dihapus) menjadi enum
--    1 -> 'active', 0 -> 'nullified' (dihapus). Nilai yang sudah berupa
--    teks (migrasi sudah pernah dijalankan) dibiarkan.
-- ---------------------------------------------------------------------
ALTER TABLE `penduduk` MODIFY `status_cd` varchar(10) NULL;
UPDATE `penduduk`
   SET `status_cd` = CASE
         WHEN `status_cd` = '0' THEN 'nullified'
         WHEN `status_cd` = '1' OR `status_cd` IS NULL OR `status_cd` = '' THEN 'active'
         ELSE `status_cd`
       END;
ALTER TABLE `penduduk`
  MODIFY `status_cd` enum('active','inactive','nullified') NOT NULL DEFAULT 'active'
  COMMENT 'active = Aktif, inactive = Tidak Aktif, nullified = Dihapus';

-- Index NIK, dibutuhkan untuk relasi surat_keterangan.nik -> penduduk.nik
ALTER TABLE `penduduk` ADD INDEX IF NOT EXISTS `idx_penduduk_nik` (`nik`);

-- ---------------------------------------------------------------------
-- 4. users: sub level dan wilayah pengguna (lingkup data selain admin)
-- ---------------------------------------------------------------------
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `sub_level` enum('rt','rw','kelurahan') DEFAULT NULL AFTER `level`,
  ADD COLUMN IF NOT EXISTS `id_wilayah` int(10) DEFAULT NULL AFTER `sub_level`,
  ADD INDEX IF NOT EXISTS `idx_users_wilayah` (`id_wilayah`);
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_wilayah` FOREIGN KEY IF NOT EXISTS (`id_wilayah`)
  REFERENCES `wilayah` (`id_wilayah`) ON DELETE SET NULL ON UPDATE CASCADE;

-- ---------------------------------------------------------------------
-- 5. surat_keterangan (relasi ke penduduk lewat nik)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `surat_keterangan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nomor_surat` varchar(30) NOT NULL COMMENT 'Format: no_urut/bulan romawi/tahun, contoh 001/X/2026',
  `no_urut` int(11) NOT NULL COMMENT 'Nomor urut, mulai dari 1 setiap bulan',
  `bulan` tinyint(2) NOT NULL,
  `tahun` smallint(4) NOT NULL,
  `tanggal_surat` date NOT NULL,
  `nik` varchar(20) NOT NULL,
  `kewarganegaraan` varchar(30) NOT NULL DEFAULT 'WNI',
  `keperluan` varchar(500) NOT NULL COMMENT 'Daftar keperluan, dipisah koma',
  `keperluan_lainnya` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_surat_nomor` (`nomor_surat`),
  UNIQUE KEY `uq_surat_urut_bulan` (`tahun`, `bulan`, `no_urut`),
  KEY `idx_surat_nik` (`nik`),
  KEY `idx_surat_created_by` (`created_by`),
  CONSTRAINT `fk_surat_penduduk_nik` FOREIGN KEY (`nik`) REFERENCES `penduduk` (`nik`) ON UPDATE CASCADE,
  CONSTRAINT `fk_surat_users` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
