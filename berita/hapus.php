<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/berita.php';
requireAkses('berita');
requireAdmin();

$modul = MODUL_BERITA;
require __DIR__ . '/../includes/konten_hapus.php';
