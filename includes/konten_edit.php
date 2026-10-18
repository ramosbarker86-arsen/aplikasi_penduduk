<?php
// Halaman edit konten (berita/edit.php, kegiatan/edit.php).
// Pemanggil sudah memuat database/auth/berita.php, memeriksa akses, dan mengisi $modul (MODUL_BERITA / MODUL_KEGIATAN).
$pdo = getDB();

$id = (int) ($_GET['id'] ?? 0);
// Hanya konten milik modul ini, dan untuk kegiatan hanya yang ada dalam lingkup wilayah pengguna.
$stmt = $pdo->prepare('SELECT * FROM berita b WHERE b.id = ? AND ' . kondisiModulKonten($pdo, $modul));
$stmt->execute([$id]);
$existing = $stmt->fetch();

if (!$existing) {
    $_SESSION['flash_error'] = $modul['nama'] . ' tidak ditemukan.';
    header('Location: list.php');
    exit;
}

$fotoLama = daftarFotoBerita($pdo, $id);
$wilayahList = $modul['wilayah'] ? daftarWilayahKonten($pdo, $existing['id_wilayah'] ? (int) $existing['id_wilayah'] : null) : [];

$errors = [];
$form = [
    'judul' => $existing['judul'], 'konten' => $existing['konten'], 'kategori' => $existing['kategori'],
    'id_wilayah' => (string) $existing['id_wilayah'], 'status' => $existing['status'],
    'keterangan_lama' => array_column($fotoLama, 'keterangan', 'id'), 'hapus_foto' => [],
    'foto_utama' => $fotoLama ? 'lama-' . $fotoLama[0]['id'] : '',
    'published_at' => $existing['published_at'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['judul'] = trim($_POST['judul'] ?? '');
    $form['konten'] = saringKontenBerita($_POST['konten'] ?? '');
    $form['status'] = ($_POST['status'] ?? '') === 'publish' ? 'publish' : 'draft';
    $form['kategori'] = isset($modul['kategori'][$_POST['kategori'] ?? '']) ? $_POST['kategori'] : $existing['kategori'];
    $form['id_wilayah'] = trim($_POST['id_wilayah'] ?? '');
    $form['keterangan_lama'] = array_replace($form['keterangan_lama'], (array) ($_POST['keterangan_lama'] ?? []));
    $form['hapus_foto'] = (array) ($_POST['hapus_foto'] ?? []);
    $form['foto_utama'] = (string) ($_POST['foto_utama'] ?? '');

    // Tanggal terbit bisa diubah manual (datetime-local: 2026-10-18T09:30 atau 2026-10-18T09:30:15).
    $inputTerbit = trim($_POST['published_at'] ?? '');
    $form['published_at'] = null;
    if ($inputTerbit !== '') {
        $dt = DateTime::createFromFormat('Y-m-d\TH:i:s', $inputTerbit) ?: DateTime::createFromFormat('Y-m-d\TH:i', $inputTerbit);
        if ($dt && in_array($inputTerbit, [$dt->format('Y-m-d\TH:i:s'), $dt->format('Y-m-d\TH:i')], true)) {
            $form['published_at'] = $dt->format('Y-m-d H:i:s');
        } else {
            $errors[] = 'Tanggal terbit tidak valid.';
            $form['published_at'] = $existing['published_at'];
        }
    }

    if ($form['judul'] === '') $errors[] = 'Judul ' . strtolower($modul['nama']) . ' wajib diisi.';
    if (kontenBeritaKosong($form['konten'])) $errors[] = 'Konten ' . strtolower($modul['nama']) . ' wajib diisi.';
    if ($modul['wilayah']) {
        if ($form['id_wilayah'] === '') {
            $errors[] = 'Wilayah (RT/RW) wajib dipilih.';
        } elseif (!in_array((int) $form['id_wilayah'], array_map('intval', array_column($wilayahList, 'id_wilayah')), true)) {
            $errors[] = 'Wilayah yang dipilih tidak aktif atau di luar wilayah Anda.';
        }
    }

    $fotoBaru = [];
    try {
        $fotoBaru = periksaUploadFotoBerita('foto_baru');
    } catch (RuntimeException $e) {
        $errors[] = $e->getMessage();
    }
    $sisaLama = count(array_filter($fotoLama, fn($f) => empty($form['hapus_foto'][$f['id']])));
    if ($sisaLama + count($fotoBaru) > MAKS_FOTO_BERITA) $errors[] = 'Maksimal ' . MAKS_FOTO_BERITA . ' gambar.';
    if ($errors && $fotoBaru) $errors[] = 'Silakan pilih ulang gambar baru yang akan diunggah.';

    if (!$errors) {
        // Slug ikut judul selama belum pernah terbit; setelah terbit tetap, supaya link yang sudah dibagikan tidak putus.
        $slug = $existing['published_at'] ? $existing['slug'] : buatSlugBerita($pdo, $form['judul'], $id);
        // Tanggal terbit dari form; jika dikosongkan dan diterbitkan, pakai tanggal lama atau waktu sekarang.
        $publishedAt = $form['published_at']
            ?: ($form['status'] === 'publish' ? ($existing['published_at'] ?: date('Y-m-d H:i:s')) : null);
        $idWilayah = $modul['wilayah'] ? (int) $form['id_wilayah'] : $existing['id_wilayah'];

        $fileBaru = [];
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('UPDATE berita SET judul = ?, slug = ?, kategori = ?, id_wilayah = ?, konten = ?, status = ?, published_at = ? WHERE id = ?');
            $stmt->execute([$form['judul'], $slug, $form['kategori'], $idWilayah, $form['konten'], $form['status'], $publishedAt, $id]);
            [$hapusFile, $fileBaru] = simpanFotoBerita($pdo, $id, $fotoLama, $fotoBaru, $_POST);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            array_map('hapusFileBerita', $fileBaru);
            throw $e;
        }
        array_map('hapusFileBerita', $hapusFile);

        $_SESSION['flash_success'] = $modul['nama'] . ' berhasil diperbarui.';
        header('Location: list.php');
        exit;
    }
}

$tombolSimpan = 'Simpan Perubahan';
$pageTitle = 'Edit ' . $modul['nama'];
require_once __DIR__ . '/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Edit <?= htmlspecialchars($modul['nama']) ?></h4>
    <div>
        <?php if ($existing['status'] === 'publish'): ?>
            <a href="../berita_baca.php?slug=<?= urlencode($existing['slug']) ?>" target="_blank" class="btn btn-sm btn-outline-dark"><i class="bi bi-box-arrow-up-right"></i> Lihat</a>
        <?php endif; ?>
        <a href="list.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>
</div>

<?php require __DIR__ . '/berita_form.php'; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
