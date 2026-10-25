<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAkses('master_wilayah');

$pdo = getDB();

$id = (int) ($_GET['id'] ?? 0);

// Tabel wilayah tidak punya kolom status_cd, jadi hapusnya memang DELETE permanen.
$stmt = $pdo->prepare('DELETE FROM wilayah WHERE id_wilayah = ?');
$stmt->execute([$id]);

if ($stmt->rowCount() > 0) {
    $_SESSION['flash_success'] = 'Data wilayah berhasil dihapus.';
} else {
    $_SESSION['flash_error'] = 'Data wilayah tidak ditemukan.';
}

header('Location: list.php');
exit;
