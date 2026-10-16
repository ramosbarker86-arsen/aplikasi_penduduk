<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pdo = getDB();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM wilayah WHERE id_wilayah = ?');
$stmt->execute([$id]);
$existing = $stmt->fetch();

if (!$existing) {
    $_SESSION['flash_error'] = 'Data wilayah tidak ditemukan.';
    header('Location: list.php');
    exit;
}

$errors = [];
$form = [
    'propinsi' => $existing['propinsi'], 'Kabupaten' => $existing['Kabupaten'],
    'kecamatan' => $existing['kecamatan'], 'kelurahan' => $existing['kelurahan'],
    'rw' => $existing['rw'], 'rt' => $existing['rt'],
];

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

    if (!$errors) {
        // id_wilayah sekarang AUTO_INCREMENT dan tidak bisa diubah dari form (tidak perlu lagi
        // trik INSERT+DELETE untuk ganti primary key).
        $stmt = $pdo->prepare('UPDATE wilayah SET propinsi = ?, Kabupaten = ?, kecamatan = ?, kelurahan = ?, rw = ?, rt = ? WHERE id_wilayah = ?');
        $stmt->execute([
            $form['propinsi'], $form['Kabupaten'], $form['kecamatan'], $form['kelurahan'], $form['rw'], $form['rt'], $id,
        ]);

        $_SESSION['flash_success'] = 'Data wilayah berhasil diperbarui.';
        header('Location: list.php');
        exit;
    }
}

$pageTitle = 'Edit Wilayah';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Edit Wilayah</h4>
    <a href="list.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="post">
            <div class="form-section">
                <div class="form-section-title">
                    <span class="badge-icon"><i class="bi bi-geo-alt"></i></span> Data Wilayah
                </div>
                <div class="row g-3 mb-2">
                    <div class="col-md-3">
                        <label class="form-label">ID Wilayah</label>
                        <input type="text" class="form-control" value="<?= (int) $id ?>" disabled>
                    </div>
                    <div class="col-md-9">
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
                </div>
            </div>

            <div class="form-action-bar">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Perubahan</button>
                <a href="list.php" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
