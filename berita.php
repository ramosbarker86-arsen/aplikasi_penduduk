<?php
// Halaman publik: daftar berita yang sudah terbit. Tidak perlu login.
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/berita.php';
require_once __DIR__ . '/includes/surat.php';

$pdo = getDB();

$search = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 9;
$offset = ($page - 1) * $perPage;

$where = "WHERE b.status = 'publish'";
$params = [];
if ($search !== '') {
    $where .= ' AND b.judul LIKE :q';
    $params[':q'] = '%' . $search . '%';
}
$kategori = isset(KATEGORI_BERITA[$_GET['kategori'] ?? '']) ? $_GET['kategori'] : '';
if ($kategori !== '') {
    $where .= ' AND b.kategori = :kategori';
    $params[':kategori'] = $kategori;
}

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM berita b $where");
$stmtCount->execute($params);
$totalRows = (int) $stmtCount->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$stmt = $pdo->prepare("SELECT b.judul, b.slug, (SELECT f.file FROM berita_foto f WHERE f.berita_id = b.id ORDER BY f.urutan, f.id LIMIT 1) AS foto, b.konten, b.published_at
                       FROM berita b
                       $where
                       ORDER BY b.published_at DESC, b.id DESC
                       LIMIT :limit OFFSET :offset");
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$pageTitle = 'Berita';
require __DIR__ . '/includes/publik_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h3 class="mb-0"><?= $kategori !== '' ? htmlspecialchars(KATEGORI_BERITA[$kategori]) . ' Terbaru' : 'Berita Terbaru' ?></h3>
    <form method="get" class="d-flex gap-2">
        <input type="text" name="q" class="form-control" placeholder="Cari berita..." value="<?= htmlspecialchars($search) ?>">
        <?php if ($kategori !== ''): ?><input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori) ?>"><?php endif; ?>
        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
    </form>
</div>

<?php if (empty($rows)): ?>
    <div class="text-center text-muted py-5">
        <i class="bi bi-newspaper fs-1 d-block mb-2"></i>
        Belum ada berita<?= $search !== '' ? ' yang cocok dengan pencarian' : '' ?>.
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($rows as $row): $link = 'berita_baca.php?slug=' . urlencode($row['slug']); ?>
            <div class="col-md-6 col-lg-4 muncul">
                <a href="<?= $link ?>" class="card berita-card h-100 text-decoration-none text-reset">
                    <?php if ($row['foto']): ?>
                        <img src="<?= htmlspecialchars($row['foto']) ?>" class="card-img-top" alt="<?= htmlspecialchars($row['judul']) ?>">
                    <?php else: ?>
                        <div class="placeholder-foto card-img-top d-flex align-items-center justify-content-center"><i class="bi bi-image"></i></div>
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="small text-muted mb-1"><i class="bi bi-calendar3"></i> <?= htmlspecialchars(tanggalIndonesia($row['published_at'])) ?></div>
                        <h5 class="card-title"><?= htmlspecialchars($row['judul']) ?></h5>
                        <p class="card-text text-muted small mb-0"><?= htmlspecialchars(ringkasanBerita($row['konten'])) ?></p>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $p ?>&q=<?= urlencode($search) ?>&kategori=<?= urlencode($kategori) ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/publik_footer.php'; ?>
