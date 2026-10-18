-- =====================================================================
-- Migrasi database aplikasi_penduduk (18 Oktober 2026)
--
-- Tabel berita (menu manajemen konten berita/ dan halaman publik
-- berita.php) beserta galeri foto berita_foto: satu berita bisa punya
-- banyak foto, masing-masing dengan keterangan (narasi gambar).
-- Jalankan setelah migrasi_2026-10-17.sql. Aman dijalankan lebih dari sekali.
--
--   mysql -uroot aplikasi_penduduk < database/migrasi_2026-10-18.sql
-- =====================================================================

CREATE TABLE IF NOT EXISTS `berita` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) NOT NULL,
  `slug` varchar(191) NOT NULL COMMENT 'Alamat ramah URL, dibuat dari judul',
  `konten` mediumtext NOT NULL COMMENT 'HTML yang sudah disaring (lihat includes/berita.php)',
  `status` enum('draft','publish') NOT NULL DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
  `published_at` datetime DEFAULT NULL COMMENT 'Diisi saat pertama kali diterbitkan',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_berita_slug` (`slug`),
  KEY `idx_berita_status_terbit` (`status`, `published_at`),
  KEY `idx_berita_created_by` (`created_by`),
  CONSTRAINT `fk_berita_users` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `berita_foto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `berita_id` int(11) NOT NULL,
  `file` varchar(255) NOT NULL,
  `keterangan` varchar(500) DEFAULT NULL COMMENT 'Narasi/keterangan gambar',
  `urutan` int(11) NOT NULL DEFAULT 0 COMMENT 'Urutan tampil; urutan terkecil = foto utama',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_berita_foto_urutan` (`berita_id`, `urutan`),
  CONSTRAINT `fk_berita_foto_berita` FOREIGN KEY (`berita_id`) REFERENCES `berita` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Versi awal fitur berita menyimpan satu foto di kolom berita.foto.
-- Pindahkan foto tersebut ke berita_foto sebagai foto utama, lalu hapus kolomnya.
-- (Kolom ditambahkan dulu jika belum ada supaya perintah di bawah tidak gagal di database baru.)
ALTER TABLE `berita` ADD COLUMN IF NOT EXISTS `foto` varchar(255) DEFAULT NULL;
INSERT INTO `berita_foto` (`berita_id`, `file`, `urutan`)
SELECT b.`id`, b.`foto`, 0
  FROM `berita` b
 WHERE b.`foto` IS NOT NULL AND b.`foto` <> ''
   AND NOT EXISTS (SELECT 1 FROM `berita_foto` f WHERE f.`berita_id` = b.`id` AND f.`file` = b.`foto`);
ALTER TABLE `berita` DROP COLUMN IF EXISTS `foto`;

-- Kategori (untuk tab Pengumuman/Kegiatan di halaman Home) dan jumlah dibaca (untuk berita terpopuler).
ALTER TABLE `berita`
  ADD COLUMN IF NOT EXISTS `kategori` enum('berita','pengumuman','kegiatan') NOT NULL DEFAULT 'berita' AFTER `slug`,
  ADD COLUMN IF NOT EXISTS `dilihat` int(11) NOT NULL DEFAULT 0 COMMENT 'Jumlah dibaca di halaman publik' AFTER `status`,
  ADD INDEX IF NOT EXISTS `idx_berita_kategori_terbit` (`status`, `kategori`, `published_at`);

-- Wilayah kegiatan (menu kegiatan/): kegiatan milik RT/RW tertentu, dibatasi lingkup wilayah pengguna.
ALTER TABLE `berita`
  ADD COLUMN IF NOT EXISTS `id_wilayah` int(10) DEFAULT NULL COMMENT 'Wilayah kegiatan (kategori kegiatan)' AFTER `kategori`,
  ADD INDEX IF NOT EXISTS `idx_berita_wilayah` (`id_wilayah`);
ALTER TABLE `berita`
  ADD CONSTRAINT `fk_berita_wilayah` FOREIGN KEY IF NOT EXISTS (`id_wilayah`)
  REFERENCES `wilayah` (`id_wilayah`) ON DELETE SET NULL ON UPDATE CASCADE;
