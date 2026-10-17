<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pdo = getDB();

$id = (int) ($_GET['id'] ?? 0);

// Soft delete: cuma ubah status_cd jadi 'nullified', bukan DELETE sungguhan, konsisten dengan
// komentar kolom status_cd di skema (active = Aktif, inactive = Tidak Aktif, nullified = Dihapus).
$stmt = $pdo->prepare("UPDATE penduduk SET status_cd = 'nullified' WHERE id = ? AND status_cd <> 'nullified'");
$stmt->execute([$id]);

if ($stmt->rowCount() > 0) {
    $_SESSION['flash_success'] = 'Data penduduk berhasil dihapus.';
} else {
    $_SESSION['flash_error'] = 'Data penduduk tidak ditemukan atau sudah dihapus.';
}

header('Location: list.php');
exit;
