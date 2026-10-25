<?php
// Laporan data penduduk (cetak A4 landscape). Isi & kop mengikuti lingkup sub_level/id_wilayah pengguna,
// termasuk admin yang punya sub_level: RT -> RT-nya, RW -> semua RT di RW-nya,
// Kelurahan -> semua RT/RW di kelurahannya. Admin tanpa sub_level -> semua wilayah.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/surat.php';
requireAkses('data_penduduk');

$pdo = getDB();
$lingkup = filterWilayahPenduduk('p.id_wilayah', true);

// Wilayah pengguna yang login (untuk judul laporan & tanda tangan).
$stmt = $pdo->prepare('SELECT u.sub_level, w.rt, w.rw, w.kelurahan, w.kecamatan, w.Kabupaten, w.ketua_rt, w.ketua_rw
                       FROM users u LEFT JOIN wilayah w ON w.id_wilayah = u.id_wilayah WHERE u.id = ?');
$stmt->execute([currentUser()['id']]);
$saya = $stmt->fetch() ?: [];

// Pilihan wilayah hanya dalam lingkup pengguna.
$wilayahList = $pdo->query('SELECT id_wilayah, rt, rw, kelurahan, kecamatan, Kabupaten, ketua_rt, ketua_rw FROM wilayah
                            WHERE ' . filterWilayahPenduduk('id_wilayah', true) . ' ORDER BY kelurahan, rw, rt')->fetchAll();
$wilayahById = array_column($wilayahList, null, 'id_wilayah');

$statusOptions = ['active' => 'Aktif', 'inactive' => 'Tidak Aktif', 'semua' => 'Aktif & Tidak Aktif'];
$jkOptions = ['' => 'Semua', 'L' => 'Laki-laki', 'P' => 'Perempuan'];

$filterWilayah = (int) ($_GET['wilayah'] ?? 0);
if (!isset($wilayahById[$filterWilayah])) $filterWilayah = 0;
$filterStatus = isset($statusOptions[$_GET['status'] ?? '']) ? $_GET['status'] : 'active';
$filterJk = isset($jkOptions[$_GET['jk'] ?? '']) ? (string) ($_GET['jk'] ?? '') : '';

$where = "WHERE p.status_cd <> 'nullified' AND $lingkup";
$params = [];
if ($filterStatus !== 'semua') {
    $where .= ' AND p.status_cd = ?';
    $params[] = $filterStatus;
}
if ($filterWilayah) {
    $where .= ' AND p.id_wilayah = ?';
    $params[] = $filterWilayah;
}
if ($filterJk !== '') {
    $where .= ' AND p.jenis_kelamin = ?';
    $params[] = $filterJk;
}

$stmt = $pdo->prepare("SELECT p.*, w.rt, w.rw, w.kelurahan
                       FROM penduduk p
                       LEFT JOIN wilayah w ON w.id_wilayah = p.id_wilayah
                       $where
                       ORDER BY CAST(w.rt AS UNSIGNED), CAST(w.rw AS UNSIGNED), p.nama_lengkap");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$jumlahL = count(array_filter($rows, fn($r) => $r['jenis_kelamin'] === 'L'));
$jumlahKK = count(array_unique(array_column($rows, 'no_kk')));

// Judul lingkup laporan & penanda tangan.
$pad = fn($v, $n) => str_pad((string) $v, $n, '0', STR_PAD_LEFT);
$fokus = $filterWilayah ? $wilayahById[$filterWilayah] : null;
$barisDesa = '';
if ($fokus) {
    $judulWilayah = 'RT ' . $pad($fokus['rt'], 2) . ' RW ' . $pad($fokus['rw'], 2) . ' Desa ' . $fokus['kelurahan'];
    $ttd = ['Ketua RT ' . $pad($fokus['rt'], 2) . ' RW ' . $pad($fokus['rw'], 2), $fokus['ketua_rt']];
} elseif (empty($saya['sub_level']) || $saya['rt'] === null) {
    // Tanpa sub_level/wilayah: admin melihat semua wilayah; pengguna lain tidak punya lingkup data.
    $judulWilayah = isAdmin() ? 'Semua Wilayah' : '-';
    $ttd = null;
} elseif ($saya['sub_level'] === 'rt') {
    $judulWilayah = 'RT ' . $pad($saya['rt'], 2) . ' RW ' . $pad($saya['rw'], 2) . ' Desa ' . $saya['kelurahan'];
    $ttd = ['Ketua RT ' . $pad($saya['rt'], 2) . ' RW ' . $pad($saya['rw'], 2), $saya['ketua_rt']];
} elseif ($saya['sub_level'] === 'rw') {
    // Pengguna RW: laporan berisi semua RT di RW-nya; nama desa pindah ke baris kecamatan.
    $judulWilayah = 'Semua Wilayah RW ' . $pad($saya['rw'], 2);
    $barisDesa = 'Desa ' . $saya['kelurahan'];
    $ttd = ['Ketua RW ' . $pad($saya['rw'], 2), $saya['ketua_rw']];
} else {
    $judulWilayah = 'Desa ' . $saya['kelurahan'];
    $ttd = null;
}
$sub = $fokus ?: $saya;
$kecamatanKab = !empty($sub['kecamatan']) ? 'Kecamatan ' . $sub['kecamatan'] . ' ' . namaKabupaten($sub['Kabupaten']) : '';
if ($barisDesa !== '') {
    $kecamatanKab = trim($barisDesa . ' ' . $kecamatanKab);
}
$kota = !empty($sub['Kabupaten']) ? trim(preg_replace('/^(kabupaten|kota)\s+/i', '', namaKabupaten($sub['Kabupaten']))) : '';

function umurTahun(?string $tgl): string
{
    if (!$tgl || !strtotime($tgl)) return '-';
    return (string) (new DateTime($tgl))->diff(new DateTime('today'))->y;
}

function e($v)
{
    return htmlspecialchars((string) $v);
}

// Kolom laporan: kunci => [judul, atribut <th>, atribut <td>, isi sel (HTML sudah di-escape)].
// Kolom "No" selalu tampil; kolom lain bisa dipilih lewat ceklist (kolom[]).
$kolomLaporan = [
    'nik' => ['NIK', '', '', fn($r) => e(tampilNik($r['nik']))],
    'no_kk' => ['No. KK', '', '', fn($r) => e(tampilNoKk($r['no_kk']))],
    'nama' => ['Nama Lengkap', '', '', fn($r) => e($r['nama_lengkap'])],
    'jk' => ['L/P', 'style="width:22px"', 'class="c"', fn($r) => e($r['jenis_kelamin'])],
    'ttl' => ['Tempat, Tgl Lahir', '', '', fn($r) => e($r['tempat_lahir'] ? $r['tempat_lahir'] . ', ' : '')
        . '<span style="white-space:nowrap">' . e(tampilTanggalLahir($r['tanggal_lahir'])) . '</span>'],
    'umur' => ['Umur', 'style="width:30px"', 'class="c"', fn($r) => e(umurTahun($r['tanggal_lahir']))],
    'agama' => ['Agama', '', '', fn($r) => e($r['agama'])],
    'pendidikan' => ['Pendidikan', '', '', fn($r) => e($r['pendidikan'] ?: '-')],
    'pekerjaan' => ['Pekerjaan', '', '', fn($r) => e($r['pekerjaan'] ?: '-')],
    'status_kawin' => ['Status Kawin', '', '', fn($r) => e($r['status_kawin'])],
    'hub_keluarga' => ['Hub. Keluarga', '', '', fn($r) => e($r['status_keluarga'] ?: '-')],
    'alamat' => ['Alamat', '', '', fn($r) => e($r['alamat'])],
    'rt_rw' => ['RT/RW', 'style="width:46px"', 'class="c"', fn($r) => $r['rt'] !== null ? e($r['rt'] . '/' . $r['rw']) : '-'],
    'email' => ['Email', '', 'style="word-break: break-all"', fn($r) => $r['email'] ? e(tampilEmail($r['email'])) : '-'],
    'telepon' => ['No. Telepon', '', 'style="white-space: nowrap"', fn($r) => e($r['no_telepon'] ?: '-')],
    'status' => ['Status', 'style="width:48px"', 'class="c"', fn($r) => $r['status_cd'] === 'active' ? 'Aktif' : 'Tidak Aktif'],
];

// Tanpa pilihan (pertama kali dibuka) semua kolom tampil. "pilih_kolom" menandai form sudah dikirim,
// supaya kolom yang dikosongkan benar-benar disembunyikan.
$kolomDipilih = array_keys($kolomLaporan);
$peringatanKolom = '';
if (isset($_GET['pilih_kolom'])) {
    $kolomDipilih = array_values(array_intersect(array_keys($kolomLaporan), (array) ($_GET['kolom'] ?? [])));
    if (!$kolomDipilih) {
        $kolomDipilih = ['nama'];
        $peringatanKolom = 'Pilih minimal satu kolom; kolom Nama Lengkap ditampilkan.';
    }
}
$kolomTampil = array_intersect_key($kolomLaporan, array_flip($kolomDipilih));

// Kop yang bisa diubah: judul laporan dan satu baris keterangan opsional di bawah kop.
const JUDUL_LAPORAN_DEFAULT = 'LAPORAN DATA PENDUDUK';
$judulLaporan = mb_substr(trim((string) ($_GET['judul'] ?? '')), 0, 100) ?: JUDUL_LAPORAN_DEFAULT;
$keteranganLaporan = mb_substr(trim((string) ($_GET['keterangan'] ?? '')), 0, 200);

// Kolom tambahan bernama bebas (sel kosong untuk diisi manual setelah dicetak), maksimal 5.
const MAKS_KOLOM_TAMBAHAN = 5;
$kolomTambahan = [];
foreach ((array) ($_GET['kolom_tambahan'] ?? []) as $nama) {
    $nama = mb_substr(trim((string) $nama), 0, 40);
    if ($nama !== '' && count($kolomTambahan) < MAKS_KOLOM_TAMBAHAN) {
        $kolomTambahan[] = $nama;
    }
}
$jumlahKolomTabel = 1 + count($kolomTampil) + count($kolomTambahan);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title><?= e(mb_convert_case($judulLaporan, MB_CASE_TITLE)) ?> - <?= e($judulWilayah) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        @page { size: A4 landscape; margin: 12mm 10mm; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10.5px; color: #000; margin: 20px; }
        .toolbar { background: #f4f6f9; border: 1px solid #dde3ec; border-radius: 8px; padding: 10px 12px; margin-bottom: 16px;
                   display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; font-size: 13px; }
        .toolbar label { display: block; font-size: 11px; color: #555; margin-bottom: 2px; }
        .toolbar select, .toolbar button, .toolbar a.btn { font-size: 13px; padding: 5px 9px; border-radius: 6px; border: 1px solid #b9c2d0; background: #fff; }
        .toolbar .utama { background: #1a2a44; color: #fff; border-color: #1a2a44; cursor: pointer; }
        .toolbar a.btn { text-decoration: none; color: #1a2a44; }
        .kop { display: flex; align-items: center; border-bottom: 3px double #000; padding-bottom: 6px; margin-bottom: 10px; }
        .kop img { width: 54px; height: 54px; object-fit: contain; }
        .kop .teks { flex: 1; text-align: center; margin-right: 54px; }
        .kop .l1 { font-size: 16px; font-weight: bold; }
        .kop .l2 { font-size: 12px; font-weight: bold; }
        .meta { display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 10.5px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { border: 1px solid #333; padding: 3px 3px; vertical-align: top; font-size: 9.5px; }
        table.data th { background: #e9edf3; text-align: center; font-size: 9px; }
        table.data td.c { text-align: center; }
        table.data tr { page-break-inside: avoid; }
        thead { display: table-header-group; }
        .ringkas { display: flex; gap: 18px; margin-top: 8px; font-size: 11px; }
        .ringkas b { font-size: 12px; }
        .ttd { display: flex; justify-content: flex-end; margin-top: 18px; page-break-inside: avoid; }
        .ttd > div { text-align: center; width: 260px; }
        .ttd .nama { margin-top: 60px; font-weight: bold; text-decoration: underline; }
        .kosong { text-align: center; padding: 18px; color: #666; }
        .pilih-kolom { flex-basis: 100%; border-top: 1px solid #dde3ec; padding-top: 8px; }
        .pilih-kolom .daftar { display: flex; flex-wrap: wrap; gap: 4px 14px; }
        .pilih-kolom .daftar label { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; color: #222; margin: 0; cursor: pointer; }
        .pilih-kolom .aksi { font-size: 11px; margin-left: 8px; }
        .pilih-kolom .aksi a { color: #1a2a44; cursor: pointer; text-decoration: underline; }
        .peringatan { color: #b45309; font-size: 12px; margin-top: 4px; }
        .atur-kop { flex-basis: 100%; border-top: 1px solid #dde3ec; padding-top: 8px; display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; }
        .atur-kop input[type=text] { font-size: 13px; padding: 5px 8px; border-radius: 6px; border: 1px solid #b9c2d0; }
        .atur-kop .judul input { width: 300px; }
        .atur-kop .ket input { width: 380px; }
        .kolom-tambahan { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
        .kolom-tambahan .item { display: inline-flex; align-items: center; gap: 2px; }
        .kolom-tambahan .item input { width: 120px; }
        .kolom-tambahan .hapus { border: none; background: none; color: #b91c1c; cursor: pointer; font-size: 15px; padding: 0 3px; }
        /* Sel kolom tambahan diisi tulisan tangan/paraf setelah dicetak, jadi barisnya dibuat lebih tinggi. */
        table.data td.isian { min-width: 60px; height: 22px; }
        @media print { .toolbar { display: none; } body { margin: 0; } }
    </style>
</head>
<body>

<form class="toolbar" method="get">
    <div>
        <label>Wilayah</label>
        <select name="wilayah">
            <option value="0">Semua wilayah <?= (isAdmin() && empty($saya['sub_level'])) ? '' : 'saya' ?></option>
            <?php foreach ($wilayahList as $w): ?>
                <option value="<?= (int) $w['id_wilayah'] ?>" <?= $filterWilayah === (int) $w['id_wilayah'] ? 'selected' : '' ?>>
                    RT <?= e($w['rt']) ?>/RW <?= e($w['rw']) ?> - <?= e($w['kelurahan']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label>Status penduduk</label>
        <select name="status">
            <?php foreach ($statusOptions as $v => $l): ?>
                <option value="<?= $v ?>" <?= $filterStatus === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label>Jenis kelamin</label>
        <select name="jk">
            <?php foreach ($jkOptions as $v => $l): ?>
                <option value="<?= $v ?>" <?= $filterJk === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit">Tampilkan</button>
    <button type="button" class="utama" onclick="window.print()"><i class="bi bi-printer"></i> Cetak</button>
    <a class="btn" href="list.php">Kembali</a>
    <span style="color:#666; font-size:12px">Kertas: A4 landscape</span>

    <div class="pilih-kolom">
        <label>Kolom ditampilkan
            <span class="aksi"><a id="kolomSemua">Semua</a> &middot; <a id="kolomKosong">Kosongkan</a></span>
        </label>
        <input type="hidden" name="pilih_kolom" value="1">
        <div class="daftar">
            <?php foreach ($kolomLaporan as $kunci => [$judul]): ?>
                <label>
                    <input type="checkbox" name="kolom[]" value="<?= $kunci ?>" class="cek-kolom"
                        <?= in_array($kunci, $kolomDipilih, true) ? 'checked' : '' ?>>
                    <?= e($judul) ?>
                </label>
            <?php endforeach; ?>
        </div>
        <?php if ($peringatanKolom): ?><div class="peringatan"><?= e($peringatanKolom) ?></div><?php endif; ?>
    </div>

    <div class="atur-kop">
        <div class="judul">
            <label>Judul laporan</label>
            <input type="text" name="judul" maxlength="100" value="<?= e($judulLaporan) ?>" placeholder="<?= e(JUDUL_LAPORAN_DEFAULT) ?>">
        </div>
        <div class="ket">
            <label>Keterangan di bawah kop (opsional)</label>
            <input type="text" name="keterangan" maxlength="200" value="<?= e($keteranganLaporan) ?>" placeholder="mis. Untuk keperluan pendataan bantuan sosial">
        </div>
        <div>
            <label>Kolom tambahan (kosong, diisi manual setelah dicetak; maks. <?= MAKS_KOLOM_TAMBAHAN ?>)</label>
            <div class="kolom-tambahan" id="daftarKolomTambahan">
                <?php foreach ($kolomTambahan as $nama): ?>
                    <span class="item"><input type="text" name="kolom_tambahan[]" maxlength="40" value="<?= e($nama) ?>"><button type="button" class="hapus" title="Hapus kolom">&times;</button></span>
                <?php endforeach; ?>
                <button type="button" id="tambahKolom">+ Tambah kolom</button>
            </div>
        </div>
        <button type="submit" class="utama">Terapkan</button>
    </div>
</form>
<script>
// Laporan langsung diperbarui saat ceklist kolom diubah.
(function () {
    const form = document.querySelector('form.toolbar');
    const cek = form.querySelectorAll('.cek-kolom');
    cek.forEach(function (c) { c.addEventListener('change', function () { form.submit(); }); });
    document.getElementById('kolomSemua').addEventListener('click', function () { cek.forEach(function (c) { c.checked = true; }); form.submit(); });
    document.getElementById('kolomKosong').addEventListener('click', function () { cek.forEach(function (c) { c.checked = false; }); form.submit(); });

    // Kolom tambahan: tambah/hapus isian nama kolom; diterapkan saat tombol Terapkan ditekan.
    const daftar = document.getElementById('daftarKolomTambahan');
    const tombolTambah = document.getElementById('tambahKolom');
    const MAKS = <?= MAKS_KOLOM_TAMBAHAN ?>;
    function aturTombol() {
        tombolTambah.disabled = daftar.querySelectorAll('.item').length >= MAKS;
    }
    function pasangHapus(item) {
        item.querySelector('.hapus').addEventListener('click', function () { item.remove(); aturTombol(); });
    }
    daftar.querySelectorAll('.item').forEach(pasangHapus);
    tombolTambah.addEventListener('click', function () {
        const item = document.createElement('span');
        item.className = 'item';
        item.innerHTML = '<input type="text" name="kolom_tambahan[]" maxlength="40" placeholder="Nama kolom">'
            + '<button type="button" class="hapus" title="Hapus kolom">&times;</button>';
        daftar.insertBefore(item, tombolTambah);
        pasangHapus(item);
        aturTombol();
        item.querySelector('input').focus();
    });
    aturTombol();
})();
</script>

<div class="kop">
    <img src="../assets/img/logo-kabupaten-bogor.png" alt="Logo">
    <div class="teks">
        <div class="l1"><?= e(mb_strtoupper($judulLaporan)) ?></div>
        <div class="l2"><?= e(mb_strtoupper($judulWilayah)) ?></div>
        <?php if ($kecamatanKab): ?><div class="l2"><?= e(mb_strtoupper($kecamatanKab)) ?></div><?php endif; ?>
        <?php if ($keteranganLaporan !== ''): ?><div class="l2"><?= e(mb_strtoupper($keteranganLaporan)) ?></div><?php endif; ?>
    </div>
</div>

<div class="meta">
    <div>
        Status: <?= e($statusOptions[$filterStatus]) ?>
        <?= $filterJk !== '' ? ' &middot; Jenis kelamin: ' . e($jkOptions[$filterJk]) : '' ?>
    </div>
    <div>Dicetak: <?= e(tanggalIndonesia(date('Y-m-d'))) ?> oleh <?= e(currentUser()['nama_lengkap'] ?: currentUser()['username']) ?></div>
</div>

<table class="data">
    <thead>
        <tr>
            <th style="width:24px">No</th>
            <?php foreach ($kolomTampil as [$judul, $atributTh]): ?>
                <th <?= $atributTh ?>><?= e($judul) ?></th>
            <?php endforeach; ?>
            <?php foreach ($kolomTambahan as $nama): ?>
                <th><?= e($nama) ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php if (!$rows): ?>
            <tr><td colspan="<?= $jumlahKolomTabel ?>" class="kosong">Tidak ada data penduduk untuk filter ini.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $i => $r): ?>
            <tr>
                <td class="c"><?= $i + 1 ?></td>
                <?php foreach ($kolomTampil as [, , $atributTd, $isi]): ?>
                    <td <?= $atributTd ?>><?= $isi($r) ?></td>
                <?php endforeach; ?>
                <?php foreach ($kolomTambahan as $_): ?>
                    <td class="isian"></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="ringkas">
    <div>Jumlah penduduk: <b><?= count($rows) ?></b></div>
    <div>Laki-laki: <b><?= $jumlahL ?></b></div>
    <div>Perempuan: <b><?= count($rows) - $jumlahL ?></b></div>
    <div>Jumlah KK: <b><?= $jumlahKK ?></b></div>
</div>

<?php if ($ttd): ?>
    <div class="ttd">
        <div>
            <div><?= e($kota ? $kota . ', ' : '') ?><?= e(tanggalIndonesia(date('Y-m-d'))) ?></div>
            <div><?= e($ttd[0]) ?></div>
            <div class="nama"><?= $ttd[1] ? e($ttd[1]) : '(..............................)' ?></div>
        </div>
    </div>
<?php endif; ?>

</body>
</html>
