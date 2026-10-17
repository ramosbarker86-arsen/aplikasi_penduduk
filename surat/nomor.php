<?php
// Endpoint JSON: pratinjau nomor surat berikutnya untuk tanggal tertentu (dipakai saat tanggal surat diganti).
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/surat.php';
// Semua pengguna boleh membuat surat; data penduduk dibatasi lingkup wilayahnya (filterWilayahPenduduk).
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$tanggal = $_GET['tanggal'] ?? '';
$d = DateTime::createFromFormat('Y-m-d', $tanggal);
if (!$d || $d->format('Y-m-d') !== $tanggal) {
    http_response_code(400);
    echo json_encode(['error' => 'Tanggal tidak valid.']);
    exit;
}

echo json_encode(['nomor_surat' => nomorSuratBerikut(getDB(), $tanggal)['nomor_surat']]);
