</main>
<footer class="publik-footer mt-4">
    <div class="container-fluid px-3 px-lg-4 py-4 d-flex flex-wrap justify-content-between align-items-center gap-2 small">
        <div class="d-flex align-items-center gap-2">
            <img src="assets/img/logo-kabupaten-bogor.png" alt="" height="28">
            <span>&copy; <?= date('Y') ?> Aplikasi Penduduk</span>
        </div>
        <div><a href="home.php" class="text-decoration-none me-3">Home</a><a href="berita.php" class="text-decoration-none">Berita</a></div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Navbar mengecil saat halaman di-scroll.
(function () {
    const nav = document.getElementById('navPublikBar');
    const atur = function () { nav.classList.toggle('mengecil', window.scrollY > 10); };
    window.addEventListener('scroll', atur, { passive: true });
    atur();
})();

// Elemen .muncul tampil perlahan saat masuk layar.
(function () {
    const el = document.querySelectorAll('.muncul');
    if (!('IntersectionObserver' in window)) { el.forEach(function (e) { e.classList.add('tampak'); }); return; }
    const io = new IntersectionObserver(function (entri) {
        entri.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('tampak'); io.unobserve(e.target); } });
    }, { threshold: .12 });
    el.forEach(function (e, i) { e.style.transitionDelay = Math.min(i % 6, 5) * 60 + 'ms'; io.observe(e); });
})();
</script>
</body>
</html>
