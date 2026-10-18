<?php
// Hapus konten (berita/hapus.php, kegiatan/hapus.php).
// Pemanggil sudah memuat database/auth/berita.php, memeriksa akses, dan mengisi $modul (MODUL_BERITA / MODUL_KEGIATAN).
$pdo = getDB();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT b.id FROM berita b WHERE b.id = ? AND ' . kondisiModulKonten($pdo, $modul));
$stmt->execute([$id]);

if ($stmt->fetch()) {
    $files = array_column(daftarFotoBerita($pdo, $id), 'file');
    // Baris berita_foto ikut terhapus lewat ON DELETE CASCADE; file gambarnya dihapus manual.
    $pdo->prepare('DELETE FROM berita WHERE id = ?')->execute([$id]);
    array_map('hapusFileBerita', $files);
    $_SESSION['flash_success'] = $modul['nama'] . ' berhasil dihapus.';
} else {
    $_SESSION['flash_error'] = $modul['nama'] . ' tidak ditemukan.';
}

header('Location: list.php');
exit;
