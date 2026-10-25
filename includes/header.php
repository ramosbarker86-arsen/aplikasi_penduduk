<?php
$user = currentUser();
$base = basePath();
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?>Aplikasi Penduduk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= $base ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark app-navbar sticky-top">
    <div class="container-fluid px-3">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= $base ?>index.php">
            <i class="bi bi-people-fill"></i>
            <span>Aplikasi Penduduk</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'index.php' && strpos($_SERVER['REQUEST_URI'], 'penduduk') === false ? 'active' : '' ?>" href="<?= $base ?>index.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <?php
                // Menu tampil sesuai hak akses pengguna (kolom akses di tabel users; admin selalu semua).
                $menuNav = [
                    ['data_penduduk', 'penduduk', 'bi-person-lines-fill'],
                    ['surat_keterangan', 'surat', 'bi-file-earmark-text'],
                    ['kegiatan', 'kegiatan', 'bi-calendar-event'],
                    ['berita', 'berita', 'bi-newspaper'],
                    ['master_wilayah', 'wilayah', 'bi-geo-alt-fill'],
                    ['pengguna', 'users', 'bi-person-gear'],
                ];
                // Nama variabel diberi awalan nav supaya tidak menimpa variabel halaman (mis. $akses di form pengguna).
                foreach ($menuNav as [$navAkses, $navFolder, $navIkon]):
                    if (!punyaAkses($navAkses)) continue; ?>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/' . $navFolder . '/') !== false ? 'active' : '' ?>" href="<?= $base . $navFolder ?>/list.php">
                        <i class="bi <?= $navIkon ?>"></i> <?= AKSES_MENU[$navAkses] ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle fs-5"></i>
                        <span><?= htmlspecialchars($user['nama_lengkap'] ?: $user['username']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text text-muted small">Level: <?= htmlspecialchars(ucfirst($user['level'])) ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= $base ?>logout.php"><i class="bi bi-box-arrow-right"></i> Keluar</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<main class="container-fluid px-3 px-md-4 py-4">
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['flash_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['flash_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>
