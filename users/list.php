<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pdo = getDB();

$rows = $pdo->query('SELECT id, username, nama_lengkap, level, foto_profil, created_at FROM users ORDER BY username ASC')->fetchAll();

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
                        <th class="pe-4">Dibuat</th>
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
                            <td class="pe-4"><?= htmlspecialchars(date('d-m-Y H:i', strtotime($row['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
