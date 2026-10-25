<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/surat.php';
// Butuh akses menu Surat Keterangan; data penduduk dibatasi lingkup wilayah pengguna (filterWilayahPenduduk).
requireAkses('surat_keterangan');

$pdo = getDB();

$errors = [];
$form = [
    'tanggal_surat' => date('Y-m-d'), 'nik' => '', 'kewarganegaraan' => 'WNI',
    'keperluan' => [], 'keperluan_lainnya' => '',
];

// Penduduk aktif yang dipilih, beserta wilayahnya (dipakai untuk kop & tanda tangan surat).
function cariPendudukSurat(PDO $pdo, string $nik)
{
    $stmt = $pdo->prepare("SELECT p.nik, p.nama_lengkap, p.tempat_lahir, p.tanggal_lahir, p.jenis_kelamin, p.alamat,
                                  p.id_wilayah, w.rt, w.rw, w.kelurahan
                           FROM penduduk p
                           LEFT JOIN wilayah w ON w.id_wilayah = p.id_wilayah
                           WHERE p.nik = ? AND p.status_cd = 'active' AND " . filterWilayahPenduduk('p.id_wilayah') . "
                           LIMIT 1");
    $stmt->execute([$nik]);
    return $stmt->fetch();
}

$penduduk = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['tanggal_surat'] = trim($_POST['tanggal_surat'] ?? '');
    $form['nik'] = trim($_POST['nik'] ?? '');
    $form['kewarganegaraan'] = trim($_POST['kewarganegaraan'] ?? '');
    $form['keperluan'] = array_values(array_intersect(KEPERLUAN_SURAT, (array) ($_POST['keperluan'] ?? [])));
    $form['keperluan_lainnya'] = trim($_POST['keperluan_lainnya'] ?? '');

    $d = DateTime::createFromFormat('Y-m-d', $form['tanggal_surat']);
    if (!$d || $d->format('Y-m-d') !== $form['tanggal_surat']) $errors[] = 'Tanggal surat tidak valid.';

    if ($form['nik'] === '') {
        $errors[] = 'Penduduk wajib dipilih.';
    } else {
        $penduduk = cariPendudukSurat($pdo, $form['nik']);
        if (!$penduduk) {
            $errors[] = 'Penduduk tidak ditemukan atau tidak aktif.';
        } elseif (!$penduduk['id_wilayah'] || $penduduk['rt'] === null) {
            $errors[] = 'Penduduk ini belum memiliki data Wilayah (RT/RW). Lengkapi dulu di menu Data Penduduk.';
        }
    }

    if ($form['kewarganegaraan'] === '') $errors[] = 'Kewarganegaraan wajib diisi.';
    if (!$form['keperluan']) $errors[] = 'Pilih minimal satu keperluan surat.';
    if (in_array('Keterangan Lainnya', $form['keperluan'], true) && $form['keperluan_lainnya'] === '') {
        $errors[] = 'Isi keterangan untuk keperluan "Keterangan Lainnya".';
    }
    if (!in_array('Keterangan Lainnya', $form['keperluan'], true)) $form['keperluan_lainnya'] = '';

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $nomor = nomorSuratBerikut($pdo, $form['tanggal_surat'], true);
            $stmt = $pdo->prepare('INSERT INTO surat_keterangan
                (nomor_surat, no_urut, bulan, tahun, tanggal_surat, nik, kewarganegaraan, keperluan, keperluan_lainnya, created_by)
                VALUES (?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([
                $nomor['nomor_surat'], $nomor['no_urut'], $nomor['bulan'], $nomor['tahun'], $form['tanggal_surat'],
                $form['nik'], $form['kewarganegaraan'], implode(',', $form['keperluan']),
                $form['keperluan_lainnya'] ?: null, currentUser()['id'],
            ]);
            $suratId = (int) $pdo->lastInsertId();
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }

        $_SESSION['flash_success'] = 'Surat keterangan nomor ' . $nomor['nomor_surat'] . ' berhasil dibuat.';
        header('Location: cetak.php?id=' . $suratId);
        exit;
    }
}

$nomorPratinjau = nomorSuratBerikut($pdo, $form['tanggal_surat'] && strtotime($form['tanggal_surat']) ? $form['tanggal_surat'] : date('Y-m-d'))['nomor_surat'];

