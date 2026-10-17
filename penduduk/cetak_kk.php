<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pdo = getDB();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT p.*, w.rt, w.rw, w.kelurahan, w.kecamatan, w.Kabupaten, w.propinsi
                        FROM penduduk p
                        LEFT JOIN wilayah w ON w.id_wilayah = p.id_wilayah
                        WHERE p.id = ? AND p.status_cd <> \'nullified\' AND ' . filterWilayahPenduduk('p.id_wilayah'));
$stmt->execute([$id]);
$current = $stmt->fetch();

if (!$current) {
    $_SESSION['flash_error'] = 'Data penduduk tidak ditemukan.';
    header('Location: list.php');
    exit;
}

$stmtAnggota = $pdo->prepare("SELECT p.*, w.rt, w.rw, w.kelurahan, w.kecamatan, w.Kabupaten, w.propinsi
                               FROM penduduk p
                               LEFT JOIN wilayah w ON w.id_wilayah = p.id_wilayah
                               WHERE p.no_kk = ? AND p.status_cd <> 'nullified' AND " . filterWilayahPenduduk('p.id_wilayah') . "
                               ORDER BY FIELD(p.status_keluarga, 'Kepala Keluarga', 'Istri', 'Anak', 'Famili Lain'), p.tanggal_lahir ASC");
$stmtAnggota->execute([$current['no_kk']]);
$anggota = $stmtAnggota->fetchAll();

$kepala = null;
foreach ($anggota as $a) {
    if ($a['status_keluarga'] === 'Kepala Keluarga') {
        $kepala = $a;
        break;
    }
}
$kepala = $kepala ?? $current;

function kkVal($value)
{
    return ($value !== null && $value !== '') ? htmlspecialchars($value) : '-';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cetak Data Keluarga - <?= htmlspecialchars(tampilNoKk($current['no_kk'])) ?></title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #111; margin: 24px; }
        .notice { background: #fff3cd; border: 1px solid #ffe69c; padding: 6px 10px; margin-bottom: 16px; font-size: 9.5px; }
        .kop { display: flex; align-items: center; gap: 16px; margin-bottom: 14px; }
        .kop img { width: 80px; height: 80px; object-fit: contain; flex-shrink: 0; }
        .kop-text { flex: 1; text-align: center; margin-right: 80px; }
        h1 { font-size: 30px; text-align: center; margin: 0 0 6px; letter-spacing: 1px; }
        .subtitle { text-align: center; font-size: 25px; color: #555; margin-bottom: 16px; }
        .meta { width: 100%; margin-bottom: 14px; }
        .meta td { padding: 1px 4px; vertical-align: top; }
        .meta .label { width: 140px; color: #333; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.data th, table.data td { border: 1px solid #333; padding: 4px 6px; font-size: 10.5px; }
        table.data th { background: #f0f0f0; text-align: center; }
        .no-col { width: 24px; text-align: center; }
        .footer { margin-top: 30px; display: flex; justify-content: space-between; font-size: 11px; }
        .sign-box { text-align: center; width: 220px; }
        .sign-box .line { margin-top: 55px; border-top: 1px solid #333; padding-top: 4px; }
        .toolbar { margin-bottom: 16px; }
        @media print {
            .toolbar { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <button onclick="window.print()">Cetak</button>
    <a href="detail.php?id=<?= (int) $current['id'] ?>">Kembali</a>
</div>

<div class="notice">
    Dokumen ini dihasilkan oleh Aplikasi Penduduk untuk keperluan administrasi/pencatatan internal.
    <strong>Bukan dokumen resmi yang diterbitkan oleh Dinas Kependudukan dan Pencatatan Sipil.</strong>
</div>

<div class="kop">
    <img src="../assets/img/garuda-pancasila.jpg" alt="Garuda Pancasila">
    <div class="kop-text">
        <h1>KARTU KELUARGA</h1>
        <div class="subtitle">No. <?= kkVal(tampilNoKk($current['no_kk'])) ?></div>
    </div>
</div>

<table class="meta">
    <tr>
        <td class="label">Nama Kepala Keluarga</td><td>: <?= kkVal($kepala['nama_lengkap']) ?></td>
        <td class="label">Desa/Kelurahan</td><td>: <?= kkVal($kepala['kelurahan']) ?></td>
    </tr>
    <tr>
        <td class="label">Alamat</td><td>: <?= kkVal($kepala['alamat']) ?></td>
        <td class="label">Kecamatan</td><td>: <?= kkVal($kepala['kecamatan']) ?></td>
    </tr>
    <tr>
        <td class="label">RT/RW</td><td>: <?= kkVal(($kepala['rt'] ?: '-') . '/' . ($kepala['rw'] ?: '-')) ?></td>
        <td class="label">Kabupaten/Kota</td><td>: <?= kkVal($kepala['Kabupaten']) ?></td>
    </tr>
    <tr>
        <td class="label"></td><td></td>
        <td class="label">Provinsi</td><td>: <?= kkVal($kepala['propinsi']) ?></td>
    </tr>
</table>

<table class="data">
    <thead>
        <tr>
            <th rowspan="2" class="no-col">No</th>
            <th rowspan="2">Nama Lengkap</th>
            <th rowspan="2">NIK</th>
            <th rowspan="2">Jenis Kelamin</th>
            <th rowspan="2">Tempat Lahir</th>
            <th rowspan="2">Tanggal Lahir</th>
            <th rowspan="2">Agama</th>
            <th rowspan="2">Pendidikan</th>
            <th rowspan="2">Pekerjaan</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($anggota as $i => $a): ?>
            <tr>
                <td class="no-col"><?= $i + 1 ?></td>
                <td><?= kkVal($a['nama_lengkap']) ?></td>
                <td><?= kkVal(tampilNik($a['nik'])) ?></td>
                <td><?= $a['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                <td><?= kkVal($a['tempat_lahir']) ?></td>
                <td><?= $a['tanggal_lahir'] ? htmlspecialchars(date('d-m-Y', strtotime($a['tanggal_lahir']))) : '-' ?></td>
                <td><?= kkVal($a['agama']) ?></td>
                <td><?= kkVal($a['pendidikan']) ?></td>
                <td><?= kkVal($a['pekerjaan']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<table class="data">
    <thead>
        <tr>
            <th rowspan="2" class="no-col">No</th>
            <th rowspan="2">Status Perkawinan</th>
            <th rowspan="2">Status Hubungan dalam Keluarga</th>
            <th colspan="2">Nama Orang Tua</th>
        </tr>
        <tr>
            <th>Ayah</th>
            <th>Ibu</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($anggota as $i => $a): ?>
            <tr>
                <td class="no-col"><?= $i + 1 ?></td>
                <td><?= kkVal($a['status_kawin']) ?></td>
                <td><?= kkVal($a['status_keluarga']) ?></td>
                <td><?= kkVal($a['nama_ayah']) ?></td>
                <td><?= kkVal($a['nama_ibu']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="footer">
    <div>Dicetak tanggal: <?= date('d-m-Y') ?></div>
    <div class="sign-box">
        <div>Kepala Keluarga</div>
        <div class="line"><?= kkVal($kepala['nama_lengkap']) ?></div>
    </div>
</div>

</body>
</html>
