<?php
// Hapus surat keterangan (soft delete): status_cd jadi 'inactive', data & nomornya tetap tersimpan.
// Nomor surat yang sudah dihapus tidak dipakai ulang (nomorSuratBerikut menghitung semua surat).
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAkses('surat_keterangan');
requireAdmin();

$pdo = getDB();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("UPDATE surat_keterangan SET status_cd = 'inactive' WHERE id = ? AND status_cd = 'active'");
$stmt->execute([$id]);

if ($stmt->rowCount() > 0) {
    $_SESSION['flash_success'] = 'Surat keterangan berhasil dihapus.';
} else {
    $_SESSION['flash_error'] = 'Surat keterangan tidak ditemukan atau sudah dihapus.';
}

header('Location: list.php');
exit;
