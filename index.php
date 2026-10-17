<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$pdo = getDB();
$lingkup = filterWilayahPenduduk('id_wilayah');

$totalPenduduk = (int) $pdo->query("SELECT COUNT(*) FROM penduduk WHERE status_cd = 'active' AND $lingkup")->fetchColumn();
$totalLaki     = (int) $pdo->query("SELECT COUNT(*) FROM penduduk WHERE status_cd = 'active' AND $lingkup AND jenis_kelamin = 'L'")->fetchColumn();
$totalPerempuan = (int) $pdo->query("SELECT COUNT(*) FROM penduduk WHERE status_cd = 'active' AND $lingkup AND jenis_kelamin = 'P'")->fetchColumn();
$totalKK       = (int) $pdo->query("SELECT COUNT(DISTINCT no_kk) FROM penduduk WHERE status_cd = 'active' AND $lingkup")->fetchColumn();

$terbaru = $pdo->query("SELECT id, nik, nama_lengkap, jenis_kelamin, alamat, created_at FROM penduduk WHERE status_cd = 'active' AND $lingkup ORDER BY created_at DESC LIMIT 5")->fetchAll();

$statusKeluarga = $pdo->query("SELECT status_keluarga AS label, COUNT(*) AS total
                                FROM penduduk
                                WHERE status_cd = 'active' AND $lingkup AND status_keluarga IS NOT NULL AND status_keluarga != ''
                                GROUP BY status_keluarga
                                ORDER BY total DESC")->fetchAll();

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Dashboard</h4>
    <a href="penduduk/tambah.php" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Tambah Penduduk
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card bg-blue">
            <div class="stat-number"><?= number_format($totalPenduduk, 0, ',', '.') ?></div>
            <div class="small">Total Penduduk</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card bg-teal">
            <div class="stat-number"><?= number_format($totalLaki, 0, ',', '.') ?></div>
            <div class="small">Laki-laki</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card bg-pink">
            <div class="stat-number"><?= number_format($totalPerempuan, 0, ',', '.') ?></div>
            <div class="small">Perempuan</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card bg-amber">
            <div class="stat-number"><?= number_format($totalKK, 0, ',', '.') ?></div>
            <div class="small">Jumlah KK</div>
        </div>
    </div>
</div>


<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header bg-white"><strong>Jenis Kelamin</strong></div>
            <div class="card-body">
                <canvas id="chartJenisKelamin" height="220"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header bg-white"><strong>Status dalam Keluarga</strong></div>
            <div class="card-body">
                <canvas id="chartStatusKeluarga" height="220"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong>Penduduk Terbaru Ditambahkan</strong>
        <a href="penduduk/list.php" class="small">Lihat semua &raquo;</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>NIK</th>
                        <th>Nama Lengkap</th>
                        <th>Jenis Kelamin</th>
                        <th>Alamat</th>
                        <th>Ditambahkan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($terbaru)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data penduduk.</td></tr>
                    <?php else: ?>
                        <?php foreach ($terbaru as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars(tampilNik($row['nik'])) ?></td>
                                <td><a href="penduduk/detail.php?id=<?= (int) $row['id'] ?>"><?= htmlspecialchars($row['nama_lengkap']) ?></a></td>
                                <td><?= $row['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                                <td><?= htmlspecialchars($row['alamat']) ?></td>
                                <td><?= htmlspecialchars(date('d-m-Y H:i', strtotime($row['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chartJenisKelamin'), {
    type: 'pie',
    data: {
        labels: ['Laki-laki', 'Perempuan'],
        datasets: [{
            data: [<?= $totalLaki ?>, <?= $totalPerempuan ?>],
            backgroundColor: ['#0f766e', '#db2777'],
        }],
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' },
        },
    },
});

new Chart(document.getElementById('chartStatusKeluarga'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($statusKeluarga, 'label')) ?>,
        datasets: [{
            data: <?= json_encode(array_map('intval', array_column($statusKeluarga, 'total'))) ?>,
            backgroundColor: ['#1a2a44', '#0f766e', '#db2777', '#d97706', '#6c757d'],
        }],
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
        },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } },
        },
    },
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
