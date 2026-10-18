<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/berita.php';
// Semua pengguna boleh mengelola kegiatan; wilayahnya dibatasi lingkup pengguna (filterWilayahPenduduk).
requireLogin();

$modul = MODUL_KEGIATAN;
require __DIR__ . '/../includes/konten_tambah.php';
