<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/upload_profil.php';
requireAdmin();

$pdo = getDB();

$errors = [];
$form = [
    'username' => '', 'nama_lengkap' => '', 'level' => 'user', 'status_cd' => 'active',
    'sub_level' => '', 'id_wilayah' => '',
];
$statusOptions = ['active' => 'Aktif', 'inactive' => 'Tidak Aktif'];
$subLevelOptions = ['rt' => 'RT', 'rw' => 'RW', 'kelurahan' => 'Kelurahan'];
$wilayahList = $pdo->query("SELECT id_wilayah, propinsi, Kabupaten, kecamatan, kelurahan, rw, rt FROM wilayah WHERE status_cd = 'active' ORDER BY kelurahan ASC, rw ASC, rt ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $key => $default) {
        $form[$key] = trim($_POST[$key] ?? '');
    }
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if ($form['username'] === '') {
        $errors[] = 'Username wajib diisi.';
    } elseif (!preg_match('/^[A-Za-z0-9_.]{3,50}$/', $form['username'])) {
        $errors[] = 'Username 3-50 karakter, hanya huruf, angka, titik, dan garis bawah.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
        $stmt->execute([$form['username']]);
        if ($stmt->fetchColumn() > 0) $errors[] = 'Username sudah digunakan.';
    }
    if ($form['nama_lengkap'] === '') $errors[] = 'Nama lengkap wajib diisi.';
    if (!in_array($form['level'], ['admin', 'user'], true)) $errors[] = 'Level tidak valid.';
    if (!isset($statusOptions[$form['status_cd']])) $errors[] = 'Status tidak valid.';
    // Divalidasi di sini karena MySQL tidak strict: nilai enum yang salah akan tersimpan sebagai ''.
    if ($form['sub_level'] !== '' && !isset($subLevelOptions[$form['sub_level']])) $errors[] = 'Sub level tidak valid.';
    if ($form['id_wilayah'] !== '' && !in_array((int) $form['id_wilayah'], array_map('intval', array_column($wilayahList, 'id_wilayah')), true)) {
        $errors[] = 'Wilayah yang dipilih tidak aktif atau tidak ditemukan.';
    }
    if (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';
    if ($password !== $passwordConfirm) $errors[] = 'Konfirmasi password tidak sama.';

    if (!$errors) {
        try {
            $fotoProfil = uploadFotoProfil('foto_profil');
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO users (username, password, nama_lengkap, level, sub_level, id_wilayah, status_cd, foto_profil) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $form['username'], password_hash($password, PASSWORD_DEFAULT),
            $form['nama_lengkap'], $form['level'], $form['sub_level'] ?: null, $form['id_wilayah'] ?: null,
            $form['status_cd'], $fotoProfil,
        ]);

        $_SESSION['flash_success'] = 'Pengguna berhasil ditambahkan.';
        header('Location: list.php');
        exit;
    }
}

$pageTitle = 'Tambah Pengguna';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Tambah Pengguna</h4>
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
        <form method="post" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control" maxlength="50" pattern="[A-Za-z0-9_.]{3,50}" value="<?= htmlspecialchars($form['username']) ?>" required>
                    <div class="form-text">3-50 karakter: huruf, angka, titik, atau garis bawah.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="nama_lengkap" class="form-control" maxlength="100" value="<?= htmlspecialchars($form['nama_lengkap']) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" minlength="6" autocomplete="new-password" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                    <input type="password" name="password_confirm" class="form-control" minlength="6" autocomplete="new-password" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Level <span class="text-danger">*</span></label>
                    <select name="level" class="form-select" required>
                        <option value="user" <?= $form['level'] === 'user' ? 'selected' : '' ?>>User</option>
                        <option value="admin" <?= $form['level'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Sub Level</label>
                    <select name="sub_level" class="form-select">
                        <option value="">-- Pilih Sub Level --</option>
                        <?php foreach ($subLevelOptions as $value => $label): ?>
                            <option value="<?= $value ?>" <?= $form['sub_level'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Wilayah (RT/RW)</label>
                    <select name="id_wilayah" class="form-select">
                        <option value="">-- Pilih Wilayah --</option>
                        <?php foreach ($wilayahList as $w): ?>
                            <option value="<?= (int) $w['id_wilayah'] ?>" <?= (string) $form['id_wilayah'] === (string) $w['id_wilayah'] ? 'selected' : '' ?>>
                                RT <?= htmlspecialchars($w['rt']) ?>/RW <?= htmlspecialchars($w['rw']) ?> -
                                <?= htmlspecialchars($w['kelurahan']) ?>, <?= htmlspecialchars($w['kecamatan']) ?>,
                                <?= htmlspecialchars($w['Kabupaten']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status_cd" class="form-select" required>
                        <?php foreach ($statusOptions as $value => $label): ?>
                            <option value="<?= $value ?>" <?= $form['status_cd'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Foto Profil</label>
                    <input type="file" name="foto_profil" class="form-control" accept=".jpg,.jpeg,.png">
                    <div class="form-text">Opsional. jpg/png, maksimal 2MB.</div>
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
