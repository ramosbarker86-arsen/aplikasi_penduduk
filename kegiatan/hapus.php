<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/berita.php';
// Menghapus kegiatan khusus admin; pengguna lain hanya bisa menambah, mengedit, dan mencetak.
requireAkses('kegiatan');
requireAdmin();

$modul = MODUL_KEGIATAN;
require __DIR__ . '/../includes/konten_hapus.php';
