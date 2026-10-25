<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAkses('master_wilayah');

$pdo = getDB();

$errors = [];
$form = [
    'propinsi' => '', 'Kabupaten' => '', 'kecamatan' => '', 'kelurahan' => '',
    'rw' => '', 'rt' => '', 'ketua_rt' => '', 'ketua_rw' => '', 'status_cd' => 'active',
];
$statusOptions = ['active' => 'Aktif', 'inactive' => 'Tidak Aktif'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $key => $default) {
        $form[$key] = trim($_POST[$key] ?? '');
    }

    if ($form['propinsi'] === '') $errors[] = 'Propinsi wajib diisi.';
    if ($form['Kabupaten'] === '') $errors[] = 'Kabupaten wajib diisi.';
    if ($form['kecamatan'] === '') $errors[] = 'Kecamatan wajib diisi.';
    if ($form['kelurahan'] === '') $errors[] = 'Kelurahan wajib diisi.';
    if ($form['rw'] === '') $errors[] = 'RW wajib diisi.';
    if ($form['rt'] === '') $errors[] = 'RT wajib diisi.';
    if (!isset($statusOptions[$form['status_cd']])) $errors[] = 'Status tidak valid.';

    if (!$errors) {
        // id_wilayah sekarang AUTO_INCREMENT, jadi tidak perlu dikirim lagi.
        $stmt = $pdo->prepare('INSERT INTO wilayah (propinsi, Kabupaten, kecamatan, kelurahan, rw, rt, ketua_rt, ketua_rw, status_cd) VALUES (?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $form['propinsi'], $form['Kabupaten'], $form['kecamatan'], $form['kelurahan'],
            $form['rw'], $form['rt'], $form['ketua_rt'] ?: null, $form['ketua_rw'] ?: null, $form['status_cd'],
        ]);

        $_SESSION['flash_success'] = 'Data wilayah berhasil ditambahkan.';
        header('Location: list.php');
        exit;
    }
}

$pageTitle = 'Tambah Wilayah';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Tambah Wilayah</h4>
    <a href="list.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Propinsi <span class="text-danger">*</span></label>
                    <input type="text" name="propinsi" class="form-control" value="<?= htmlspecialchars($form['propinsi']) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Kabupaten <span class="text-danger">*</span></label>
                    <input type="text" name="Kabupaten" class="form-control" maxlength="100" value="<?= htmlspecialchars($form['Kabupaten']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Kecamatan <span class="text-danger">*</span></label>
                    <input type="text" name="kecamatan" class="form-control" value="<?= htmlspecialchars($form['kecamatan']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Kelurahan <span class="text-danger">*</span></label>
                    <input type="text" name="kelurahan" class="form-control" value="<?= htmlspecialchars($form['kelurahan']) ?>" required>
                </div>

                <div class="col-md-2">
                    <label class="form-label">RW <span class="text-danger">*</span></label>
                    <input type="text" name="rw" class="form-control" maxlength="3" value="<?= htmlspecialchars($form['rw']) ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">RT <span class="text-danger">*</span></label>
                    <input type="text" name="rt" class="form-control" maxlength="3" value="<?= htmlspecialchars($form['rt']) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Ketua RT</label>
                    <input type="text" name="ketua_rt" class="form-control" maxlength="100" value="<?= htmlspecialchars($form['ketua_rt']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Ketua RW</label>
                    <input type="text" name="ketua_rw" class="form-control" maxlength="100" value="<?= htmlspecialchars($form['ketua_rw']) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status_cd" class="form-select" required>
                        <?php foreach ($statusOptions as $value => $label): ?>
                            <option value="<?= $value ?>" <?= $form['status_cd'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                <a href="list.php" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
