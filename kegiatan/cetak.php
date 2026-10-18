<?php
// Cetak laporan pelaksanaan kegiatan. Bulan laporan diambil dari tanggal terbit (published_at).
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/berita.php';
require_once __DIR__ . '/../includes/surat.php';
// Sama seperti edit: hanya kegiatan dalam lingkup wilayah pengguna.
requireLogin();

$pdo = getDB();
$modul = MODUL_KEGIATAN;

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT b.*, w.rt, w.rw, w.kelurahan, w.ketua_rt, w.ketua_rw
                       FROM berita b
                       LEFT JOIN wilayah w ON w.id_wilayah = b.id_wilayah
                       WHERE b.id = ? AND ' . kondisiModulKonten($pdo, $modul));
$stmt->execute([$id]);
$k = $stmt->fetch();

if (!$k) {
    $_SESSION['flash_error'] = 'Kegiatan tidak ditemukan.';
    header('Location: list.php');
    exit;
}

$foto = daftarFotoBerita($pdo, $id);

// Kegiatan yang belum pernah terbit belum punya published_at; pakai tanggal dibuat sebagai cadangan.
$tanggal = $k['published_at'] ?: $k['created_at'];
$tanggalPanjang = mb_strtoupper(tanggalIndonesia($tanggal));             // 24 MEI 2026
$bulanTahun = substr($tanggalPanjang, strpos($tanggalPanjang, ' ') + 1); // MEI 2026

$rtRw = $k['rt'] !== null ? 'RT ' . $k['rt'] . ' RW ' . $k['rw'] : 'RT .... RW ....';
$desa = $k['kelurahan'] ?? '....................';

function e($v)
{
    return htmlspecialchars((string) $v);
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Kegiatan <?= e($rtRw) ?> - <?= e($bulanTahun) ?></title>
    <style>
        @page { size: A4; margin: 15mm 18mm; }
        body { font-family: Calibri, Arial, Helvetica, sans-serif; font-size: 12.5px; color: #000; margin: 24px auto; max-width: 720px; }
        .toolbar { margin-bottom: 16px; }
        .lembar { border: 1px solid #000; }
        .kepala { text-align: center; padding: 8px 10px; border-bottom: 1px solid #000; line-height: 1.45; text-transform: uppercase; font-size: 13px; }
        .kepala .bulan { font-weight: bold; text-decoration: underline; }
        .isi { padding: 10px 14px 24px; min-height: 420px; }
        .isi .label { margin-bottom: 14px; }
        .konten { line-height: 1.6; }
        .konten p { margin: 0 0 6px; }
        .konten ol, .konten ul { margin: 4px 0 8px; padding-left: 22px; }
        .konten .ql-align-center { text-align: center; }
        .konten .ql-align-right { text-align: right; }
        .konten .ql-align-justify { text-align: justify; }
        .galeri { display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; margin-top: 16px; }
        .galeri figure { margin: 0; text-align: center; page-break-inside: avoid; }
        .galeri.satu figure { width: 82%; }
        .galeri.banyak figure { width: calc(50% - 6px); }
        .galeri img { width: 100%; max-height: 330px; object-fit: cover; display: block; }
        .galeri.banyak img { height: 210px; }
        .galeri figcaption { font-size: 11px; margin-top: 4px; font-style: italic; }
        .ttd { display: flex; justify-content: space-between; margin-top: 18px; text-transform: uppercase; font-size: 11.5px; }
        .ttd > div { width: 45%; }
        .ttd .kiri { padding-left: 50px; }
        .ttd .kanan { text-align: right; padding-right: 4px; }
        .ttd .jabatan { margin-top: 26px; }
        .ttd .nama { margin-top: 70px; text-transform: none; }
        /* Jabatan dan nama Ketua RW/RT rata tengah satu sama lain; bloknya tetap di sisi kiri/kanan. */
        .ttd .blok-tengah { display: inline-block; text-align: center; }
        @media print {
            .toolbar { display: none; }
            body { margin: 0; max-width: none; }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <button onclick="window.print()">Cetak</button>
    <a href="list.php">Kembali ke daftar kegiatan</a>
</div>

<div class="lembar">
    <div class="kepala">
        <div>Laporan Pelaksanaan Kegiatan <?= e($rtRw) ?></div>
        <div>Desa <?= e($desa) ?></div>
        <div class="bulan">Bulan <?= e($bulanTahun) ?></div>
    </div>
    <div class="isi">
        <div class="label">Laporan :</div>
        <div class="konten"><?= saringKontenBerita($k['konten']) ?></div>

        <?php if ($foto): ?>
            <div class="galeri <?= count($foto) === 1 ? 'satu' : 'banyak' ?>">
                <?php foreach ($foto as $f): ?>
                    <figure>
                        <img src="../<?= e($f['file']) ?>" alt="<?= e($f['keterangan'] ?: $k['judul']) ?>">
                        <?php if ($f['keterangan']): ?><figcaption><?= e($f['keterangan']) ?></figcaption><?php endif; ?>
                    </figure>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="ttd">
    <div class="kiri">
        <div>&nbsp;</div>
        <div class="blok-tengah">
            <div class="jabatan">Mengetahui</div>
            <div class="jabatan">Ketua RW <?= e($k['rw'] ?? '....') ?></div>
            <div class="nama"><?= $k['ketua_rw'] ? e($k['ketua_rw']) : '(..............................)' ?></div>
        </div>
    </div>
    <div class="kanan">
        <div><?= e(mb_strtoupper($desa)) ?>, <?= e($tanggalPanjang) ?></div>
        <div class="jabatan">&nbsp;</div>
        <div class="blok-tengah">
            <div class="jabatan">Ketua <?= e($rtRw) ?></div>
            <div class="nama"><?= $k['ketua_rt'] ? e($k['ketua_rt']) : '(..............................)' ?></div>
        </div>
    </div>
</div>

</body>
</html>
