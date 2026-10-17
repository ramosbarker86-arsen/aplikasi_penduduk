<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/surat.php';
// Semua pengguna boleh mengedit surat dalam lingkup wilayahnya; yang bisa diubah hanya keperluan surat.
requireLogin();

$pdo = getDB();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM surat_keterangan WHERE id = ?');
$stmt->execute([$id]);
$surat = $stmt->fetch();

// Sama seperti cetak.php: utamakan penduduk aktif dengan NIK ini, dan batasi lingkup wilayah pengguna.
$p = null;
if ($surat) {
    $stmt = $pdo->prepare("SELECT p.nama_lengkap, p.nik, w.rt, w.rw, w.kelurahan
                           FROM penduduk p
                           LEFT JOIN wilayah w ON w.id_wilayah = p.id_wilayah
                           WHERE p.nik = ? AND " . filterWilayahPenduduk('p.id_wilayah') . "
                           ORDER BY FIELD(p.status_cd, 'active', 'inactive', 'nullified'), p.id DESC
                           LIMIT 1");
    $stmt->execute([$surat['nik']]);
    $p = $stmt->fetch();
}

if (!$surat || !$p) {
    $_SESSION['flash_error'] = 'Surat keterangan tidak ditemukan.';
    header('Location: list.php');
    exit;
}

$errors = [];
$form = [
    'keperluan' => array_values(array_filter(explode(',', $surat['keperluan']))),
    'keperluan_lainnya' => (string) $surat['keperluan_lainnya'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['keperluan'] = array_values(array_intersect(KEPERLUAN_SURAT, (array) ($_POST['keperluan'] ?? [])));
    $form['keperluan_lainnya'] = trim($_POST['keperluan_lainnya'] ?? '');

    if (!$form['keperluan']) $errors[] = 'Pilih minimal satu keperluan surat.';
    if (in_array('Keterangan Lainnya', $form['keperluan'], true) && $form['keperluan_lainnya'] === '') {
        $errors[] = 'Isi keterangan untuk keperluan "Keterangan Lainnya".';
    }
    if (!in_array('Keterangan Lainnya', $form['keperluan'], true)) $form['keperluan_lainnya'] = '';

    if (!$errors) {
        $stmt = $pdo->prepare('UPDATE surat_keterangan SET keperluan = ?, keperluan_lainnya = ? WHERE id = ?');
        $stmt->execute([implode(',', $form['keperluan']), $form['keperluan_lainnya'] ?: null, $id]);

        $_SESSION['flash_success'] = 'Keperluan surat nomor ' . $surat['nomor_surat'] . ' berhasil diperbarui.';
        header('Location: list.php');
        exit;
    }
}

$pageTitle = 'Edit Surat Keterangan';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Edit Surat Keterangan</h4>
    <a href="list.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post">
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Nomor Surat</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($surat['nomor_surat']) ?>" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal Surat</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars(tanggalIndonesia($surat['tanggal_surat'])) ?>" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kewarganegaraan</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($surat['kewarganegaraan']) ?>" disabled>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Penduduk</label>
                    <input type="text" class="form-control" disabled
                           value="<?= htmlspecialchars($p['nama_lengkap'] . ' - NIK ' . tampilNik($p['nik']) . ($p['rt'] !== null ? ' - RT ' . $p['rt'] . '/RW ' . $p['rw'] . ', ' . $p['kelurahan'] : '')) ?>">
                </div>
            </div>
            <div class="form-text mt-2">Hanya keperluan surat yang bisa diubah. Nomor, tanggal, dan data penduduk tetap.</div>
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

    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Perubahan</button>
    <a href="list.php" class="btn btn-outline-secondary">Batal</a>
</form>

<script>
document.querySelector('input[name="keperluan[]"][value="Keterangan Lainnya"]').addEventListener('change', function () {
    document.getElementById('wrapLainnya').classList.toggle('d-none', !this.checked);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
