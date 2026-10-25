<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAkses('data_penduduk');

$pdo = getDB();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT p.*, w.rt, w.rw
                        FROM penduduk p
                        LEFT JOIN wilayah w ON w.id_wilayah = p.id_wilayah
                        WHERE p.id = ? AND p.status_cd <> \'nullified\' AND ' . filterWilayahPenduduk('p.id_wilayah'));
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    $_SESSION['flash_error'] = 'Data penduduk tidak ditemukan.';
    header('Location: list.php');
    exit;
}

function field($label, $value)
{
    echo '<div class="col-md-4 mb-3">';
    echo '<div class="text-muted small">' . htmlspecialchars($label) . '</div>';
    echo '<div class="fw-medium">' . ($value !== null && $value !== '' ? htmlspecialchars($value) : '-') . '</div>';
    echo '</div>';
}

$pageTitle = 'Detail Penduduk';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Detail Penduduk</h4>
    <div>
        <a href="cetak_kk.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-dark" target="_blank"><i class="bi bi-printer"></i> Cetak Kartu Keluarga</a>
        <?php if (isAdmin()): ?>
        <a href="edit.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Edit</a>
        <?php endif; ?>
        <a href="list.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="row">
            <?php
            field('NIK', tampilNik($row['nik']));
            field('No. KK', tampilNoKk($row['no_kk']));
            field('Nama Lengkap', $row['nama_lengkap']);
            field('Tempat, Tanggal Lahir', trim($row['tempat_lahir'] . ', ' . tampilTanggalLahir($row['tanggal_lahir']), ', '));
            field('Umur', $row['umur'] !== null ? $row['umur'] . ' tahun' : null);
            field('Jenis Kelamin', $row['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan');
            field('Agama', $row['agama']);
            field('Pendidikan', $row['pendidikan']);
            field('Pekerjaan', $row['pekerjaan']);
            field('Status Perkawinan', $row['status_kawin']);
            field('Status dalam Keluarga', $row['status_keluarga']);
            field('Kategori', $row['kategori']);
            field('Ahli Waris', $row['is_ahli_waris'] ? 'Ya' : 'Tidak');
            field('No. Telepon', $row['no_telepon']);
            field('Email', tampilEmail($row['email']));
            field('Nama Ayah', $row['nama_ayah']);
            field('Nama Ibu', $row['nama_ibu']);
            field('RT/RW', trim(($row['rt'] ?: '-') . '/' . ($row['rw'] ?: '-')));
            field('Terdaftar Sejak', date('d-m-Y H:i', strtotime($row['created_at'])));
            ?>
            <div class="col-12 mb-3">
                <div class="text-muted small">Alamat</div>
                <div class="fw-medium"><?= nl2br(htmlspecialchars($row['alamat'])) ?></div>
            </div>
        </div>

        <hr>

        <div class="row">
            <div class="col-md-6">
                <div class="text-muted small mb-1">Foto KTP</div>
                <?php if ($row['foto_ktp']): ?>
                    <a href="../<?= htmlspecialchars($row['foto_ktp']) ?>" target="_blank">
                        <?php if (substr(strtolower($row['foto_ktp']), -4) === '.pdf'): ?>
                            <span class="badge bg-secondary"><i class="bi bi-file-earmark-pdf"></i> Lihat PDF</span>
                        <?php else: ?>
                            <img src="../<?= htmlspecialchars($row['foto_ktp']) ?>" class="foto-preview">
                        <?php endif; ?>
                    </a>
                <?php else: ?>
                    <span class="text-muted">Belum diunggah</span>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <div class="text-muted small mb-1">Foto KK</div>
                <?php if ($row['foto_kk']): ?>
                    <a href="../<?= htmlspecialchars($row['foto_kk']) ?>" target="_blank">
                        <?php if (substr(strtolower($row['foto_kk']), -4) === '.pdf'): ?>
                            <span class="badge bg-secondary"><i class="bi bi-file-earmark-pdf"></i> Lihat PDF</span>
                        <?php else: ?>
                            <img src="../<?= htmlspecialchars($row['foto_kk']) ?>" class="foto-preview">
                        <?php endif; ?>
                    </a>
                <?php else: ?>
                    <span class="text-muted">Belum diunggah</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
