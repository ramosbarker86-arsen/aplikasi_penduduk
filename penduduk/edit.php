<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAkses('data_penduduk');
requireAdmin();

$pdo = getDB();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM penduduk WHERE id = ? AND status_cd <> \'nullified\'');
$stmt->execute([$id]);
$existing = $stmt->fetch();

if (!$existing) {
    $_SESSION['flash_error'] = 'Data penduduk tidak ditemukan.';
    header('Location: list.php');
    exit;
}

$masterPekerjaan = $pdo->query("SELECT nama_pekerjaan FROM master_pekerjaan WHERE status_cd = 1 ORDER BY urutan ASC, nama_pekerjaan ASC")->fetchAll(PDO::FETCH_COLUMN);
$masterPendidikan = $pdo->query("SELECT nama_pendidikan FROM master_pendidikan WHERE status_cd = 1 ORDER BY urutan ASC, level_pendidikan ASC")->fetchAll(PDO::FETCH_COLUMN);
// Hanya wilayah aktif, kecuali wilayah yang sedang dipakai penduduk ini supaya tidak hilang dari pilihan.
$stmtWilayah = $pdo->prepare("SELECT id_wilayah, propinsi, Kabupaten, kecamatan, kelurahan, rw, rt FROM wilayah
                              WHERE status_cd = 'active' OR id_wilayah = ?
                              ORDER BY kelurahan ASC, rw ASC, rt ASC");
$stmtWilayah->execute([(int) $existing['id_wilayah']]);
$wilayahList = $stmtWilayah->fetchAll();

$agamaOptions = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Khonghucu', 'Lainnya'];
$statusKawinOptions = ['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'];
$statusKeluargaOptions = ['Kepala Keluarga', 'Istri', 'Anak', 'Famili Lain'];
$kategoriOptions = ['Tetap', 'Pendatang', 'Kontrak', 'Lainnya'];

$errors = [];
$form = [
    'nik' => $existing['nik'], 'no_kk' => $existing['no_kk'], 'nama_lengkap' => $existing['nama_lengkap'],
    'tempat_lahir' => $existing['tempat_lahir'], 'tanggal_lahir' => $existing['tanggal_lahir'],
    'jenis_kelamin' => $existing['jenis_kelamin'], 'agama' => $existing['agama'],
    'pendidikan' => $existing['pendidikan'], 'pekerjaan' => $existing['pekerjaan'],
    'status_kawin' => $existing['status_kawin'], 'status_keluarga' => $existing['status_keluarga'],
    'is_ahli_waris' => (int) $existing['is_ahli_waris'], 'alamat' => $existing['alamat'],
    'id_wilayah' => $existing['id_wilayah'], 'no_telepon' => $existing['no_telepon'], 'email' => (string) $existing['email'],
    'nama_ayah' => $existing['nama_ayah'], 'nama_ibu' => $existing['nama_ibu'], 'kategori' => $existing['kategori'],
    'status_cd' => $existing['status_cd'],
];
$statusOptions = ['active' => 'Aktif', 'inactive' => 'Tidak Aktif', 'nullified' => 'Nullified (Dihapus)'];

function uploadFotoEdit(string $field, string $subdir): ?string
{
    if (empty($_FILES[$field]['name'])) {
        return null;
    }

    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Gagal mengunggah file ' . $field . '.');
    }

    $allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        throw new RuntimeException('Format file ' . $field . ' harus jpg, jpeg, png, atau pdf.');
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Ukuran file ' . $field . ' maksimal 2MB.');
    }

    $destDir = __DIR__ . '/../uploads/' . $subdir . '/';
    $newName = $subdir . '_' . uniqid() . '.' . $ext;
    move_uploaded_file($file['tmp_name'], $destDir . $newName);

    return 'uploads/' . $subdir . '/' . $newName;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $key => $default) {
        if ($key === 'is_ahli_waris') {
            $form[$key] = isset($_POST['is_ahli_waris']) ? 1 : 0;
            continue;
        }
        $form[$key] = trim($_POST[$key] ?? '');
    }

    if ($form['nik'] === '') $errors[] = 'NIK wajib diisi.';
    if (!isset($statusOptions[$form['status_cd']])) $errors[] = 'Status tidak valid.';
    if ($form['no_kk'] === '') $errors[] = 'No. KK wajib diisi.';
    if ($form['id_wilayah'] !== '' && !in_array((int) $form['id_wilayah'], array_map('intval', array_column($wilayahList, 'id_wilayah')), true)) {
        $errors[] = 'Wilayah yang dipilih tidak aktif atau tidak ditemukan.';
    }
    if ($form['nama_lengkap'] === '') $errors[] = 'Nama lengkap wajib diisi.';
    if ($form['tanggal_lahir'] === '') $errors[] = 'Tanggal lahir wajib diisi.';
    if ($form['agama'] === '') $errors[] = 'Agama wajib dipilih.';
    if ($form['status_kawin'] === '') $errors[] = 'Status kawin wajib dipilih.';
    if ($form['alamat'] === '') $errors[] = 'Alamat wajib diisi.';
    if ($form['email'] !== '' && (strlen($form['email']) > 100 || !filter_var($form['email'], FILTER_VALIDATE_EMAIL))) {
        $errors[] = 'Format email tidak valid.';
    }

    if (!$errors && $form['nik'] !== '') {
        $cek = $pdo->prepare('SELECT COUNT(*) FROM penduduk WHERE nik = ? AND status_cd <> \'nullified\' AND id != ?');
        $cek->execute([$form['nik'], $id]);
        if ((int) $cek->fetchColumn() > 0) {
            $errors[] = 'NIK sudah terdaftar untuk penduduk lain.';
        }
    }

    $fotoKtpPath = $existing['foto_ktp'];
    $fotoKkPath = $existing['foto_kk'];

    if (!$errors) {
        try {
            $newKtp = uploadFotoEdit('foto_ktp', 'ktp');
            $newKk = uploadFotoEdit('foto_kk', 'kk');
            if ($newKtp) $fotoKtpPath = $newKtp;
            if ($newKk) $fotoKkPath = $newKk;
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (!$errors) {
        $umur = (int) floor((time() - strtotime($form['tanggal_lahir'])) / (365.25 * 24 * 3600));

        $stmt = $pdo->prepare("UPDATE penduduk SET
            nik = ?, no_kk = ?, nama_lengkap = ?, tempat_lahir = ?, tanggal_lahir = ?, jenis_kelamin = ?,
            agama = ?, pendidikan = ?, pekerjaan = ?, status_kawin = ?, status_keluarga = ?, is_ahli_waris = ?,
            alamat = ?, id_wilayah = ?, no_telepon = ?, email = ?, nama_ayah = ?, nama_ibu = ?, umur = ?, kategori = ?,
            foto_ktp = ?, foto_kk = ?, status_cd = ?
            WHERE id = ?");
        $stmt->execute([
            $form['nik'], $form['no_kk'], $form['nama_lengkap'], $form['tempat_lahir'], $form['tanggal_lahir'],
            $form['jenis_kelamin'], $form['agama'], $form['pendidikan'] ?: null, $form['pekerjaan'] ?: null,
            $form['status_kawin'], $form['status_keluarga'] ?: null, $form['is_ahli_waris'], $form['alamat'],
            $form['id_wilayah'] ?: null, $form['no_telepon'] ?: null, $form['email'] ?: null, $form['nama_ayah'] ?: null,
            $form['nama_ibu'] ?: null, $umur, $form['kategori'] ?: null, $fotoKtpPath, $fotoKkPath, $form['status_cd'], $id,
        ]);

        $_SESSION['flash_success'] = 'Data penduduk berhasil diperbarui.';
        header('Location: list.php');
        exit;
    }
}

$pageTitle = 'Edit Penduduk';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Edit Penduduk</h4>
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
        <form method="post" enctype="multipart/form-data">
            <div class="form-section">
                <div class="form-section-title">
                    <span class="badge-icon"><i class="bi bi-person"></i></span> Data Identitas
                </div>
                <div class="row g-3 mb-2">
                    <div class="col-md-4">
                        <label class="form-label">NIK <span class="text-danger">*</span></label>
                        <input type="text" name="nik" class="form-control" maxlength="20" value="<?= htmlspecialchars($form['nik']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">No. KK <span class="text-danger">*</span></label>
                        <input type="text" name="no_kk" class="form-control" maxlength="16" value="<?= htmlspecialchars($form['no_kk']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($form['nama_lengkap']) ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" class="form-control" value="<?= htmlspecialchars($form['tempat_lahir']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_lahir" class="form-control" value="<?= htmlspecialchars($form['tanggal_lahir']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label d-block">Jenis Kelamin <span class="text-danger">*</span></label>
                        <div class="form-check form-check-inline mt-2">
                            <input class="form-check-input" type="radio" name="jenis_kelamin" value="L" id="jkL" <?= $form['jenis_kelamin'] === 'L' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="jkL">Laki-laki</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="jenis_kelamin" value="P" id="jkP" <?= $form['jenis_kelamin'] === 'P' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="jkP">Perempuan</label>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Agama <span class="text-danger">*</span></label>
                        <select name="agama" class="form-select" required>
                            <option value="">-- Pilih Agama --</option>
                            <?php foreach ($agamaOptions as $opt): ?>
                                <option value="<?= $opt ?>" <?= $form['agama'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status Perkawinan <span class="text-danger">*</span></label>
                        <select name="status_kawin" class="form-select" required>
                            <option value="">-- Pilih Status --</option>
                            <?php foreach ($statusKawinOptions as $opt): ?>
                                <option value="<?= $opt ?>" <?= $form['status_kawin'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status dalam Keluarga</label>
                        <select name="status_keluarga" class="form-select">
                            <option value="">-- Pilih Status --</option>
                            <?php foreach ($statusKeluargaOptions as $opt): ?>
                                <option value="<?= $opt ?>" <?= $form['status_keluarga'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-title">
                    <span class="badge-icon"><i class="bi bi-mortarboard"></i></span> Pendidikan &amp; Pekerjaan
                </div>
                <div class="row g-3 mb-2">
                    <div class="col-md-4">
                        <label class="form-label">Pendidikan Terakhir</label>
                        <select name="pendidikan" class="form-select">
                            <option value="">-- Pilih Pendidikan --</option>
                            <?php foreach ($masterPendidikan as $opt): ?>
                                <option value="<?= htmlspecialchars($opt) ?>" <?= $form['pendidikan'] === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Pekerjaan</label>
                        <select name="pekerjaan" class="form-select">
                            <option value="">-- Pilih Pekerjaan --</option>
                            <?php foreach ($masterPekerjaan as $opt): ?>
                                <option value="<?= htmlspecialchars($opt) ?>" <?= $form['pekerjaan'] === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kategori</label>
                        <select name="kategori" class="form-select">
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($kategoriOptions as $opt): ?>
                                <option value="<?= $opt ?>" <?= $form['kategori'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status_cd" class="form-select" required>
                            <?php foreach ($statusOptions as $value => $label): ?>
                                <option value="<?= $value ?>" <?= $form['status_cd'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Nullified = data dianggap dihapus dan tidak tampil lagi di daftar.</div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-title">
                    <span class="badge-icon"><i class="bi bi-geo-alt"></i></span> Alamat &amp; Wilayah
                </div>
                <div class="row g-3 mb-2">
                    <div class="col-12">
                        <label class="form-label">Alamat <span class="text-danger">*</span></label>
                        <textarea name="alamat" class="form-control" rows="2" required><?= htmlspecialchars($form['alamat']) ?></textarea>
                    </div>

                    <div class="col-md-8">
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
                    <div class="col-md-4">
                        <label class="form-label">No. Telepon</label>
                        <input type="text" name="no_telepon" class="form-control" maxlength="15" value="<?= htmlspecialchars($form['no_telepon']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" maxlength="100" placeholder="nama@contoh.com" value="<?= htmlspecialchars($form['email']) ?>">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-title">
                    <span class="badge-icon"><i class="bi bi-people"></i></span> Data Keluarga
                </div>
                <div class="row g-3 mb-2">
                    <div class="col-md-6">
                        <label class="form-label">Nama Ayah</label>
                        <input type="text" name="nama_ayah" class="form-control" value="<?= htmlspecialchars($form['nama_ayah']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nama Ibu</label>
                        <input type="text" name="nama_ibu" class="form-control" value="<?= htmlspecialchars($form['nama_ibu']) ?>">
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_ahli_waris" id="ahliWaris" value="1" <?= $form['is_ahli_waris'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ahliWaris">Ahli Waris</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-title">
                    <span class="badge-icon"><i class="bi bi-file-earmark-text"></i></span> Dokumen
                </div>
                <div class="row g-3 mb-2">
                    <div class="col-md-6">
                        <label class="form-label">Foto KTP <span class="text-muted small">(kosongkan jika tidak diganti)</span></label>
                        <?php if ($existing['foto_ktp']): ?>
                            <div class="mb-1"><a href="../<?= htmlspecialchars($existing['foto_ktp']) ?>" target="_blank">Lihat file saat ini</a></div>
                        <?php endif; ?>
                        <input type="file" name="foto_ktp" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Foto KK <span class="text-muted small">(kosongkan jika tidak diganti)</span></label>
                        <?php if ($existing['foto_kk']): ?>
                            <div class="mb-1"><a href="../<?= htmlspecialchars($existing['foto_kk']) ?>" target="_blank">Lihat file saat ini</a></div>
                        <?php endif; ?>
                        <input type="file" name="foto_kk" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
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
