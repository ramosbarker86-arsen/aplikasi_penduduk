-- Seed data awal untuk aplikasi_penduduk
-- Jalankan sekali saja setelah skema database dibuat.

INSERT INTO users (username, password, nama_lengkap, level) VALUES
('admin', '$2y$10$vphXaB2GGRKaCe6zYRLZz.akzhcRZ48uQDQXMYODW9ceCMYPFlPne', 'Administrator', 'admin');

INSERT INTO master_pendidikan (kode_pendidikan, nama_pendidikan, level_pendidikan, urutan, status_cd) VALUES
('TS', 'Tidak/Belum Sekolah', 0, 1, 1),
('SD', 'SD/Sederajat', 1, 2, 1),
('SMP', 'SMP/Sederajat', 2, 3, 1),
('SMA', 'SMA/Sederajat', 3, 4, 1),
('D3', 'Diploma (D1-D3)', 4, 5, 1),
('S1', 'Sarjana (S1)', 5, 6, 1),
('S2', 'Magister (S2)', 6, 7, 1),
('S3', 'Doktor (S3)', 7, 8, 1);

INSERT INTO master_pekerjaan (kode_pekerjaan, nama_pekerjaan, kategori_pekerjaan, urutan, status_cd) VALUES
('BLM', 'Belum/Tidak Bekerja', 'Lainnya', 1, 1),
('PLJ', 'Pelajar/Mahasiswa', 'Lainnya', 2, 1),
('IRT', 'Mengurus Rumah Tangga', 'Lainnya', 3, 1),
('PNS', 'Pegawai Negeri Sipil', 'PNS', 4, 1),
('TNI', 'TNI', 'PNS', 5, 1),
('POL', 'Polri', 'PNS', 6, 1),
('SWT', 'Karyawan Swasta', 'Swasta', 7, 1),
('WRS', 'Wiraswasta', 'Wiraswasta', 8, 1),
('PTN', 'Petani/Pekebun', 'Petani', 9, 1),
('NLY', 'Nelayan', 'Petani', 10, 1),
('BRH', 'Buruh Harian Lepas', 'Swasta', 11, 1),
('DOK', 'Dokter', 'Swasta', 12, 1),
('GRU', 'Guru/Dosen', 'Swasta', 13, 1),
('PNS2', 'Pensiunan', 'Lainnya', 14, 1),
('LNY', 'Lainnya', 'Lainnya', 15, 1);