$pageTitle = 'Buat Surat Keterangan';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Buat Surat Keterangan</h4>
    <a href="list.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" id="formSurat">
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Nomor Surat</label>
                    <input type="text" id="nomorSurat" class="form-control" value="<?= htmlspecialchars($nomorPratinjau) ?>" readonly>
                    <div class="form-text">Dibuat otomatis: no. urut per bulan / bulan romawi / tahun.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal Surat <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_surat" id="tanggalSurat" class="form-control" value="<?= htmlspecialchars($form['tanggal_surat']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kewarganegaraan <span class="text-danger">*</span></label>
                    <input type="text" name="kewarganegaraan" class="form-control" maxlength="30" value="<?= htmlspecialchars($form['kewarganegaraan']) ?>" required>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <label class="form-label">Cari Penduduk <span class="text-danger">*</span></label>
            <div class="position-relative">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="cariPenduduk" class="form-control" placeholder="Ketik nama atau NIK penduduk (minimal 2 huruf)..." autocomplete="off">
                </div>
                <div id="hasilCari" class="list-group position-absolute w-100 shadow-sm" style="z-index: 1000; max-height: 320px; overflow-y: auto;"></div>
            </div>
            <input type="hidden" name="nik" id="nikPenduduk" value="<?= htmlspecialchars($form['nik']) ?>">

            <div id="pendudukTerpilih" class="border rounded p-3 mt-3 bg-light <?= $penduduk ? '' : 'd-none' ?>">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fw-semibold" id="pNama"><?= $penduduk ? htmlspecialchars($penduduk['nama_lengkap']) : '' ?></div>
                        <div class="small text-muted">NIK: <span id="pNik"><?= $penduduk ? htmlspecialchars(tampilNik($penduduk['nik'])) : '' ?></span></div>
                        <div class="small">TTL: <span id="pTtl"><?= $penduduk ? htmlspecialchars(trim($penduduk['tempat_lahir'] . ', ' . tampilTanggalLahir($penduduk['tanggal_lahir'], 'tanggalIndonesia'), ', ')) : '' ?></span></div>
                        <div class="small">Alamat: <span id="pAlamat"><?= $penduduk ? htmlspecialchars($penduduk['alamat']) : '' ?></span></div>
                        <div class="small">Wilayah: <span id="pWilayah"><?= $penduduk && $penduduk['rt'] !== null ? htmlspecialchars('RT ' . $penduduk['rt'] . '/RW ' . $penduduk['rw'] . ', ' . $penduduk['kelurahan']) : '<span class="text-danger">Belum ada wilayah</span>' ?></span></div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="gantiPenduduk">Ganti</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <label class="form-label">Keperluan Surat <span class="text-danger">*</span></label>
            <div class="row">
                <?php foreach (array_chunk(KEPERLUAN_SURAT, 6) as $kolom): ?>
                    <div class="col-md-6">
                        <?php foreach ($kolom as $opt): $idOpt = 'kep_' . md5($opt); ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="keperluan[]" id="<?= $idOpt ?>" value="<?= htmlspecialchars($opt) ?>"
                                    <?= in_array($opt, $form['keperluan'], true) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="<?= $idOpt ?>"><?= htmlspecialchars($opt) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-2 <?= in_array('Keterangan Lainnya', $form['keperluan'], true) ? '' : 'd-none' ?>" id="wrapLainnya">
                <input type="text" name="keperluan_lainnya" class="form-control" maxlength="255" placeholder="Sebutkan keterangan lainnya..." value="<?= htmlspecialchars($form['keperluan_lainnya']) ?>">
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan &amp; Cetak</button>
    <a href="list.php" class="btn btn-outline-secondary">Batal</a>
</form>

<script>
(function () {
    const input = document.getElementById('cariPenduduk');
    const hasil = document.getElementById('hasilCari');
    const nik = document.getElementById('nikPenduduk');
    const kartu = document.getElementById('pendudukTerpilih');
    let timer = null;

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : s;
        return d.innerHTML;
    }

    function pilih(p) {
        nik.value = p.nik;
        document.getElementById('pNama').textContent = p.nama;
        document.getElementById('pNik').textContent = p.nik_tampil;
        document.getElementById('pTtl').textContent = p.ttl;
        document.getElementById('pAlamat').textContent = p.alamat;
        document.getElementById('pWilayah').innerHTML = p.wilayah ? esc(p.wilayah) : '<span class="text-danger">Belum ada wilayah</span>';
        kartu.classList.remove('d-none');
        hasil.innerHTML = '';
        input.value = '';
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        const q = input.value.trim();
        if (q.length < 2) { hasil.innerHTML = ''; return; }
        timer = setTimeout(function () {
            fetch('cari_penduduk.php?q=' + encodeURIComponent(q))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.length) {
                        hasil.innerHTML = '<div class="list-group-item text-muted small">Tidak ada penduduk aktif yang cocok.</div>';
                        return;
                    }
                    hasil.innerHTML = '';
                    data.forEach(function (p) {
                        const a = document.createElement('button');
                        a.type = 'button';
                        a.className = 'list-group-item list-group-item-action';
                        a.innerHTML = '<div class="fw-semibold">' + esc(p.nama) + '</div>'
                            + '<div class="small text-muted">NIK ' + esc(p.nik_tampil) + ' &middot; ' + esc(p.wilayah || 'Belum ada wilayah') + '</div>';
                        a.addEventListener('click', function () { pilih(p); });
                        hasil.appendChild(a);
                    });
                });
        }, 250);
    });

    document.addEventListener('click', function (e) {
        if (!hasil.contains(e.target) && e.target !== input) hasil.innerHTML = '';
    });

    document.getElementById('gantiPenduduk').addEventListener('click', function () {
        nik.value = '';
        kartu.classList.add('d-none');
        input.focus();
    });

    document.getElementById('tanggalSurat').addEventListener('change', function () {
        if (!this.value) return;
        fetch('nomor.php?tanggal=' + encodeURIComponent(this.value))
            .then(function (r) { return r.json(); })
            .then(function (data) { if (data.nomor_surat) document.getElementById('nomorSurat').value = data.nomor_surat; });
    });

    const cekLainnya = document.querySelector('input[name="keperluan[]"][value="Keterangan Lainnya"]');
    cekLainnya.addEventListener('change', function () {
        document.getElementById('wrapLainnya').classList.toggle('d-none', !this.checked);
    });

    document.getElementById('formSurat').addEventListener('submit', function (e) {
        if (!nik.value) {
            e.preventDefault();
            alert('Pilih penduduk terlebih dahulu.');
            input.focus();
        }
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
