-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 16, 2026 at 05:15 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `aplikasi_penduduk`
--

-- --------------------------------------------------------

--
-- Table structure for table `blok_makam`
--

CREATE TABLE `blok_makam` (
  `id` int(11) NOT NULL,
  `kode_blok` varchar(10) NOT NULL,
  `nama_blok` varchar(50) NOT NULL,
  `kategori` enum('VIP','Umum','Ekonomis') NOT NULL,
  `total_petak` int(11) NOT NULL,
  `petak_terisi` int(11) DEFAULT 0,
  `luas_per_petak` decimal(5,2) NOT NULL,
  `harga_per_petak` decimal(12,2) NOT NULL,
  `lokasi_deskripsi` text DEFAULT NULL,
  `status_blok` enum('Aktif','Nonaktif','Penuh') DEFAULT 'Aktif',
  `warna_kode` varchar(7) DEFAULT '#007bff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `dana_hibah`
--

CREATE TABLE `dana_hibah` (
  `id` int(11) NOT NULL,
  `sumber_hibah` varchar(100) NOT NULL,
  `tanggal_hibah` date NOT NULL,
  `jumlah` decimal(12,2) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `bukti_hibah` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status_cd` int(11) DEFAULT 1,
  `tanda_tangan` longblob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `histori_ahli_waris`
--

CREATE TABLE `histori_ahli_waris` (
  `id` int(11) NOT NULL,
  `penduduk_id` int(11) NOT NULL,
  `no_kk` varchar(16) NOT NULL,
  `tanggal_pengangkatan` datetime DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'Aktif',
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `iuran`
--

CREATE TABLE `iuran` (
  `id` int(11) NOT NULL,
  `penduduk_id` int(11) NOT NULL,
  `jenis_iuran_id` int(11) NOT NULL,
  `tanggal_bayar` date NOT NULL,
  `jumlah_bayar` decimal(12,2) NOT NULL,
  `bulan_tahun` varchar(7) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `tagihan_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status_cd` tinyint(1) DEFAULT 1 COMMENT '1 = Aktif, 0 = Dihapus',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status_approve` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `cicilan_ke` int(11) DEFAULT NULL,
  `total_cicilan` int(11) DEFAULT NULL,
  `parent_iuran_id` int(11) DEFAULT NULL,
  `sisa_tagihan` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `jenis_iuran`
--

CREATE TABLE `jenis_iuran` (
  `id` int(11) NOT NULL,
  `nama_iuran` varchar(100) NOT NULL,
  `jumlah` decimal(12,2) NOT NULL,
  `status_cd` tinyint(1) DEFAULT 1 COMMENT '1 = Aktif, 0 = Dihapus',
  `periode` enum('bulanan','tahunan','sekali') NOT NULL,
  `bisa_dicicil` tinyint(1) NOT NULL DEFAULT 0,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `bisa_cicilan` tinyint(1) DEFAULT 0,
  `maks_cicilan` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `jenis_pengeluaran`
--

CREATE TABLE `jenis_pengeluaran` (
  `id` int(11) NOT NULL,
  `nama_jenis` varchar(100) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `kategori_aset`
--

CREATE TABLE `kategori_aset` (
  `id_kategori` int(11) NOT NULL,
  `nama_kategori` varchar(50) NOT NULL,
  `deskripsi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `kematian`
--

CREATE TABLE `kematian` (
  `id` int(11) NOT NULL,
  `penduduk_id` int(11) NOT NULL,
  `tanggal_kematian` date NOT NULL,
  `tempat_kematian` varchar(100) NOT NULL,
  `sebab_kematian` varchar(100) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `tata_letak_makam` text DEFAULT NULL,
  `layout_makam` varchar(50) DEFAULT NULL,
  `denah_makam` text DEFAULT NULL,
  `blok_makam` varchar(10) DEFAULT NULL,
  `nomor_makam` varchar(10) DEFAULT NULL,
  `luas_makam` decimal(5,2) DEFAULT NULL,
  `status_makam` enum('terisi','kosong','keluarga') DEFAULT 'terisi'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `layout_denah`
--

CREATE TABLE `layout_denah` (
  `id` int(11) NOT NULL,
  `nama_layout` varchar(100) NOT NULL,
  `panjang_total` decimal(8,2) NOT NULL,
  `lebar_total` decimal(8,2) NOT NULL,
  `luas_total` decimal(10,2) NOT NULL,
  `skala` varchar(20) DEFAULT '1:100',
  `file_denah` varchar(255) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `dibuat_pada` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `log_aset`
--

CREATE TABLE `log_aset` (
  `id_log` int(11) NOT NULL,
  `id_aset` varchar(20) NOT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `peminjam` varchar(100) DEFAULT NULL,
  `keperluan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `master_aset`
--

CREATE TABLE `master_aset` (
  `id_aset` varchar(20) NOT NULL,
  `id_kategori` int(11) NOT NULL,
  `nama_aset` varchar(100) NOT NULL,
  `merk_type` varchar(100) DEFAULT NULL,
  `nomor_sertifikat_nopol` varchar(100) DEFAULT NULL,
  `tahun_perolehan` year(4) DEFAULT NULL,
  `nilai_perolehan` decimal(15,2) DEFAULT NULL,
  `kondisi` enum('Baik','Rusak Ringan','Rusak Berat','Dalam Perbaikan') DEFAULT 'Baik',
  `status` enum('Tersedia','Dipinjam','Dalam Perawatan','Hilang') DEFAULT 'Tersedia',
  `lokasi_penyimpanan` varchar(100) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `tanggal_input` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `master_pekerjaan`
--

CREATE TABLE `master_pekerjaan` (
  `id` int(11) NOT NULL,
  `kode_pekerjaan` varchar(10) NOT NULL,
  `nama_pekerjaan` varchar(100) NOT NULL,
  `kategori_pekerjaan` varchar(50) DEFAULT NULL COMMENT 'PNS, Swasta, Wiraswasta, Petani, dll',
  `keterangan` text DEFAULT NULL,
  `urutan` int(11) DEFAULT 0,
  `status_cd` tinyint(1) DEFAULT 1 COMMENT '1: aktif, 0: nonaktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `master_pekerjaan`
--

INSERT INTO `master_pekerjaan` (`id`, `kode_pekerjaan`, `nama_pekerjaan`, `kategori_pekerjaan`, `keterangan`, `urutan`, `status_cd`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(52, 'BLM', 'Belum/Tidak Bekerja', 'Lainnya', NULL, 1, 1, '2026-10-15 02:20:08', '2026-10-15 02:20:08', NULL, NULL),
(53, 'PLJ', 'Pelajar/Mahasiswa', 'Lainnya', NULL, 2, 1, '2026-10-15 02:20:08', '2026-10-15 02:20:08', NULL, NULL),
(54, 'IRT', 'Mengurus Rumah Tangga', 'Lainnya', NULL, 3, 1, '2026-10-15 02:20:08', '2026-10-15 02:20:08', NULL, NULL),
(55, 'PNS', 'Pegawai Negeri Sipil', 'PNS', NULL, 4, 1, '2026-10-15 02:20:08', '2026-10-15 02:20:08', NULL, NULL),
(56, 'TNI', 'TNI', 'PNS', NULL, 5, 1, '2026-10-15 02:20:08', '2026-10-15 02:20:08', NULL, NULL),
(57, 'POL', 'Polri', 'PNS', NULL, 6, 1, '2026-10-15 02:20:08', '2026-10-15 02:20:08', NULL, NULL),
(58, 'SWT', 'Karyawan Swasta', 'Swasta', NULL, 7, 1, '2026-10-15 02:20:08', '2026-10-15 02:20:08', NULL, NULL),
(59, 'WRS', 'Wiraswasta', 'Wiraswasta', NULL, 8, 1, '2026-10-15 02:20:08', '2026-10-15 02:20:08', NULL, NULL),
(60, 'PTN', 'Petani/Pekebun', 'Petani', NULL, 9, 1, '2026-10-15 02:20:08', '2026-10-15 02:20:08', NULL, NULL),
(61, 'NLY', 'Nelayan', 'Petani', NULL, 10, 1, '2026-10-15 02:20:08', '2026-10-15 02:20:08', NULL, NULL),
(62, 'BRH', 'Buruh Harian Lepas', 'Swasta', NULL, 11, 1, '2026-10-15 02:20:08', '2026-10-15 02:20:08', NULL, NULL),
(63, 'DOK', 'Dokter', 'Swasta', NULL, 12, 1, '2026-10-15 02:20:08', '2026-10-15 02:20:08', NULL, NULL),
(64, 'GRU', 'Guru/Dosen', 'Swasta', NULL, 13, 1, '2026-10-15 02:20:08', '2026-10-15 02:20:08', NULL, NULL),
(65, 'PNS2', 'Pensiunan', 'Lainnya', NULL, 14, 1, '2026-10-15 02:20:08', '2026-10-15 02:20:08', NULL, NULL),
(66, 'LNY', 'Lainnya', 'Lainnya', NULL, 15, 1, '2026-10-15 02:20:08', '2026-10-15 02:20:08', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `master_pendidikan`
--

CREATE TABLE `master_pendidikan` (
  `id` int(11) NOT NULL,
  `kode_pendidikan` varchar(10) NOT NULL,
  `nama_pendidikan` varchar(100) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `level_pendidikan` tinyint(4) DEFAULT 0 COMMENT '0: Non Formal, 1: SD, 2: SMP, 3: SMA, 4: D1-D3, 5: S1, 6: S2, 7: S3',
  `urutan` int(11) DEFAULT 0,
  `status_cd` tinyint(1) DEFAULT 1 COMMENT '1: aktif, 0: nonaktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `master_pendidikan`
--

INSERT INTO `master_pendidikan` (`id`, `kode_pendidikan`, `nama_pendidikan`, `keterangan`, `level_pendidikan`, `urutan`, `status_cd`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(14, 'TS', 'Tidak/Belum Sekolah', NULL, 0, 1, 1, '2026-10-15 02:20:08', '2026-10-15 02:20:08', NULL, NULL),
(15, 'SD', 'SD/Sederajat', NULL, 1, 2, 1, '2026-10-15 02:20:08', '2026-10-15 02:20:08', NULL, NULL),
(16, 'SMP', 'SMP/Sederajat', NULL, 2, 3, 1, '2026-10-15 02:20:08', '2026-10-15 02:20:08', NULL, NULL),
(17, 'SMA', 'SMA/Sederajat', NULL, 3, 4, 1, '2026-10-15 02:20:08', '2026-10-15 02:20:08', NULL, NULL),
(18, 'D3', 'Diploma (D1-D3)', NULL, 4, 5, 1, '2026-10-15 02:20:08', '2026-10-15 02:20:08', NULL, NULL),
(19, 'S1', 'Sarjana (S1)', NULL, 5, 6, 1, '2026-10-15 02:20:08', '2026-10-15 02:20:08', NULL, NULL),
(20, 'S2', 'Magister (S2)', NULL, 6, 7, 1, '2026-10-15 02:20:08', '2026-10-15 02:20:08', NULL, NULL),
(21, 'S3', 'Doktor (S3)', NULL, 7, 8, 1, '2026-10-15 02:20:08', '2026-10-15 02:20:08', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `penduduk`
--

CREATE TABLE `penduduk` (
  `id` int(11) NOT NULL,
  `nik` varchar(20) NOT NULL,
  `no_kk` varchar(16) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `tempat_lahir` varchar(50) NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL,
  `agama` varchar(20) NOT NULL,
  `pendidikan` varchar(100) DEFAULT NULL,
  `pekerjaan` varchar(100) DEFAULT NULL,
  `status_kawin` varchar(15) NOT NULL,
  `status_keluarga` varchar(50) DEFAULT NULL,
  `status_cd` tinyint(1) DEFAULT 1 COMMENT '1 = Aktif, 0 = Dihapus',
  `is_ahli_waris` tinyint(1) DEFAULT 0 COMMENT '1 = Ahli Waris, 0 = Bukan',
  `alamat` text NOT NULL,
  `id_wilayah` int(3) DEFAULT NULL,
  `no_telepon` varchar(15) DEFAULT NULL,
  `nama_ayah` varchar(100) DEFAULT NULL,
  `nama_ibu` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `umur` int(11) DEFAULT NULL,
  `kategori` varchar(20) DEFAULT NULL,
  `foto_ktp` varchar(255) DEFAULT NULL,
  `foto_kk` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `penduduk`
--


-- --------------------------------------------------------

--
-- Table structure for table `pengeluaran`
--

CREATE TABLE `pengeluaran` (
  `id` int(11) NOT NULL,
  `jenis_pengeluaran_id` int(11) NOT NULL,
  `tanggal_pengeluaran` date NOT NULL,
  `jumlah` decimal(12,2) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `bukti_pengeluaran` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status_cd` tinyint(1) DEFAULT 1 COMMENT 'Status record: 1=Active, 0=Deleted'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `petak_makam`
--

CREATE TABLE `petak_makam` (
  `id` int(11) NOT NULL,
  `id_blok` int(11) NOT NULL,
  `nomor_petak` varchar(10) NOT NULL,
  `baris` int(11) NOT NULL,
  `kolom` int(11) NOT NULL,
  `status_petak` enum('Tersedia','Terisi','Dipesan','Rusak') DEFAULT 'Tersedia',
  `nama_penghuni` varchar(100) DEFAULT NULL,
  `tanggal_penempatan` date DEFAULT NULL,
  `jenis_makam` enum('Single','Keluarga','Anak') DEFAULT 'Single',
  `deskripsi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `petugas`
--

CREATE TABLE `petugas` (
  `id_petugas` int(11) NOT NULL,
  `nama_petugas` varchar(100) NOT NULL,
  `jabatan` varchar(50) DEFAULT NULL,
  `status_aktif` enum('Aktif','Non-Aktif') DEFAULT 'Aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `tagihan_iuran`
--

CREATE TABLE `tagihan_iuran` (
  `id` int(11) NOT NULL,
  `penduduk_id` int(11) NOT NULL,
  `jenis_iuran_id` int(11) NOT NULL,
  `jumlah_tagihan` decimal(12,2) NOT NULL,
  `tanggal_tagihan` date NOT NULL,
  `keterangan` text DEFAULT NULL,
  `status` enum('belum_bayar','cicilan','lunas') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'belum_bayar',
  `user_id` int(11) DEFAULT NULL,
  `status_cd` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `tanda_tangan_kwitansi`
--

CREATE TABLE `tanda_tangan_kwitansi` (
  `id` int(11) NOT NULL,
  `kwitansi_id` int(11) NOT NULL,
  `tipe` varchar(20) NOT NULL COMMENT 'penerima, penyerah, mengetahui',
  `tanda_tangan` text NOT NULL COMMENT 'data URL dari canvas',
  `dibuat_pada` timestamp NOT NULL DEFAULT current_timestamp(),
  `dibuat_oleh` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `level` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `foto_profil` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `users`
--


-- --------------------------------------------------------

--
-- Table structure for table `wilayah`
--

CREATE TABLE `wilayah` (
  `id_wilayah` int(10) NOT NULL,
  `propinsi` varchar(200) NOT NULL,
  `Kabupaten` varchar(100) NOT NULL,
  `kecamatan` varchar(200) NOT NULL,
  `kelurahan` varchar(200) NOT NULL,
  `rw` varchar(3) NOT NULL,
  `rt` varchar(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wilayah`
--

INSERT INTO `wilayah` (`id_wilayah`, `propinsi`, `Kabupaten`, `kecamatan`, `kelurahan`, `rw`, `rt`) VALUES
(1, 'Jawa Barat', 'Bogor', 'Bojonggede', 'Rawapanjang', '09', '01'),
(2, 'Jawa Barat', 'Bogor', 'Bojonggede', 'Rawapanjang', '09', '02'),
(3, 'Jawa Barat', 'Bogor', 'Bojonggede', 'Rawapanjang', '09', '03'),
(4, 'Jawa Barat', 'Bogor', 'Bojonggede', 'Rawapanjang', '09', '04'),
(5, 'Jawa Barat', 'Bogor', 'Bojonggede', 'Rawapanjang', '09', '05'),
(6, 'Jawa Barat', 'Bogor', 'Bojonggede', 'Rawapanjang', '09', '06');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `blok_makam`
--
ALTER TABLE `blok_makam`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD UNIQUE KEY `kode_blok` (`kode_blok`) USING BTREE;

--
-- Indexes for table `dana_hibah`
--
ALTER TABLE `dana_hibah`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `histori_ahli_waris`
--
ALTER TABLE `histori_ahli_waris`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `iuran`
--
ALTER TABLE `iuran`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `jenis_iuran`
--
ALTER TABLE `jenis_iuran`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `jenis_pengeluaran`
--
ALTER TABLE `jenis_pengeluaran`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `kategori_aset`
--
ALTER TABLE `kategori_aset`
  ADD PRIMARY KEY (`id_kategori`) USING BTREE;

--
-- Indexes for table `kematian`
--
ALTER TABLE `kematian`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `layout_denah`
--
ALTER TABLE `layout_denah`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `log_aset`
--
ALTER TABLE `log_aset`
  ADD PRIMARY KEY (`id_log`) USING BTREE;

--
-- Indexes for table `master_aset`
--
ALTER TABLE `master_aset`
  ADD PRIMARY KEY (`id_aset`) USING BTREE;

--
-- Indexes for table `master_pekerjaan`
--
ALTER TABLE `master_pekerjaan`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `master_pendidikan`
--
ALTER TABLE `master_pendidikan`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `penduduk`
--
ALTER TABLE `penduduk`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `pengeluaran`
--
ALTER TABLE `pengeluaran`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `petak_makam`
--
ALTER TABLE `petak_makam`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `petugas`
--
ALTER TABLE `petugas`
  ADD PRIMARY KEY (`id_petugas`) USING BTREE;

--
-- Indexes for table `tagihan_iuran`
--
ALTER TABLE `tagihan_iuran`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `tanda_tangan_kwitansi`
--
ALTER TABLE `tanda_tangan_kwitansi`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `wilayah`
--
ALTER TABLE `wilayah`
  ADD PRIMARY KEY (`id_wilayah`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `blok_makam`
--
ALTER TABLE `blok_makam`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `dana_hibah`
--
ALTER TABLE `dana_hibah`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `histori_ahli_waris`
--
ALTER TABLE `histori_ahli_waris`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `iuran`
--
ALTER TABLE `iuran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=438;

--
-- AUTO_INCREMENT for table `jenis_iuran`
--
ALTER TABLE `jenis_iuran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `jenis_pengeluaran`
--
ALTER TABLE `jenis_pengeluaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `kategori_aset`
--
ALTER TABLE `kategori_aset`
  MODIFY `id_kategori` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `kematian`
--
ALTER TABLE `kematian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `layout_denah`
--
ALTER TABLE `layout_denah`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `log_aset`
--
ALTER TABLE `log_aset`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `master_pekerjaan`
--
ALTER TABLE `master_pekerjaan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `master_pendidikan`
--
ALTER TABLE `master_pendidikan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `penduduk`
--
ALTER TABLE `penduduk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1652;

--
-- AUTO_INCREMENT for table `pengeluaran`
--
ALTER TABLE `pengeluaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `petak_makam`
--
ALTER TABLE `petak_makam`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `petugas`
--
ALTER TABLE `petugas`
  MODIFY `id_petugas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tagihan_iuran`
--
ALTER TABLE `tagihan_iuran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tanda_tangan_kwitansi`
--
ALTER TABLE `tanda_tangan_kwitansi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `wilayah`
--
ALTER TABLE `wilayah`
  MODIFY `id_wilayah` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
