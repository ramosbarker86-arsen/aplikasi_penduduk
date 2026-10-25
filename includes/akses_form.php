<?php
// Bagian "Hak Akses Menu" di form pengguna (users/tambah.php, users/edit.php).
// Variabel yang diharapkan: $akses (menu => 'ya'|'tidak'), $form['level'], $kunciAkses (bool: akun sendiri).
// Hak akses berlaku untuk semua level, termasuk admin.
?>
<div class="col-12">
    <label class="form-label mb-1">Hak Akses Menu</label>
    <div class="form-text mt-0 mb-2">
        <?php if ($kunciAkses): ?>
            <i class="bi bi-lock"></i> Ini akun Anda sendiri: hak akses tidak bisa diubah dari sini.
        <?php else: ?>
            Centang menu yang boleh dibuka pengguna ini (berlaku juga untuk level Admin).
            Anda hanya bisa mengatur menu yang Anda miliki sendiri.
        <?php endif; ?>
    </div>
    <div class="row g-2">
        <?php foreach (AKSES_MENU as $menu => $label):
            $bolehUbah = !$kunciAkses && punyaAkses($menu); ?>
            <div class="col-sm-6 col-lg-4">
                <div class="form-check form-switch border rounded px-5 py-2">
                    <input class="form-check-input cek-akses" type="checkbox" role="switch" name="akses[<?= $menu ?>]" value="ya"
                           id="akses-<?= $menu ?>" <?= $akses[$menu] === 'ya' ? 'checked' : '' ?> <?= $bolehUbah ? '' : 'disabled' ?>>
                    <label class="form-check-label" for="akses-<?= $menu ?>"><?= htmlspecialchars($label) ?></label>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<script>
// Saat level diganti menjadi Admin, semua menu yang boleh diatur dicentang sebagai titik awal (tetap bisa diubah).
(function () {
    const level = document.querySelector('select[name="level"]');
    if (!level) return;
    let sebelumnya = level.value;
    level.addEventListener('change', function () {
        if (level.value === 'admin' && sebelumnya !== 'admin') {
            document.querySelectorAll('.cek-akses:not(:disabled)').forEach(function (c) { c.checked = true; });
        }
        sebelumnya = level.value;
    });
})();
</script>
