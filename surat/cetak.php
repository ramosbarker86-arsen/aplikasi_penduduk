<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/surat.php';
// Semua pengguna boleh membuat surat; data penduduk dibatasi lingkup wilayahnya (filterWilayahPenduduk).
requireLogin();

$pdo = getDB();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM surat_keterangan WHERE id = ?');
$stmt->execute([$id]);
$surat = $stmt->fetch();

if (!$surat) {
    $_SESSION['flash_error'] = 'Surat keterangan tidak ditemukan.';
    header('Location: list.php');
    exit;
}

// NIK tidak unik secara mutlak (data yang sudah nullified boleh punya NIK sama), jadi utamakan data yang aktif.
$stmt = $pdo->prepare("SELECT p.*, w.rt, w.rw, w.kelurahan, w.kecamatan, w.Kabupaten, w.ketua_rt, w.ketua_rw
                       FROM penduduk p
                       LEFT JOIN wilayah w ON w.id_wilayah = p.id_wilayah
                       WHERE p.nik = ? AND " . filterWilayahPenduduk('p.id_wilayah') . "
                       ORDER BY FIELD(p.status_cd, 'active', 'inactive', 'nullified'), p.id DESC
                       LIMIT 1");
$stmt->execute([$surat['nik']]);
$p = $stmt->fetch();

// Penduduk di luar lingkup wilayah pengguna: perlakukan seperti surat yang tidak ada.
if (!$p) {
    $_SESSION['flash_error'] = 'Surat keterangan tidak ditemukan.';
    header('Location: list.php');
    exit;
}

$rt = str_pad((string) $p['rt'], 3, '0', STR_PAD_LEFT);
$rw = str_pad((string) $p['rw'], 2, '0', STR_PAD_LEFT);
$kabupaten = namaKabupaten($p['Kabupaten']);
$kota = trim(preg_replace('/^(kabupaten|kota)\s+/i', '', $kabupaten));
$wilayahKalimat = 'RT.' . $rt . ' RW.' . $rw . ' Desa ' . $p['kelurahan'] . ' Kecamatan ' . $p['kecamatan'] . ' ' . $kabupaten;
$keperluan = array_filter(explode(',', $surat['keperluan']));

function e($v)
{
    return htmlspecialchars((string) $v);
}

function kotak(bool $centang, string $label = ''): string
{
    return '<span class="opsi"><span class="kotak">' . ($centang ? '&#10003;' : '') . '</span>' . e($label) . '</span>';
}

$agamaKotak = ['Islam' => 'Islam', 'Kristen' => 'Kristen', 'Hindu' => 'Hindu', 'Budha' => 'Buddha'];
$agamaLain = !in_array($p['agama'], $agamaKotak, true) ? $p['agama'] : '';
$pernahKawin = in_array($p['status_kawin'], ['Cerai Hidup', 'Cerai Mati'], true);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Keterangan <?= e($surat['nomor_surat']) ?> - <?= e($p['nama_lengkap']) ?></title>
    <style>
        @page { size: A4; margin: 18mm 20mm; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #000; margin: 24px auto; max-width: 720px; }
        .toolbar { margin-bottom: 16px; }
        .kop { display: flex; align-items: center; border-bottom: 3px double #000; padding-bottom: 8px; }
        .kop img { width: 72px; height: 72px; object-fit: contain; }
        .kop-text { flex: 1; text-align: center; margin-right: 72px; }
        .kop-text .l1 { font-size: 22px; font-weight: bold; }
        .kop-text .l2, .kop-text .l3 { font-size: 13px; font-weight: bold; }
        .judul { text-align: center; margin: 18px 0 16px; }
        .judul .t { font-weight: bold; font-size: 13px; text-decoration: underline; }
        p { margin: 0 0 10px; line-height: 1.5; text-align: justify; }
        table.data { width: 100%; border-collapse: collapse; margin: 6px 0 14px; }
        table.data td { padding: 3px 2px; vertical-align: top; }
        table.data td.label { width: 140px; }
        table.data td.titik { width: 12px; }
        table.data td.isi { border-bottom: 1px solid #000; }
        table.data td.isi.polos { border-bottom: none; }
        .opsi { display: inline-flex; align-items: center; vertical-align: middle; margin-right: 22px; }
        .kotak { display: inline-flex; align-items: center; justify-content: center; width: 14px; height: 14px;
                 border: 1px solid #000; margin-right: 6px; font-size: 12px; font-weight: bold; line-height: 1; }
        table.keperluan { width: 100%; margin: 4px 0 14px; }
        table.keperluan td { padding: 4px 0; width: 50%; }
        table.keperluan .kotak { width: 34px; height: 16px; margin-right: 10px; }
        .ttd { display: flex; justify-content: space-between; margin-top: 26px; }
        .ttd > div { width: 45%; text-align: center; }
        /* Ruang untuk tanda tangan dan cap stempel basah; sama untuk RW dan RT supaya nama sejajar. */
        .ttd .nama { margin-top: 100px; font-weight: bold; text-decoration: underline; text-transform: uppercase; }
        .catatan { margin-top: 40px; padding-top: 6px; border-top: 1px solid #000; font-size: 10.5px; font-style: italic; }
        @media print {
            .toolbar { display: none; }
            body { margin: 0; max-width: none; }
            /* Di kertas, catatan selalu berada di bagian bawah halaman. */
            .catatan { position: fixed; bottom: 0; left: 0; right: 0; margin-top: 0; }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <button onclick="window.print()">Cetak</button>
    <a href="list.php">Kembali ke daftar surat</a>
</div>

<div class="kop">
    <img src="../assets/img/logo-kabupaten-bogor.png" alt="Logo Kabupaten Bogor">
    <div class="kop-text">
        <div class="l1">PENGURUS RT.<?= e($rt) ?> RW.<?= e($rw) ?></div>
        <div class="l2"><?= e(strtoupper('Desa ' . $p['kelurahan'] . ' Kecamatan ' . $p['kecamatan'])) ?></div>
        <div class="l3"><?= e(strtoupper($kabupaten)) ?></div>
    </div>
</div>

<div class="judul">
    <div class="t">SURAT KETERANGAN</div>
    <div>Nomor : <?= e($surat['nomor_surat']) ?></div>
</div>

<p>Yang bertanda tangan di bawah ini, Ketua <?= e($wilayahKalimat) ?> Dengan ini menerangkan bahwa :</p>

<table class="data">
    <tr><td class="label">Nama Lengkap</td><td class="titik">:</td><td class="isi"><?= e($p['nama_lengkap']) ?></td></tr>
    <tr><td class="label">Tempat/Tanggal Lahir</td><td class="titik">:</td><td class="isi"><?= e(trim($p['tempat_lahir'] . ', ' . tanggalIndonesia($p['tanggal_lahir']), ', ')) ?></td></tr>
    <tr><td class="label">Status Perkawinan</td><td class="titik">:</td><td class="isi polos">
        <?= kotak($p['status_kawin'] === 'Belum Kawin', 'Belum Kawin') ?>
        <?= kotak($p['status_kawin'] === 'Kawin', 'Kawin') ?>
        <?= kotak($pernahKawin, 'Pernah Kawin') ?>
    </td></tr>
    <tr><td class="label">Jenis Kelamin</td><td class="titik">:</td><td class="isi polos">
        <?= kotak($p['jenis_kelamin'] === 'L', 'Laki-laki') ?>
        <?= kotak($p['jenis_kelamin'] === 'P', 'Perempuan') ?>
    </td></tr>
    <tr><td class="label">Agama</td><td class="titik">:</td><td class="isi polos">
        <?php foreach ($agamaKotak as $label => $nilai): ?><?= kotak($p['agama'] === $nilai, $label) ?><?php endforeach; ?>
        <?php if ($agamaLain !== ''): ?><?= kotak(true, $agamaLain) ?><?php endif; ?>
    </td></tr>
    <tr><td class="label">Kewarganegaraan</td><td class="titik">:</td><td class="isi"><?= e($surat['kewarganegaraan']) ?></td></tr>
    <tr><td class="label">Pekerjaan</td><td class="titik">:</td><td class="isi"><?= e($p['pekerjaan'] ?: '-') ?></td></tr>
    <tr><td class="label">NO. NIK</td><td class="titik">:</td><td class="isi"><?= e($surat['nik']) ?></td></tr>
    <tr><td class="label">Alamat</td><td class="titik">:</td><td class="isi"><?= e($p['alamat'] . ' RT.' . $rt . '/RW.' . $rw) ?></td></tr>
</table>

<p>Orang tersebut di atas adalah benar warga <?= e($wilayahKalimat) ?>, Surat Keterangan ini dibuat sebagai kelengkapan untuk mengurus surat :</p>

<table class="keperluan">
    <?php [$kiri, $kanan] = array_chunk(KEPERLUAN_SURAT, 6); ?>
    <?php foreach ($kiri as $i => $opt): ?>
        <tr>
            <?php foreach ([$opt, $kanan[$i]] as $o): ?>
                <td>
                    <?= kotak(in_array($o, $keperluan, true), $o) ?>
                    <?php if ($o === 'Keterangan Lainnya' && $surat['keperluan_lainnya']): ?>: <?= e($surat['keperluan_lainnya']) ?><?php endif; ?>
                </td>
            <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
</table>

<p>Demikian Surat Pengantar ini dibuat untuk dipergunakan sebagaimana mestinya</p>

<div class="ttd">
    <div>
        <div>Mengetahui,</div>
        <div>Ketua RW <?= e($rw) ?></div>
        <div class="nama"><?= $p['ketua_rw'] ? e($p['ketua_rw']) : '(..............................)' ?></div>
    </div>
    <div>
        <div><?= e($kota) ?>, <?= e(tanggalIndonesia($surat['tanggal_surat'])) ?></div>
        <div>Ketua RT.<?= e($rt) ?>/RW.<?= e($rw) ?></div>
        <div class="nama"><?= $p['ketua_rt'] ? e($p['ketua_rt']) : '(..............................)' ?></div>
    </div>
</div>

<div class="catatan">
    * Surat Keterangan tidak sah jika tidak ada cap stempel basah dan tanda tangan dari Ketua RT dan Ketua RW setempat
</div>

</body>
</html>
