-- =====================================================================
-- Migrasi database aplikasi_penduduk (25 Oktober 2026)
--
-- Hak akses menu per pengguna (ya/tidak). Nilai default mengikuti akses yang
-- sudah berjalan untuk pengguna biasa: Data Penduduk, Surat Keterangan, dan
-- Kegiatan = ya; Berita, Master Wilayah, dan Pengguna = tidak.
-- Pengguna level admin diberi akses ke semua menu.
-- Juga menambah status_cd di surat_keterangan (hapus surat = status inactive)
-- dan kolom email di penduduk.
-- Jalankan setelah migrasi_2026-10-18.sql. Kolom yang sudah ada dilewati.
--
--   mysql -uroot aplikasi_penduduk < database/migrasi_2026-10-25.sql
-- =====================================================================

ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `data_penduduk` enum('ya','tidak') NOT NULL DEFAULT 'ya' COMMENT 'Akses menu Data Penduduk' AFTER `status_cd`,
  ADD COLUMN IF NOT EXISTS `surat_keterangan` enum('ya','tidak') NOT NULL DEFAULT 'ya' COMMENT 'Akses menu Surat Keterangan' AFTER `data_penduduk`,
  ADD COLUMN IF NOT EXISTS `kegiatan` enum('ya','tidak') NOT NULL DEFAULT 'ya' COMMENT 'Akses menu Kegiatan' AFTER `surat_keterangan`,
  ADD COLUMN IF NOT EXISTS `berita` enum('ya','tidak') NOT NULL DEFAULT 'tidak' COMMENT 'Akses menu Berita' AFTER `kegiatan`,
  ADD COLUMN IF NOT EXISTS `master_wilayah` enum('ya','tidak') NOT NULL DEFAULT 'tidak' COMMENT 'Akses menu Master Wilayah' AFTER `berita`,
  ADD COLUMN IF NOT EXISTS `pengguna` enum('ya','tidak') NOT NULL DEFAULT 'tidak' COMMENT 'Akses menu Pengguna' AFTER `master_wilayah`;

-- Admin mendapat akses ke semua menu.
UPDATE `users`
   SET `data_penduduk` = 'ya', `surat_keterangan` = 'ya', `kegiatan` = 'ya',
       `berita` = 'ya', `master_wilayah` = 'ya', `pengguna` = 'ya'
 WHERE `level` = 'admin';

-- Status surat keterangan: hapus surat = ubah status jadi 'inactive' (data & nomornya tetap tersimpan).
ALTER TABLE `surat_keterangan`
  ADD COLUMN IF NOT EXISTS `status_cd` enum('active','inactive') NOT NULL DEFAULT 'active'
  COMMENT 'active = Aktif, inactive = Dihapus' AFTER `keperluan_lainnya`;

-- Email penduduk (opsional).
ALTER TABLE `penduduk`
  ADD COLUMN IF NOT EXISTS `email` varchar(100) DEFAULT NULL AFTER `no_telepon`;
