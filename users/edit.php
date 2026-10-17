<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/upload_profil.php';
requireAdmin();

$pdo = getDB();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT id, username, nama_lengkap, level, status_cd, foto_profil FROM users WHERE id = ?');
$stmt->execute([$id]);
$existing = $stmt->fetch();

if (!$existing) {
    $_SESSION['flash_error'] = 'Data pengguna tidak ditemukan.';
    header('Location: list.php');
    exit;
}

$isSelf = (int) $existing['id'] === (int) currentUser()['id'];

$errors = [];
$form = [
    'username' => $existing['username'], 'nama_lengkap' => $existing['nama_lengkap'],
    'level' => $existing['level'], 'status_cd' => $existing['status_cd'],
];
$statusOptions = ['active' => 'Aktif', 'inactive' => 'Tidak Aktif'];

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
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? AND id != ?');
        $stmt->execute([$form['username'], $id]);
        if ($stmt->fetchColumn() > 0) $errors[] = 'Username sudah digunakan.';
    }
    if ($form['nama_lengkap'] === '') $errors[] = 'Nama lengkap wajib diisi.';
    if (!in_array($form['level'], ['admin', 'user'], true)) $errors[] = 'Level tidak valid.';
    if (!isset($statusOptions[$form['status_cd']])) $errors[] = 'Status tidak valid.';

    // Admin tidak boleh menurunkan level atau menonaktifkan akunnya sendiri,
    // supaya tidak terkunci dari halaman pengguna.
    if ($isSelf && $form['level'] !== 'admin') $errors[] = 'Anda tidak bisa mengubah level akun Anda sendiri.';
    if ($isSelf && $form['status_cd'] !== 'active') $errors[] = 'Anda tidak bisa menonaktifkan akun Anda sendiri.';

    // Password opsional: kosongkan jika tidak ingin diganti.
    if ($password !== '' || $passwordConfirm !== '') {
        if (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';
        if ($password !== $passwordConfirm) $errors[] = 'Konfirmasi password tidak sama.';
    }

    $fotoProfil = $existing['foto_profil'];
    if (!$errors) {
        try {
            $newFoto = uploadFotoProfil('foto_profil');
            if ($newFoto) $fotoProfil = $newFoto;
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (!$errors) {
        $fields = 'username = ?, nama_lengkap = ?, level = ?, status_cd = ?, foto_profil = ?';
        $params = [$form['username'], $form['nama_lengkap'], $form['level'], $form['status_cd'], $fotoProfil];
        if ($password !== '') {
            $fields .= ', password = ?';
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }
        $params[] = $id;

        $stmt = $pdo->prepare("UPDATE users SET $fields WHERE id = ?");
        $stmt->execute($params);

        // Hapus foto lama yang sudah diganti.
        if ($fotoProfil !== $existing['foto_profil'] && $existing['foto_profil']) {
            $oldPath = __DIR__ . '/../' . $existing['foto_profil'];
            if (is_file($oldPath)) unlink($oldPath);
        }

        if ($isSelf) {
            $_SESSION['username'] = $form['username'];
            $_SESSION['nama_lengkap'] = $form['nama_lengkap'];
        }

        $_SESSION['flash_success'] = 'Data pengguna berhasil diperbarui.';
        header('Location: list.php');
        exit;
    }
}

$pageTitle = 'Edit Pengguna';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Edit Pengguna</h4>
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
                    <label class="form-label">Password Baru</label>
                    <input type="password" name="password" class="form-control" minlength="6" autocomplete="new-password">
                    <div class="form-text">Kosongkan jika tidak ingin mengganti password.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Konfirmasi Password Baru</label>
                    <input type="password" name="password_confirm" class="form-control" minlength="6" autocomplete="new-password">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Level <span class="text-danger">*</span></label>
                    <select name="level" class="form-select" required>
                        <option value="user" <?= $form['level'] === 'user' ? 'selected' : '' ?>>User</option>
                        <option value="admin" <?= $form['level'] === 'admin' ? 'selected' : '' ?>>Admin</option>
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
                <?php if ($isSelf): ?>
                <div class="col-12">
                    <div class="form-text text-warning"><i class="bi bi-info-circle"></i> Ini akun Anda sendiri: level harus tetap Admin dan status tetap Aktif.</div>
                </div>
                <?php endif; ?>

                <div class="col-md-6">
                    <label class="form-label">Foto Profil</label>
                    <?php if ($existing['foto_profil']): ?>
                        <div class="mb-2">
                            <img src="../<?= htmlspecialchars($existing['foto_profil']) ?>" alt="" class="rounded-circle" width="64" height="64" style="object-fit:cover">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="foto_profil" class="form-control" accept=".jpg,.jpeg,.png">
                    <div class="form-text">Opsional. jpg/png, maksimal 2MB. Kosongkan jika tidak ingin mengganti.</div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Perubahan</button>
                <a href="list.php" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
