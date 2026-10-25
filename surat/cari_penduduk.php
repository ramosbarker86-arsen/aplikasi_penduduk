<?php
// Endpoint JSON untuk pencarian penduduk di form surat (surat/tambah.php).
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/surat.php';
// Butuh akses menu Surat Keterangan; data penduduk dibatasi lingkup wilayah pengguna (filterWilayahPenduduk).
requireAkses('surat_keterangan');

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT p.nik, p.nama_lengkap, p.tempat_lahir, p.tanggal_lahir, p.jenis_kelamin, p.alamat,
                              w.rt, w.rw, w.kelurahan
                       FROM penduduk p
                       LEFT JOIN wilayah w ON w.id_wilayah = p.id_wilayah
                       WHERE p.status_cd = 'active' AND " . filterWilayahPenduduk('p.id_wilayah') . "
                         AND (p.nama_lengkap LIKE ? OR p.nik LIKE ?)
                       ORDER BY p.nama_lengkap ASC
                       LIMIT 10");
$like = '%' . $q . '%';
$stmt->execute([$like, $like]);

$hasil = [];
foreach ($stmt->fetchAll() as $row) {
    $hasil[] = [
        'nik' => $row['nik'],
        'nik_tampil' => tampilNik($row['nik']),
        'nama' => $row['nama_lengkap'],
        'ttl' => trim($row['tempat_lahir'] . ', ' . tampilTanggalLahir($row['tanggal_lahir'], 'tanggalIndonesia'), ', '),
        'jenis_kelamin' => $row['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan',
        'alamat' => $row['alamat'],
        'wilayah' => $row['rt'] !== null ? 'RT ' . $row['rt'] . '/RW ' . $row['rw'] . ', ' . $row['kelurahan'] : null,
    ];
}

echo json_encode($hasil);
