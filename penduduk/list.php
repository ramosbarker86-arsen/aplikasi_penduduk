<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAkses('data_penduduk');

$pdo = getDB();

$search = trim($_GET['q'] ?? '');
$jkOptions = ['L' => 'Laki-laki', 'P' => 'Perempuan'];
$filterJk = isset($jkOptions[$_GET['jk'] ?? '']) ? $_GET['jk'] : '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = "WHERE p.status_cd <> 'nullified' AND " . filterWilayahPenduduk('p.id_wilayah');
$params = [];

if ($search !== '') {
    $where .= ' AND (p.nik LIKE :q1 OR p.no_kk LIKE :q2 OR p.nama_lengkap LIKE :q3 OR p.alamat LIKE :q4)';
    $like = '%' . $search . '%';
    $params[':q1'] = $like;
    $params[':q2'] = $like;
    $params[':q3'] = $like;
    $params[':q4'] = $like;
}
if ($filterJk !== '') {
    $where .= ' AND p.jenis_kelamin = :jk';
    $params[':jk'] = $filterJk;
}

$totalRows = (int) (function () use ($pdo, $where, $params) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM penduduk p $where");
    $stmt->execute($params);
    return $stmt->fetchColumn();
})();

$totalPages = max(1, (int) ceil($totalRows / $perPage));

$stmt = $pdo->prepare("SELECT p.id, p.nik, p.no_kk, p.nama_lengkap, p.jenis_kelamin, p.tanggal_lahir, p.alamat,
                               w.rt, w.rw, p.no_telepon, p.status_cd
                        FROM penduduk p
                        LEFT JOIN wilayah w ON w.id_wilayah = p.id_wilayah
                        $where
                        ORDER BY CAST(w.rt AS UNSIGNED), CAST(w.rw AS UNSIGNED), p.nama_lengkap ASC
                        LIMIT :limit OFFSET :offset");
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

// Umur dihitung dari tanggal lahir saat ini (kolom umur di tabel hanya diperbarui saat data disimpan).
function umurPenduduk($tanggalLahir): string
{
    $ts = $tanggalLahir ? strtotime($tanggalLahir) : false;
    if ($ts === false) return '-';
    return (new DateTime(date('Y-m-d', $ts)))->diff(new DateTime('today'))->y . ' th';
}

$pageTitle = 'Data Penduduk';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3">
        <span class="page-title-icon"><i class="bi bi-person-lines-fill"></i></span>
        <div>
            <h4 class="mb-0">Data Penduduk</h4>
            <small class="text-muted"><?= (int) $totalRows ?> penduduk terdaftar</small>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="laporan.php" class="btn btn-outline-primary" target="_blank">
            <i class="bi bi-file-earmark-bar-graph"></i> Laporan
        </a>
        <a href="tambah.php" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Tambah Penduduk
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white search-card border-0 pt-3">
        <form method="get" class="row g-2">
            <div class="col-sm-8 col-md-5">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Cari NIK, No. KK, nama, atau alamat..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-sm-4 col-md-2">
                <select name="jk" class="form-select" onchange="this.form.submit()" aria-label="Filter jenis kelamin">
                    <option value="">Semua jenis kelamin</option>
                    <?php foreach ($jkOptions as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $filterJk === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-4 col-md-2">
                <button type="submit" class="btn btn-primary w-100">Cari</button>
            </div>
            <?php if ($search !== '' || $filterJk !== ''): ?>
            <div class="col-sm-4 col-md-2">
                <a href="list.php" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-modern mb-0 align-middle dt-simple">
                <thead>
                    <tr>
                        <th class="ps-4">Nama Penduduk</th>
                        <th>Jenis Kelamin</th>
                        <th>NIK</th>
                        <th>No. KK</th>
                        <th>Tgl Lahir</th>
                        <th>Umur</th>
                        <th>Alamat</th>
                        <th>RT/RW</th>
                        <th>No. Telepon</th>
                        <th>Status</th>
                        <th class="text-center pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="11" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Tidak ada data penduduk<?= $search !== '' ? ' yang cocok dengan pencarian' : '' ?>.
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td class="ps-4">
                                    <a href="detail.php?id=<?= (int) $row['id'] ?>" class="fw-semibold text-decoration-none"><?= htmlspecialchars($row['nama_lengkap']) ?></a>
                                </td>
                                <td>
                                    <span class="badge rounded-pill <?= $row['jenis_kelamin'] === 'L' ? 'text-bg-primary' : 'text-bg-danger' ?>">
                                        <?= $row['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars(tampilNik($row['nik'])) ?></td>
                                <td><?= htmlspecialchars(tampilNoKk($row['no_kk'])) ?></td>
                                <td><?= htmlspecialchars(tampilTanggalLahir($row['tanggal_lahir'])) ?></td>
                                <td class="text-nowrap"><?= htmlspecialchars(umurPenduduk($row['tanggal_lahir'])) ?></td>
                                <td><?= htmlspecialchars($row['alamat']) ?></td>
                                <td><?= htmlspecialchars(($row['rt'] ?: '-') . '/' . ($row['rw'] ?: '-')) ?></td>
                                <td><?= htmlspecialchars($row['no_telepon'] ?: '-') ?></td>
                                <td>
                                    <?php if ($row['status_cd'] === 'active'): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Tidak Aktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center text-nowrap pe-4">
                                    <a href="detail.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Detail"><i class="bi bi-eye"></i></a>
                                    <?php if (isAdmin()): ?>
                                    <a href="edit.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                    <a href="hapus.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-danger" title="Hapus"
                                       onclick="return confirm('Hapus data penduduk \'<?= htmlspecialchars(addslashes($row['nama_lengkap'])) ?>\'?');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                    <?php endif; ?>
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
                        <a class="page-link" href="?page=<?= $p ?>&q=<?= urlencode($search) ?>&jk=<?= urlencode($filterJk) ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
