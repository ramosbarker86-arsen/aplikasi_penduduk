<?php
// Pulihkan surat keterangan yang dihapus: status_cd kembali 'active' dengan nomor surat yang sama.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAkses('surat_keterangan');
requireAdmin();

$pdo = getDB();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("UPDATE surat_keterangan SET status_cd = 'active' WHERE id = ? AND status_cd = 'inactive'");
$stmt->execute([$id]);

if ($stmt->rowCount() > 0) {
    $_SESSION['flash_success'] = 'Surat keterangan berhasil dipulihkan.';
} else {
    $_SESSION['flash_error'] = 'Surat keterangan tidak ditemukan atau tidak dalam keadaan dihapus.';
}

header('Location: list.php?status=inactive');
exit;
