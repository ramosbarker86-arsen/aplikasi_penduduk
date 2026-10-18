<?php
// Halaman daftar konten (berita/list.php, kegiatan/list.php).
// Pemanggil sudah memuat database/auth/berita.php, memeriksa akses, dan mengisi $modul (MODUL_BERITA / MODUL_KEGIATAN).
require_once __DIR__ . '/surat.php';

$pdo = getDB();
$namaKecil = strtolower($modul['nama']);

$search = trim($_GET['q'] ?? '');
$filterStatus = in_array($_GET['status'] ?? '', ['draft', 'publish'], true) ? $_GET['status'] : '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Hanya kategori modul ini; untuk kegiatan juga dibatasi lingkup wilayah pengguna.
$where = 'WHERE ' . kondisiModulKonten($pdo, $modul);
$params = [];
if ($search !== '') {
    $where .= ' AND b.judul LIKE :q';
    $params[':q'] = '%' . $search . '%';
}
if ($filterStatus !== '') {
    $where .= ' AND b.status = :status';
    $params[':status'] = $filterStatus;
}

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM berita b $where");
$stmtCount->execute($params);
$totalRows = (int) $stmtCount->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$stmt = $pdo->prepare("SELECT b.id, b.judul, b.slug, (SELECT f.file FROM berita_foto f WHERE f.berita_id = b.id ORDER BY f.urutan, f.id LIMIT 1) AS foto, (SELECT COUNT(*) FROM berita_foto f WHERE f.berita_id = b.id) AS jumlah_foto, b.kategori, b.dilihat, b.status, b.published_at, b.created_at, b.updated_at, u.nama_lengkap AS penulis,
                              w.rt, w.rw, w.kelurahan
                       FROM berita b
                       LEFT JOIN users u ON u.id = b.created_by
                       LEFT JOIN wilayah w ON w.id_wilayah = b.id_wilayah
                       $where
                       ORDER BY COALESCE(b.published_at, b.created_at) DESC, b.id DESC
                       LIMIT :limit OFFSET :offset");
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$jumlah = $pdo->query('SELECT b.status, COUNT(*) FROM berita b WHERE ' . kondisiModulKonten($pdo, $modul) . ' GROUP BY b.status')
              ->fetchAll(PDO::FETCH_KEY_PAIR);
$linkPublik = '../berita.php' . (count($modul['kategori']) === 1 ? '?kategori=' . array_key_first($modul['kategori']) : '');

$pageTitle = $modul['nama'];
require_once __DIR__ . '/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3">
        <span class="page-title-icon"><i class="bi <?= $modul['ikon'] ?>"></i></span>
        <div>
            <h4 class="mb-0"><?= htmlspecialchars($modul['nama']) ?></h4>
            <small class="text-muted">
                <?= (int) ($jumlah['publish'] ?? 0) ?> terbit &middot; <?= (int) ($jumlah['draft'] ?? 0) ?> draft
                &middot; <a href="<?= $linkPublik ?>" target="_blank">Lihat halaman <?= $namaKecil ?> publik <i class="bi bi-box-arrow-up-right"></i></a>
            </small>
        </div>
    </div>
    <a href="tambah.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Tambah <?= htmlspecialchars($modul['nama']) ?>
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white search-card border-0 pt-3">
        <form method="get" class="row g-2">
            <div class="col-sm-6 col-md-5">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Cari judul <?= $namaKecil ?>..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-sm-3 col-md-2">
                <select name="status" class="form-select">
                    <option value="">Semua status</option>
                    <option value="publish" <?= $filterStatus === 'publish' ? 'selected' : '' ?>>Terbit</option>
                    <option value="draft" <?= $filterStatus === 'draft' ? 'selected' : '' ?>>Draft</option>
                </select>
            </div>
            <div class="col-sm-3 col-md-2">
                <button type="submit" class="btn btn-primary w-100">Cari</button>
            </div>
            <?php if ($search !== '' || $filterStatus !== ''): ?>
            <div class="col-sm-3 col-md-2">
                <a href="list.php" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-modern mb-0 align-middle">
                <thead>
                    <tr>
                        <th class="ps-4">Foto</th>
                        <th>Judul</th>
                        <?php if ($modul['wilayah']): ?><th>Wilayah</th><?php endif; ?>
                        <th>Penulis</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th class="text-center pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="<?= $modul['wilayah'] ? 7 : 6 ?>" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Belum ada <?= $namaKecil ?><?= $search !== '' || $filterStatus !== '' ? ' yang cocok dengan pencarian' : '' ?>.
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td class="ps-4">
                                    <?php if ($row['foto']): ?>
                                        <img src="../<?= htmlspecialchars($row['foto']) ?>" alt="" class="rounded" width="72" height="48" style="object-fit:cover">
                                        <?php if ($row['jumlah_foto'] > 1): ?><div class="small text-muted"><i class="bi bi-images"></i> <?= (int) $row['jumlah_foto'] ?> gambar</div><?php endif; ?>
                                    <?php else: ?>
                                        <span class="d-inline-flex align-items-center justify-content-center rounded bg-light text-muted" style="width:72px;height:48px"><i class="bi bi-image"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($row['judul']) ?></div>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars(KATEGORI_BERITA[$row['kategori']] ?? $row['kategori']) ?></span>
                                    <span class="small text-muted ms-1"><i class="bi bi-eye"></i> <?= (int) $row['dilihat'] ?> kali dibaca</span>
                                </td>
                                <?php if ($modul['wilayah']): ?>
                                    <td>
                                        <?php if ($row['rt'] !== null): ?>
                                            RT <?= htmlspecialchars($row['rt']) ?>/RW <?= htmlspecialchars($row['rw']) ?>
                                            <div class="small text-muted"><?= htmlspecialchars($row['kelurahan']) ?></div>
                                        <?php else: ?>-<?php endif; ?>
                                    </td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars($row['penulis'] ?? '-') ?></td>
                                <td>
                                    <?php if ($row['status'] === 'publish'): ?>
                                        <span class="badge bg-success">Terbit</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small">
                                    <?php if ($row['status'] === 'publish'): ?>
                                        Terbit <?= htmlspecialchars(tanggalIndonesia($row['published_at'])) ?>
                                    <?php else: ?>
                                        Dibuat <?= htmlspecialchars(tanggalIndonesia($row['created_at'])) ?>
                                    <?php endif; ?>
                                    <?php if ($row['updated_at']): ?><div class="text-muted">Diubah <?= htmlspecialchars(tanggalIndonesia($row['updated_at'])) ?></div><?php endif; ?>
                                </td>
                                <td class="text-center text-nowrap pe-4">
                                    <?php if ($row['status'] === 'publish'): ?>
                                        <a href="../berita_baca.php?slug=<?= urlencode($row['slug']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Lihat"><i class="bi bi-eye"></i></a>
                                    <?php endif; ?>
                                    <a href="edit.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                    <?php if (!empty($modul['cetak'])): ?>
                                        <a href="cetak.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-dark" title="Cetak Laporan" target="_blank"><i class="bi bi-printer"></i></a>
                                    <?php endif; ?>
                                    <a href="hapus.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-danger" title="Hapus"
                                       onclick="return confirm('Hapus <?= $namaKecil ?> ini? Tindakan ini tidak bisa dibatalkan.');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted">Total: <?= (int) $totalRows ?> data</small>
        <?php if ($totalPages > 1): ?>
        <nav>
            <ul class="pagination pagination-sm mb-0 justify-content-end">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $p ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($filterStatus) ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
