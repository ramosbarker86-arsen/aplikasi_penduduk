<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/berita.php';
requireAdmin();

$modul = MODUL_BERITA;
require __DIR__ . '/../includes/konten_list.php';
