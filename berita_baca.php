<?php
// Halaman publik: baca satu berita yang sudah terbit. Tidak perlu login.
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/berita.php';
require_once __DIR__ . '/includes/surat.php';

$pdo = getDB();

$stmt = $pdo->prepare("SELECT b.*, u.nama_lengkap AS penulis, w.rt, w.rw, w.kelurahan
                       FROM berita b
                       LEFT JOIN users u ON u.id = b.created_by
                       LEFT JOIN wilayah w ON w.id_wilayah = b.id_wilayah
                       WHERE b.slug = ? AND b.status = 'publish'
                       LIMIT 1");
$stmt->execute([(string) ($_GET['slug'] ?? '')]);
$berita = $stmt->fetch();

if (!$berita) {
    http_response_code(404);
    $pageTitle = 'Berita tidak ditemukan';
    require __DIR__ . '/includes/publik_header.php';
    echo '<div class="text-center text-muted py-5"><i class="bi bi-emoji-frown fs-1 d-block mb-2"></i>'
       . 'Berita tidak ditemukan atau belum diterbitkan.<div class="mt-3"><a href="berita.php" class="btn btn-primary">Lihat berita lain</a></div></div>';
    require __DIR__ . '/includes/publik_footer.php';
    exit;
}

// Hitung jumlah dibaca (untuk "Berita Terpopuler" di halaman Home). Kunjungan pengguna yang login tidak dihitung.
if (empty($_SESSION['user_id'])) {
    $pdo->prepare('UPDATE berita SET dilihat = dilihat + 1 WHERE id = ?')->execute([$berita['id']]);
}

// Foto pertama = foto utama (di atas konten), sisanya tampil sebagai galeri di bawah konten.
$galeri = daftarFotoBerita($pdo, (int) $berita['id']);
$fotoUtama = array_shift($galeri);

$lainnya = $pdo->prepare("SELECT judul, slug, published_at FROM berita
                          WHERE status = 'publish' AND id <> ?
                          ORDER BY published_at DESC LIMIT 3");
$lainnya->execute([$berita['id']]);
$lainnya = $lainnya->fetchAll();

$pageTitle = $berita['judul'];
require __DIR__ . '/includes/publik_header.php';
?>

<article class="berita-baca mx-auto">
    <a href="berita.php" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Semua berita</a>
    <h1 class="mt-2 mb-2"><?= htmlspecialchars($berita['judul']) ?></h1>
    <div class="text-muted small mb-3">
        <i class="bi bi-calendar3"></i> <?= htmlspecialchars(tanggalIndonesia($berita['published_at'])) ?>
        <?php if ($berita['penulis']): ?> &middot; <i class="bi bi-person"></i> <?= htmlspecialchars($berita['penulis']) ?><?php endif; ?>
        <?php if ($berita['rt'] !== null): ?> &middot; <i class="bi bi-geo-alt"></i> RT <?= htmlspecialchars($berita['rt']) ?>/RW <?= htmlspecialchars($berita['rw']) ?> <?= htmlspecialchars($berita['kelurahan']) ?><?php endif; ?>
    </div>

    <?php if ($fotoUtama): ?>
        <figure class="mb-4">
            <a href="<?= htmlspecialchars($fotoUtama['file']) ?>" target="_blank">
                <img src="<?= htmlspecialchars($fotoUtama['file']) ?>" class="img-fluid rounded w-100 foto-utama"
                     alt="<?= htmlspecialchars($fotoUtama['keterangan'] ?: $berita['judul']) ?>">
            </a>
            <?php if ($fotoUtama['keterangan']): ?>
                <figcaption class="keterangan-foto"><?= nl2br(htmlspecialchars($fotoUtama['keterangan'])) ?></figcaption>
            <?php endif; ?>
        </figure>
    <?php endif; ?>

    <div class="konten-berita">
        <?= saringKontenBerita($berita['konten']) ?>
    </div>

    <?php if ($galeri): ?>
        <h5 class="mt-5 mb-3"><i class="bi bi-images"></i> Galeri Foto</h5>
        <div class="row g-3">
            <?php foreach ($galeri as $f): ?>
                <div class="col-sm-6">
                    <figure class="mb-0">
                        <a href="<?= htmlspecialchars($f['file']) ?>" target="_blank">
                            <img src="<?= htmlspecialchars($f['file']) ?>" class="img-fluid rounded w-100 foto-galeri"
                                 alt="<?= htmlspecialchars($f['keterangan'] ?: $berita['judul']) ?>">
                        </a>
                        <?php if ($f['keterangan']): ?>
                            <figcaption class="keterangan-foto"><?= nl2br(htmlspecialchars($f['keterangan'])) ?></figcaption>
                        <?php endif; ?>
                    </figure>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($lainnya): ?>
        <hr class="my-5">
        <h5 class="mb-3">Berita Lainnya</h5>
        <ul class="list-unstyled">
            <?php foreach ($lainnya as $l): ?>
                <li class="mb-2">
                    <a href="berita_baca.php?slug=<?= urlencode($l['slug']) ?>" class="text-decoration-none"><?= htmlspecialchars($l['judul']) ?></a>
                    <span class="small text-muted">&middot; <?= htmlspecialchars(tanggalIndonesia($l['published_at'])) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</article>

<?php require __DIR__ . '/includes/publik_footer.php'; ?>
