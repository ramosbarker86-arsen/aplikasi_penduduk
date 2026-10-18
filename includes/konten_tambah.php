<?php
// Halaman tambah konten (berita/tambah.php, kegiatan/tambah.php).
// Pemanggil sudah memuat database/auth/berita.php, memeriksa akses, dan mengisi $modul (MODUL_BERITA / MODUL_KEGIATAN).
$pdo = getDB();

$errors = [];
$form = [
    'judul' => '', 'konten' => '', 'kategori' => array_key_first($modul['kategori']), 'id_wilayah' => '', 'status' => 'draft',
    'keterangan_lama' => [], 'hapus_foto' => [], 'foto_utama' => '',
];
$wilayahList = $modul['wilayah'] ? daftarWilayahKonten($pdo) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['judul'] = trim($_POST['judul'] ?? '');
    $form['konten'] = saringKontenBerita($_POST['konten'] ?? '');
    $form['status'] = ($_POST['status'] ?? '') === 'publish' ? 'publish' : 'draft';
    $form['kategori'] = isset($modul['kategori'][$_POST['kategori'] ?? '']) ? $_POST['kategori'] : array_key_first($modul['kategori']);
    $form['id_wilayah'] = trim($_POST['id_wilayah'] ?? '');

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
    if (count($fotoBaru) > MAKS_FOTO_BERITA) $errors[] = 'Maksimal ' . MAKS_FOTO_BERITA . ' gambar.';
    if ($errors && $fotoBaru) $errors[] = 'Silakan pilih ulang gambar yang akan diunggah.';

    if (!$errors) {
        $fileBaru = [];
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO berita (judul, slug, kategori, id_wilayah, konten, status, created_by, published_at) VALUES (?,?,?,?,?,?,?,?)');
            $stmt->execute([
                $form['judul'], buatSlugBerita($pdo, $form['judul']), $form['kategori'],
                $modul['wilayah'] ? (int) $form['id_wilayah'] : null, $form['konten'], $form['status'],
                currentUser()['id'], $form['status'] === 'publish' ? date('Y-m-d H:i:s') : null,
            ]);
            $beritaId = (int) $pdo->lastInsertId();
            [, $fileBaru] = simpanFotoBerita($pdo, $beritaId, [], $fotoBaru, $_POST);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            array_map('hapusFileBerita', $fileBaru);
            throw $e;
        }

        $_SESSION['flash_success'] = $modul['nama'] . ($form['status'] === 'publish' ? ' berhasil diterbitkan.' : ' disimpan sebagai draft.');
        header('Location: list.php');
        exit;
    }
}

$fotoLama = [];
$tombolSimpan = 'Simpan';
$pageTitle = 'Tambah ' . $modul['nama'];
require_once __DIR__ . '/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Tambah <?= htmlspecialchars($modul['nama']) ?></h4>
    <a href="list.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<?php require __DIR__ . '/berita_form.php'; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
