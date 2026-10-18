<?php
// Halaman depan publik: slider berita terpopuler, daftar berita, Member Login, pencarian,
// dan tab Berita/Kegiatan. Juga ditampilkan oleh login.php (variabel $loginError dari sana).
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/berita.php';
require_once __DIR__ . '/includes/surat.php';

$pdo = getDB();
$loginError = $loginError ?? '';

$kolom = "b.judul, b.slug, b.kategori, b.konten, b.published_at, u.nama_lengkap AS penulis,
          (SELECT f.file FROM berita_foto f WHERE f.berita_id = b.id ORDER BY f.urutan, f.id LIMIT 1) AS foto";
$dari = "FROM berita b LEFT JOIN users u ON u.id = b.created_by WHERE b.status = 'publish'";

// Slider: berita terpopuler (paling banyak dibaca), utamakan yang punya foto.
$populer = $pdo->query("SELECT $kolom $dari
                        ORDER BY (SELECT COUNT(*) FROM berita_foto f WHERE f.berita_id = b.id) > 0 DESC,
                                 b.dilihat DESC, b.published_at DESC
                        LIMIT 5")->fetchAll();

$terbaru = $pdo->query("SELECT $kolom $dari AND b.kategori = 'berita' ORDER BY b.published_at DESC LIMIT 6")->fetchAll();

// Statistik publik (hanya angka agregat, tanpa data pribadi): penduduk aktif per jenis kelamin.
$jumlahJk = $pdo->query("SELECT jenis_kelamin, COUNT(*) FROM penduduk WHERE status_cd = 'active' GROUP BY jenis_kelamin")
                ->fetchAll(PDO::FETCH_KEY_PAIR);
$segmenJk = [
    ['label' => 'Laki-laki', 'jumlah' => (int) ($jumlahJk['L'] ?? 0), 'warna' => 'var(--series-1)'],
    ['label' => 'Perempuan', 'jumlah' => (int) ($jumlahJk['P'] ?? 0), 'warna' => 'var(--series-2)'],
];
$totalPenduduk = array_sum(array_column($segmenJk, 'jumlah'));

// Tab di sidebar: daftar berita terbaru dan kegiatan.
const TAB_HOME = ['berita', 'kegiatan'];
$tab = [];
foreach (TAB_HOME as $k) {
    $stmt = $pdo->prepare("SELECT $kolom $dari AND b.kategori = ? ORDER BY b.published_at DESC LIMIT 5");
    $stmt->execute([$k]);
    $tab[$k] = $stmt->fetchAll();
}

function linkBerita(array $b): string
{
    return 'berita_baca.php?slug=' . urlencode($b['slug']);
}

$pageTitle = 'Home';
$menuAktif = 'home';
require __DIR__ . '/includes/publik_header.php';
?>
<style>
    /* Slider berita terpopuler */
    .hero { border-radius: 20px; overflow: hidden; box-shadow: var(--bayangan-hover); background: var(--biru-gelap); }
    .hero .carousel-item { height: 540px; overflow: hidden; }
    .hero .carousel-item img, .hero .hero-kosong { width: 100%; height: 100%; object-fit: cover; }
    .hero .carousel-item.active img { animation: kenBurns 9s ease-out forwards; }
    @keyframes kenBurns { from { transform: scale(1); } to { transform: scale(1.08); } }
    .hero .hero-kosong { background: radial-gradient(circle at 30% 20%, var(--biru) 0%, var(--biru-gelap) 75%); }
    .hero .overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(4, 18, 45, .9) 0%, rgba(4, 18, 45, .35) 45%, rgba(4, 18, 45, 0) 72%); }
    .hero .caption { position: absolute; left: 1.75rem; right: 1.75rem; bottom: 1.6rem; color: #fff; }
    .hero .carousel-item.active .caption > * { animation: naik .8s cubic-bezier(.2, .7, .2, 1) both; }
    .hero .carousel-item.active .caption > *:nth-child(2) { animation-delay: .1s; }
    .hero .carousel-item.active .caption > *:nth-child(3) { animation-delay: .2s; }
    @keyframes naik { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }
    .hero .label { display: inline-flex; align-items: center; gap: .5rem; background: rgba(255, 255, 255, .14); backdrop-filter: blur(6px);
                   border: 1px solid rgba(255, 255, 255, .25); border-radius: 999px; padding: .3rem .9rem; font-size: .8rem;
                   text-transform: uppercase; letter-spacing: .5px; }
    .hero .label .utama { color: var(--emas); font-weight: 700; }
    .hero .judul { font-weight: 600; font-size: 1.7rem; line-height: 1.3; margin: .85rem 0; text-shadow: 0 2px 8px rgba(0, 0, 0, .4); }
    .hero .judul a { color: #fff; text-decoration: none; }
    .hero .meta { font-size: .9rem; opacity: .9; }
    .hero .selengkapnya { color: #fff; font-weight: 500; text-decoration: none; white-space: nowrap; background: rgba(255, 255, 255, .15);
                          border: 1px solid rgba(255, 255, 255, .35); border-radius: 999px; padding: .45rem 1.1rem; backdrop-filter: blur(6px);
                          transition: all var(--transisi); }
    .hero .selengkapnya:hover { background: var(--emas); border-color: var(--emas); }
    .hero .selengkapnya i { transition: transform var(--transisi); display: inline-block; }
    .hero .selengkapnya:hover i { transform: translateX(4px); }
    .hero .carousel-control-prev, .hero .carousel-control-next { width: 7%; opacity: 0; transition: opacity var(--transisi); }
    .hero:hover .carousel-control-prev, .hero:hover .carousel-control-next { opacity: .85; }
    .hero-dots { display: flex; justify-content: center; gap: .5rem; margin: 1.1rem 0 1.6rem; }
    .hero-dots button { width: 12px; height: 12px; border-radius: 999px; border: none; background: #c9d3e6; padding: 0; transition: all var(--transisi); }
    .hero-dots button.active { width: 34px; background: var(--biru); }

    /* Judul bagian */
    .judul-bagian { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
    .judul-bagian h5 { margin: 0; font-weight: 700; display: flex; align-items: center; gap: .6rem; }
    .judul-bagian h5::before { content: ''; width: 6px; height: 22px; border-radius: 3px; background: linear-gradient(var(--emas), var(--biru)); }

    /* Daftar berita terbaru */
    .daftar-berita .item { padding: 1rem; gap: 1.1rem; }
    .daftar-berita .thumb { width: 170px; height: 112px; border-radius: 12px; flex-shrink: 0; }
    .daftar-berita .thumb img, .daftar-berita .thumb-kosong { width: 100%; height: 100%; object-fit: cover; }
    .daftar-berita .thumb-kosong { background: linear-gradient(135deg, var(--biru-muda), #dbe6f8); color: #9fb3d6; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; }
    .daftar-berita h5 { font-size: 1.08rem; font-weight: 600; line-height: 1.4; margin-bottom: .35rem; }
    .daftar-berita h5 a { color: var(--teks); text-decoration: none; }
    .daftar-berita .item:hover h5 a { color: var(--biru); }
    .chip-tanggal { display: inline-flex; align-items: center; gap: .35rem; font-size: .8rem; color: var(--biru); background: var(--biru-muda); border-radius: 999px; padding: .15rem .65rem; }

    /* Member Login */
    .kotak-login { overflow: hidden; }
    .kotak-login .kepala { background: linear-gradient(120deg, var(--biru-tua), var(--biru)); color: #fff; padding: 1.4rem 1.5rem 2.6rem; text-align: center; }
    .kotak-login .kepala .ikon { width: 56px; height: 56px; border-radius: 50%; background: rgba(255, 255, 255, .18); display: inline-flex;
                                 align-items: center; justify-content: center; font-size: 1.6rem; margin-bottom: .5rem; }
    .kotak-login .kepala h4 { font-weight: 600; margin: 0; }
    .kotak-login .kepala small { opacity: .8; }
    .kotak-login .isi { background: #fff; margin: -1.6rem 1rem 1rem; border-radius: 14px; padding: 1.25rem; box-shadow: var(--bayangan); position: relative; }
    .kotak-login .input-group-text { background: var(--biru-muda); border: none; color: var(--biru); border-radius: 10px 0 0 10px; }
    .kotak-login .form-control { border: 1px solid #e3e9f3; border-left: none; border-radius: 0 10px 10px 0; padding: .6rem .85rem; }
    .kotak-login .btn { border-radius: 10px; padding: .6rem; }

    /* Pencarian */
    .kotak-cari { padding: .45rem .45rem .45rem 1.1rem; border-radius: 999px; }
    .kotak-cari:focus-within { box-shadow: 0 0 0 .25rem rgba(18, 87, 184, .15), var(--bayangan); }
    .kotak-cari input { border: none; box-shadow: none !important; background: transparent; }
    .kotak-cari .btn { border-radius: 999px; padding: .5rem 1.6rem; }

    /* Tab Berita / Kegiatan */
    .kotak-tab { padding: 1rem; }
    .kotak-tab .nav { background: var(--biru-muda); border-radius: 999px; padding: .3rem; }
    .kotak-tab .nav-link { border: none; border-radius: 999px; color: var(--abu); font-weight: 600; font-size: .85rem; text-transform: uppercase;
                           letter-spacing: .5px; width: 100%; padding: .55rem 0; transition: all var(--transisi); }
    .kotak-tab .nav-link.active { background: #fff; color: var(--biru); box-shadow: 0 2px 10px rgba(16, 42, 92, .12); }
    .kotak-tab .item { display: flex; gap: .85rem; padding: .8rem .5rem; border-radius: 12px; transition: background var(--transisi); }
    .kotak-tab .item:hover { background: var(--biru-muda); }
    .kotak-tab .item + .item { border-top: 1px solid #eef2f8; }
    .kotak-tab .thumb { width: 100px; height: 84px; border-radius: 10px; flex-shrink: 0; }
    .kotak-tab .thumb img, .kotak-tab .thumb-kosong { width: 100%; height: 100%; object-fit: cover; }
    .kotak-tab .thumb-kosong { background: linear-gradient(135deg, var(--biru-muda), #dbe6f8); color: #9fb3d6; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
    .kotak-tab .judul { font-size: .85rem; font-weight: 600; text-transform: uppercase; line-height: 1.45; color: var(--teks); text-decoration: none;
                        display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
    .kotak-tab .item:hover .judul { color: var(--biru); }
    .kotak-tab .meta { color: var(--abu); font-size: .8rem; margin-top: .4rem; }

    /* Statistik penduduk (donut). Warna seri dari palet kategori tervalidasi (slot 1 biru, slot 2 oranye). */
    .kotak-statistik { --series-1: #2a78d6; --series-2: #eb6834; --track: #edf1f7; padding: 1.25rem; }
    .donut-wrap { position: relative; max-width: 240px; margin: 0 auto .75rem; }
    .donut { width: 100%; height: auto; display: block; overflow: visible; }
    .donut-track { fill: none; stroke: var(--track); stroke-width: 26; }
    .donut-segmen { fill: none; stroke-width: 26; cursor: pointer; outline: none; transition: opacity var(--transisi), stroke-width var(--transisi); }
    .donut-segmen:hover, .donut-segmen:focus-visible { stroke-width: 30; }
    .donut-segmen.redup { opacity: .35; }
    .donut-total { text-anchor: middle; font-size: 34px; font-weight: 700; fill: var(--teks); }
    .donut-sub { text-anchor: middle; font-size: 13px; fill: var(--abu); }
    .donut-tooltip { position: absolute; pointer-events: none; transform: translate(-50%, calc(-100% - 12px)); background: #fff; color: var(--teks);
                     font-size: .8rem; line-height: 1.35; padding: .45rem .7rem; border-radius: 10px; box-shadow: var(--bayangan-hover);
                     white-space: nowrap; opacity: 0; transition: opacity .15s ease; }
    .donut-tooltip.tampak { opacity: 1; }
    .donut-legenda li { display: flex; align-items: center; gap: .6rem; padding: .5rem .6rem; border-radius: 10px; font-size: .9rem; transition: background var(--transisi); }
    .donut-legenda li + li { margin-top: .15rem; }
    .donut-legenda li.aktif { background: var(--biru-muda); }
    .donut-legenda .swatch { width: 12px; height: 12px; border-radius: 4px; flex-shrink: 0; }
    .donut-legenda .nama { flex: 1; color: var(--teks); }
    .donut-legenda .angka { font-weight: 700; color: var(--teks); }
    .donut-legenda .persen { color: var(--abu); width: 3.6rem; text-align: right; }

    @media (max-width: 767.98px) {
        .hero .carousel-item { height: 400px; } .hero .judul { font-size: 1.25rem; } .hero .caption { left: 1rem; right: 1rem; }
        .daftar-berita .thumb { width: 110px; height: 84px; }
    }
</style>

<div class="row g-4">
    <div class="col-lg-8">
        <?php if ($populer): ?>
            <div id="heroBerita" class="carousel slide carousel-fade hero muncul" data-bs-ride="carousel" data-bs-interval="6500">
                <div class="carousel-inner">
                    <?php foreach ($populer as $i => $b): $ts = strtotime($b['published_at']); ?>
                        <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                            <?php if ($b['foto']): ?>
                                <img src="<?= htmlspecialchars($b['foto']) ?>" alt="<?= htmlspecialchars($b['judul']) ?>">
                            <?php else: ?>
                                <div class="hero-kosong"></div>
                            <?php endif; ?>
                            <div class="overlay"></div>
                            <div class="caption">
                                <div class="label">
                                    <span class="utama"><i class="bi bi-fire"></i> Berita Terpopuler</span>
                                    <span>&middot; <?= htmlspecialchars(KATEGORI_BERITA[$b['kategori']] ?? 'Berita') ?></span>
                                </div>
                                <h2 class="judul"><a href="<?= linkBerita($b) ?>"><?= htmlspecialchars($b['judul']) ?></a></h2>
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                    <div class="meta d-flex flex-wrap gap-3">
                                        <span><i class="bi bi-clock"></i> <?= date('H:i:s', $ts) ?></span>
                                        <span><i class="bi bi-calendar3"></i> <?= date('Y-m-d', $ts) ?></span>
                                        <?php if ($b['penulis']): ?><span><i class="bi bi-person"></i> <?= htmlspecialchars($b['penulis']) ?></span><?php endif; ?>
                                    </div>
                                    <a href="<?= linkBerita($b) ?>" class="selengkapnya">Selengkapnya <i class="bi bi-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($populer) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#heroBerita" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon"></span><span class="visually-hidden">Sebelumnya</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#heroBerita" data-bs-slide="next">
                        <span class="carousel-control-next-icon"></span><span class="visually-hidden">Berikutnya</span>
                    </button>
                <?php endif; ?>
            </div>
            <div class="hero-dots">
                <?php foreach ($populer as $i => $b): ?>
                    <button type="button" data-bs-target="#heroBerita" data-bs-slide-to="<?= $i ?>" class="<?= $i === 0 ? 'active' : '' ?>"
                            aria-label="Berita <?= $i + 1 ?>"></button>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="hero d-flex align-items-center justify-content-center text-white-50 mb-4" style="height: 320px">
                <div class="text-center"><i class="bi bi-newspaper fs-1 d-block mb-2"></i>Belum ada berita yang diterbitkan.</div>
            </div>
        <?php endif; ?>

        <?php if ($terbaru): ?>
            <div class="judul-bagian muncul">
                <h5>Berita Terbaru</h5>
                <a href="berita.php?kategori=berita" class="fw-semibold text-decoration-none small">Lihat semua <i class="bi bi-arrow-right"></i></a>
            </div>
        <?php endif; ?>
        <div class="daftar-berita d-grid gap-3">
            <?php foreach ($terbaru as $b): ?>
                <a href="<?= linkBerita($b) ?>" class="item kartu kartu-hover d-flex text-decoration-none text-reset muncul">
                    <div class="thumb zoom">
                        <?php if ($b['foto']): ?>
                            <img src="<?= htmlspecialchars($b['foto']) ?>" alt="<?= htmlspecialchars($b['judul']) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="thumb-kosong"><i class="bi bi-image"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="min-w-0">
                        <h5><span class="text-reset"><?= htmlspecialchars($b['judul']) ?></span></h5>
                        <div class="chip-tanggal mb-2"><i class="bi bi-calendar3"></i> <?= htmlspecialchars(tanggalIndonesia($b['published_at'])) ?></div>
                        <div class="small text-secondary"><?= htmlspecialchars(ringkasanBerita($b['konten'], 150)) ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="kotak-login kartu mb-4 muncul">
            <div class="kepala">
                <div class="ikon"><i class="bi bi-person-lock"></i></div>
                <h4>Member Login</h4>
                <small>Masuk untuk mengelola data warga</small>
            </div>
            <div class="isi">
                <?php if (!empty($_SESSION['user_id'])): $u = currentUser(); ?>
                    <p class="text-center mb-3">Selamat datang, <strong><?= htmlspecialchars($u['nama_lengkap'] ?: $u['username']) ?></strong></p>
                    <a href="index.php" class="btn btn-biru w-100 mb-2"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a href="logout.php" class="btn btn-outline-secondary w-100">Keluar</a>
                <?php else: ?>
                    <?php if ($loginError): ?>
                        <div class="alert alert-danger py-2 small rounded-3"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($loginError) ?></div>
                    <?php endif; ?>
                    <form method="post" action="login.php">
                        <div class="input-group mb-3">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" name="username" class="form-control" placeholder="Username"
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autocomplete="username">
                        </div>
                        <div class="input-group mb-3">
                            <span class="input-group-text"><i class="bi bi-key"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="Password" required autocomplete="current-password">
                        </div>
                        <button type="submit" class="btn btn-biru w-100">Sign in <i class="bi bi-box-arrow-in-right"></i></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <form action="berita.php" method="get" class="kotak-cari kartu d-flex align-items-center gap-2 mb-4 muncul">
            <i class="bi bi-search fs-5 text-secondary"></i>
            <input type="text" name="q" class="form-control" placeholder="Apa yang anda cari...">
            <button type="submit" class="btn btn-emas">Cari</button>
        </form>

        <div class="kotak-tab kartu muncul">
            <ul class="nav nav-pills flex-nowrap mb-2" role="tablist">
                <?php foreach (TAB_HOME as $i => $k): ?>
                    <li class="nav-item flex-fill text-center" role="presentation">
                        <button class="nav-link <?= $i === 0 ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-<?= $k ?>" type="button" role="tab">
                            <?= KATEGORI_BERITA[$k] ?>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="tab-content">
                <?php foreach (TAB_HOME as $i => $k): ?>
                    <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="tab-<?= $k ?>" role="tabpanel">
                        <?php if (!$tab[$k]): ?>
                            <div class="text-center text-muted small py-4"><i class="bi bi-inbox fs-3 d-block mb-1"></i>Belum ada <?= strtolower(KATEGORI_BERITA[$k]) ?>.</div>
                        <?php endif; ?>
                        <?php foreach ($tab[$k] as $b): ?>
                            <a href="<?= linkBerita($b) ?>" class="item text-decoration-none">
                                <div class="thumb zoom">
                                    <?php if ($b['foto']): ?>
                                        <img src="<?= htmlspecialchars($b['foto']) ?>" alt="" loading="lazy">
                                    <?php else: ?>
                                        <div class="thumb-kosong"><i class="bi bi-image"></i></div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1 d-flex flex-column justify-content-between min-w-0">
                                    <span class="judul"><?= htmlspecialchars($b['judul']) ?></span>
                                    <div class="meta d-flex justify-content-between">
                                        <span><i class="bi bi-calendar3"></i> <?= date('Y-m-d', strtotime($b['published_at'])) ?></span>
                                        <?php if ($b['penulis']): ?><span class="text-truncate ms-2"><i class="bi bi-person"></i> <?= htmlspecialchars($b['penulis']) ?></span><?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                        <?php if ($tab[$k]): ?>
                            <div class="text-end small mt-2"><a href="berita.php?kategori=<?= $k ?>" class="fw-semibold text-decoration-none">Lihat semua <i class="bi bi-arrow-right"></i></a></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php
        // Diagram lingkaran (donut) penduduk aktif per jenis kelamin; total di tengah.
        $r = 78;
        $keliling = 2 * M_PI * $r;
        $adaIsi = array_filter($segmenJk, fn($s) => $s['jumlah'] > 0);
        $celah = count($adaIsi) > 1 ? 2.5 : 0; // celah 2px warna latar di antara irisan
        $offset = 0;
        ?>
        <div class="kotak-statistik kartu mt-4 muncul">
            <div class="judul-bagian mb-1"><h5>Statistik Penduduk</h5></div>
            <div class="small text-secondary mb-3">Penduduk aktif berdasarkan jenis kelamin</div>
            <?php if ($totalPenduduk === 0): ?>
                <div class="text-center text-muted small py-4"><i class="bi bi-pie-chart fs-3 d-block mb-1"></i>Belum ada data penduduk.</div>
            <?php else: ?>
                <div class="donut-wrap">
                    <svg viewBox="0 0 200 200" class="donut" role="img"
                         aria-label="Diagram lingkaran jumlah penduduk: <?= htmlspecialchars(implode(', ', array_map(fn($s) => $s['label'] . ' ' . $s['jumlah'], $segmenJk))) ?>, total <?= $totalPenduduk ?>">
                        <circle cx="100" cy="100" r="<?= $r ?>" class="donut-track"/>
                        <?php foreach ($segmenJk as $s): if ($s['jumlah'] === 0) continue;
                            $panjang = $keliling * $s['jumlah'] / $totalPenduduk;
                            $isi = max($panjang - $celah, 0.01);
                            $persen = round($s['jumlah'] / $totalPenduduk * 100, 1); ?>
                            <circle cx="100" cy="100" r="<?= $r ?>" class="donut-segmen" style="stroke: <?= $s['warna'] ?>"
                                    stroke-dasharray="<?= round($isi, 3) ?> <?= round($keliling - $isi, 3) ?>"
                                    stroke-dashoffset="<?= round(-$offset, 3) ?>" transform="rotate(-90 100 100)"
                                    data-label="<?= htmlspecialchars($s['label']) ?>" data-jumlah="<?= $s['jumlah'] ?>" data-persen="<?= $persen ?>"
                                    tabindex="0"/>
                        <?php $offset += $panjang; endforeach; ?>
                        <text x="100" y="98" class="donut-total"><?= number_format($totalPenduduk, 0, ',', '.') ?></text>
                        <text x="100" y="122" class="donut-sub">Penduduk</text>
                    </svg>
                    <div class="donut-tooltip" role="tooltip"></div>
                </div>
                <ul class="donut-legenda list-unstyled mb-0">
                    <?php foreach ($segmenJk as $s): $persen = round($s['jumlah'] / $totalPenduduk * 100, 1); ?>
                        <li data-label="<?= htmlspecialchars($s['label']) ?>">
                            <span class="swatch" style="background: <?= $s['warna'] ?>"></span>
                            <span class="nama"><?= htmlspecialchars($s['label']) ?></span>
                            <span class="angka"><?= number_format($s['jumlah'], 0, ',', '.') ?></span>
                            <span class="persen"><?= str_replace('.', ',', (string) $persen) ?>%</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <table class="visually-hidden">
                    <caption>Jumlah penduduk aktif berdasarkan jenis kelamin</caption>
                    <thead><tr><th>Jenis kelamin</th><th>Jumlah</th><th>Persentase</th></tr></thead>
                    <tbody>
                        <?php foreach ($segmenJk as $s): ?>
                            <tr><td><?= htmlspecialchars($s['label']) ?></td><td><?= $s['jumlah'] ?></td><td><?= round($s['jumlah'] / $totalPenduduk * 100, 1) ?>%</td></tr>
                        <?php endforeach; ?>
                        <tr><td>Total</td><td><?= $totalPenduduk ?></td><td>100%</td></tr>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Tooltip diagram lingkaran: muncul saat kursor/fokus berada di irisan atau baris legenda.
(function () {
    const wrap = document.querySelector('.donut-wrap');
    if (!wrap) return;
    const tip = wrap.querySelector('.donut-tooltip');
    const segmen = wrap.querySelectorAll('.donut-segmen');
    const legenda = document.querySelectorAll('.donut-legenda li');

    function sorot(label) {
        segmen.forEach(function (s) { s.classList.toggle('redup', label !== null && s.dataset.label !== label); });
        legenda.forEach(function (l) { l.classList.toggle('aktif', l.dataset.label === label); });
    }
    function tampil(s, x, y) {
        tip.innerHTML = '<strong>' + s.dataset.label + '</strong><br>' + Number(s.dataset.jumlah).toLocaleString('id-ID')
            + ' jiwa &middot; ' + String(s.dataset.persen).replace('.', ',') + '%';
        const box = wrap.getBoundingClientRect();
        tip.style.left = (x - box.left) + 'px';
        tip.style.top = (y - box.top) + 'px';
        tip.classList.add('tampak');
        sorot(s.dataset.label);
    }
    function sembunyi() { tip.classList.remove('tampak'); sorot(null); }

    segmen.forEach(function (s) {
        s.addEventListener('pointermove', function (e) { tampil(s, e.clientX, e.clientY); });
        s.addEventListener('pointerleave', sembunyi);
        s.addEventListener('focus', function () { const b = s.getBoundingClientRect(); tampil(s, b.left + b.width / 2, b.top + b.height / 2); });
        s.addEventListener('blur', sembunyi);
    });
    legenda.forEach(function (l) {
        l.addEventListener('pointerenter', function () { sorot(l.dataset.label); });
        l.addEventListener('pointerleave', function () { sorot(null); });
    });
})();
</script>

<script>
// Titik penanda slider berada di luar carousel, jadi status aktifnya diperbarui manual.
document.getElementById('heroBerita')?.addEventListener('slide.bs.carousel', function (e) {
    document.querySelectorAll('.hero-dots button').forEach(function (b, i) { b.classList.toggle('active', i === e.to); });
});
</script>

<?php require __DIR__ . '/includes/publik_footer.php'; ?>
