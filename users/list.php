<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAkses('pengguna');

$pdo = getDB();

// Selain admin tidak melihat akun admin.
$rows = $pdo->query('SELECT u.id, u.username, u.nama_lengkap, u.level, u.sub_level, u.status_cd, u.foto_profil, u.created_at,
                            ' . implode(', ', array_map(fn($m) => "u.$m", array_keys(AKSES_MENU))) . ',
                            w.rt, w.rw, w.kelurahan, w.kecamatan, w.Kabupaten
                     FROM users u
                     LEFT JOIN wilayah w ON w.id_wilayah = u.id_wilayah
                     ' . (isAdmin() ? '' : "WHERE u.level <> 'admin'") . '
                     ORDER BY u.username ASC')->fetchAll();
$subLevelLabels = ['rt' => 'RT', 'rw' => 'RW', 'kelurahan' => 'Kelurahan'];

$pageTitle = 'Pengguna';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3">
        <span class="page-title-icon"><i class="bi bi-person-gear"></i></span>
        <div>
            <h4 class="mb-0">Pengguna</h4>
            <small class="text-muted"><?= count($rows) ?> pengguna terdaftar</small>
        </div>
    </div>
    <a href="tambah.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Tambah Pengguna
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-modern mb-0 align-middle">
                <thead>
                    <tr>
                        <th class="ps-4">Foto</th>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Level</th>
                        <th>Sub Level</th>
                        <th>Wilayah</th>
                        <th>Hak Akses</th>
                        <th>Status</th>
                        <th>Dibuat</th>
                        <th class="text-center pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td class="ps-4">
                                <?php if ($row['foto_profil']): ?>
                                    <img src="../<?= htmlspecialchars($row['foto_profil']) ?>" alt="" class="rounded-circle" width="36" height="36" style="object-fit:cover">
                                <?php else: ?>
                                    <i class="bi bi-person-circle fs-3 text-muted"></i>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                            <td>
                                <span class="badge <?= $row['level'] === 'admin' ? 'bg-primary' : 'bg-secondary' ?>"><?= htmlspecialchars(ucfirst($row['level'])) ?></span>
                            </td>
                            <td><?= htmlspecialchars($subLevelLabels[$row['sub_level']] ?? '-') ?></td>
                            <td>
                                <?php if ($row['rt'] !== null): ?>
                                    RT <?= htmlspecialchars($row['rt']) ?>/RW <?= htmlspecialchars($row['rw']) ?>
                                    <div class="small text-muted"><?= htmlspecialchars($row['kelurahan'] . ', ' . $row['kecamatan'] . ', ' . $row['Kabupaten']) ?></div>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td style="max-width: 260px">
                                <?php $dimiliki = array_filter(AKSES_MENU, fn($m) => $row[$m] === 'ya', ARRAY_FILTER_USE_KEY); ?>
                                <?php if (count($dimiliki) === count(AKSES_MENU)): ?>
                                    <span class="badge bg-primary-subtle text-primary-emphasis">Semua menu</span>
                                <?php else: ?>
                                    <?php foreach ($dimiliki as $label): ?>
                                        <span class="badge bg-light text-dark border fw-normal"><?= htmlspecialchars($label) ?></span>
                                    <?php endforeach; ?>
                                    <?php if (!$dimiliki): ?><span class="text-muted small">Tidak ada</span><?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['status_cd'] === 'active'): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Tidak Aktif</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars(date('d-m-Y H:i', strtotime($row['created_at']))) ?></td>
                            <td class="text-center text-nowrap pe-4">
                                <a href="edit.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
