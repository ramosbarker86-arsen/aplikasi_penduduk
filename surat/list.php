<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/surat.php';
// Semua pengguna boleh membuat surat; data penduduk dibatasi lingkup wilayahnya (filterWilayahPenduduk).
requireLogin();

$pdo = getDB();

$search = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Nama diambil dari penduduk aktif/terbaru dengan NIK tersebut (NIK bisa dipakai ulang oleh data nullified).
$from = "FROM surat_keterangan s
         LEFT JOIN penduduk p ON p.id = (SELECT p2.id FROM penduduk p2 WHERE p2.nik = s.nik
                                          ORDER BY FIELD(p2.status_cd, 'active', 'inactive', 'nullified'), p2.id DESC LIMIT 1)";
// Selain admin hanya melihat surat milik penduduk dalam lingkup wilayahnya.
$where = 'WHERE ' . filterWilayahPenduduk('p.id_wilayah');
$params = [];
if ($search !== '') {
    $where .= ' AND (s.nomor_surat LIKE :q1 OR s.nik LIKE :q2 OR p.nama_lengkap LIKE :q3)';
    $like = '%' . $search . '%';
    $params = [':q1' => $like, ':q2' => $like, ':q3' => $like];
}

$stmtCount = $pdo->prepare("SELECT COUNT(*) $from $where");
$stmtCount->execute($params);
$totalRows = (int) $stmtCount->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$stmt = $pdo->prepare("SELECT s.id, s.nomor_surat, s.tanggal_surat, s.nik, s.keperluan, s.keperluan_lainnya, p.nama_lengkap
                       $from $where
                       ORDER BY s.tanggal_surat DESC, s.id DESC
                       LIMIT :limit OFFSET :offset");
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$pageTitle = 'Surat Keterangan';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3">
        <span class="page-title-icon"><i class="bi bi-file-earmark-text"></i></span>
        <div>
            <h4 class="mb-0">Surat Keterangan</h4>
            <small class="text-muted"><?= (int) $totalRows ?> surat dibuat</small>
        </div>
    </div>
    <a href="tambah.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Buat Surat
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white search-card border-0 pt-3">
        <form method="get" class="row g-2">
            <div class="col-sm-8 col-md-5">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Cari nomor surat, NIK, atau nama..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-sm-4 col-md-2">
                <button type="submit" class="btn btn-primary w-100">Cari</button>
            </div>
            <?php if ($search !== ''): ?>
            <div class="col-sm-4 col-md-2">
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
                        <th class="ps-4">Nomor Surat</th>
                        <th>Tanggal</th>
                        <th>Nama Penduduk</th>
                        <th>NIK</th>
                        <th>Keperluan</th>
                        <th class="text-center pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Belum ada surat keterangan<?= $search !== '' ? ' yang cocok dengan pencarian' : '' ?>.
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $kep = array_filter(explode(',', $row['keperluan']));
                            if ($row['keperluan_lainnya']) {
                                $kep = array_map(fn($k) => $k === 'Keterangan Lainnya' ? 'Lainnya: ' . $row['keperluan_lainnya'] : $k, $kep);
                            }
                            ?>
                            <tr>
                                <td class="ps-4 fw-semibold"><?= htmlspecialchars($row['nomor_surat']) ?></td>
                                <td><?= htmlspecialchars(tanggalIndonesia($row['tanggal_surat'])) ?></td>
                                <td><?= htmlspecialchars($row['nama_lengkap'] ?? '-') ?></td>
                                <td><?= htmlspecialchars(tampilNik($row['nik'])) ?></td>
                                <td><?= htmlspecialchars(implode(', ', $kep)) ?></td>
                                <td class="text-center text-nowrap pe-4">
                                    <a href="edit.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit Keperluan"><i class="bi bi-pencil"></i></a>
                                    <a href="cetak.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-dark" title="Cetak" target="_blank"><i class="bi bi-printer"></i></a>
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
                <?php for ($pg = 1; $pg <= $totalPages; $pg++): ?>
                    <li class="page-item <?= $pg === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $pg ?>&q=<?= urlencode($search) ?>"><?= $pg ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
