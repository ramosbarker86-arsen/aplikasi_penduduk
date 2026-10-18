<?php
// Kerangka halaman publik (tanpa login): home.php/login.php, berita.php, dan berita_baca.php.
// Variabel opsional: $pageTitle, $menuAktif ('home' | 'berita').
$menuAktif = $menuAktif ?? 'berita';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?>Aplikasi Penduduk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --biru: #1257b8; --biru-tua: #0a3a82; --biru-gelap: #072a5e; --biru-muda: #e9f1fd;
            --emas: #e0b12c; --emas-tua: #c39318;
            --latar: #f3f6fb; --teks: #1d2433; --abu: #6b7385;
            --radius: 16px; --bayangan: 0 6px 24px rgba(16, 42, 92, .08); --bayangan-hover: 0 14px 34px rgba(16, 42, 92, .16);
            --transisi: .35s cubic-bezier(.2, .7, .2, 1);
        }
        html { scroll-behavior: smooth; }
        body { font-family: 'Poppins', system-ui, sans-serif; background: var(--latar); color: var(--teks); }
        a { color: var(--biru); transition: color var(--transisi); }
        a:hover { color: var(--biru-tua); }
        ::selection { background: var(--emas); color: #fff; }

        /* Navbar */
        .publik-navbar { background: linear-gradient(100deg, var(--biru-gelap) 0%, var(--biru-tua) 45%, var(--biru) 100%);
                         padding: .7rem 0; transition: padding var(--transisi), box-shadow var(--transisi); }
        .publik-navbar.mengecil { padding: .35rem 0; box-shadow: 0 6px 22px rgba(7, 42, 94, .35); }
        .publik-navbar .navbar-brand { font-weight: 700; font-size: 1.3rem; letter-spacing: .2px; }
        .publik-navbar .navbar-brand img { height: 44px; width: auto; filter: drop-shadow(0 2px 4px rgba(0, 0, 0, .25)); transition: height var(--transisi); }
        .publik-navbar.mengecil .navbar-brand img { height: 36px; }
        .publik-navbar .nav-link { position: relative; color: rgba(255, 255, 255, .78) !important; font-weight: 600; font-size: 1.05rem;
                                   padding: .5rem 1.1rem !important; transition: color var(--transisi); }
        .publik-navbar .nav-link::after { content: ''; position: absolute; left: 1.1rem; right: 1.1rem; bottom: .2rem; height: 3px; border-radius: 3px;
                                          background: var(--emas); transform: scaleX(0); transform-origin: center; transition: transform var(--transisi); }
        .publik-navbar .nav-link:hover, .publik-navbar .nav-link.active { color: #fff !important; }
        .publik-navbar .nav-link:hover::after, .publik-navbar .nav-link.active::after { transform: scaleX(1); }
        .publik-navbar .btn-outline-light { border-radius: 999px; padding: .35rem 1rem; }

        /* Tombol */
        .btn { transition: all var(--transisi); }
        .btn-emas { background: linear-gradient(135deg, var(--emas), var(--emas-tua)); border: none; color: #fff; font-weight: 600; }
        .btn-emas:hover { color: #fff; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(195, 147, 24, .4); }
        .btn-biru { background: linear-gradient(135deg, var(--biru), var(--biru-tua)); border: none; color: #fff; font-weight: 600; }
        .btn-biru:hover { color: #fff; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(18, 87, 184, .4); }
        .btn-primary { background: var(--biru); border-color: var(--biru); }
        .btn-primary:hover { background: var(--biru-tua); border-color: var(--biru-tua); }
        .form-control:focus, .form-select:focus { border-color: var(--biru); box-shadow: 0 0 0 .2rem rgba(18, 87, 184, .15); }
        .pagination .page-link { color: var(--biru); border-radius: 10px !important; margin: 0 3px; border: none; box-shadow: var(--bayangan); }
        .pagination .active > .page-link { background: var(--biru); color: #fff; }

        /* Kartu umum */
        .kartu { background: #fff; border-radius: var(--radius); box-shadow: var(--bayangan); transition: transform var(--transisi), box-shadow var(--transisi); }
        .kartu-hover:hover { transform: translateY(-4px); box-shadow: var(--bayangan-hover); }
        .zoom { overflow: hidden; }
        .zoom img { transition: transform .8s cubic-bezier(.2, .7, .2, 1); }
        .kartu-hover:hover .zoom img, .zoom:hover img { transform: scale(1.07); }

        /* Daftar & baca berita */
        .berita-card { border: none; border-radius: var(--radius); overflow: hidden; box-shadow: var(--bayangan); transition: transform var(--transisi), box-shadow var(--transisi); }
        .berita-card:hover { transform: translateY(-5px); box-shadow: var(--bayangan-hover); }
        .berita-card img { height: 210px; object-fit: cover; transition: transform .8s cubic-bezier(.2, .7, .2, 1); }
        .berita-card:hover img { transform: scale(1.06); }
        .berita-card .card-img-wrap { overflow: hidden; }
        .berita-card .placeholder-foto { height: 210px; background: linear-gradient(135deg, var(--biru-muda), #dbe6f8); color: #9fb3d6; font-size: 2.5rem; }
        .berita-card .card-title { font-weight: 600; transition: color var(--transisi); }
        .berita-card:hover .card-title { color: var(--biru); }
        .berita-baca { max-width: 800px; }
        .berita-baca .foto-utama { max-height: 460px; object-fit: cover; border-radius: var(--radius); box-shadow: var(--bayangan); }
        .berita-baca .foto-galeri { height: 240px; object-fit: cover; border-radius: 12px; }
        .keterangan-foto { font-size: .88rem; color: var(--abu); margin-top: .5rem; line-height: 1.5; }
        .konten-berita { font-size: 1.05rem; line-height: 1.9; }
        .konten-berita h2, .konten-berita h3, .konten-berita h4 { margin-top: 1.5rem; font-weight: 600; }
        .konten-berita blockquote { border-left: 4px solid var(--emas); background: #fff; border-radius: 0 12px 12px 0; padding: .75rem 1rem; color: #555; font-style: italic; }
        .konten-berita .ql-align-center { text-align: center; }
        .konten-berita .ql-align-right { text-align: right; }
        .konten-berita .ql-align-justify { text-align: justify; }

        /* Muncul perlahan saat di-scroll */
        .muncul { opacity: 0; transform: translateY(18px); transition: opacity .7s ease, transform .7s cubic-bezier(.2, .7, .2, 1); }
        .muncul.tampak { opacity: 1; transform: none; }

        .publik-footer { background: linear-gradient(100deg, var(--biru-gelap), var(--biru-tua)); color: rgba(255, 255, 255, .75); }
        .publik-footer a { color: #fff; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { transition: none !important; animation: none !important; }
            .muncul { opacity: 1; transform: none; }
            html { scroll-behavior: auto; }
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-md navbar-dark publik-navbar sticky-top" id="navPublikBar">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand d-flex align-items-center gap-2" href="home.php">
            <img src="assets/img/logo-kabupaten-bogor.png" alt="Logo Kabupaten Bogor">
            <span>Aplikasi Penduduk</span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navPublik">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navPublik">
            <ul class="navbar-nav mx-md-auto ps-md-5">
                <li class="nav-item"><a class="nav-link <?= $menuAktif === 'home' ? 'active' : '' ?>" href="home.php">Home</a></li>
                <li class="nav-item"><a class="nav-link <?= $menuAktif === 'berita' ? 'active' : '' ?>" href="berita.php">Berita</a></li>
            </ul>
            <?php if (!empty($_SESSION['user_id'])): ?>
                <a href="index.php" class="btn btn-sm btn-outline-light"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main class="<?= $menuAktif === 'home' ? 'container-fluid px-3 px-lg-4' : 'container' ?> py-4">
