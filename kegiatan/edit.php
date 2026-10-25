<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/berita.php';
// Butuh akses menu Kegiatan; wilayahnya dibatasi lingkup pengguna (filterWilayahPenduduk).
requireAkses('kegiatan');

$modul = MODUL_KEGIATAN;
require __DIR__ . '/../includes/konten_edit.php';
