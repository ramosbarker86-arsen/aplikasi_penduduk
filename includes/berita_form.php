<?php
// Form berita/kegiatan, dipakai oleh includes/konten_tambah.php dan konten_edit.php. Variabel yang diharapkan: $modul (MODUL_BERITA /
// MODUL_KEGIATAN), $form (judul, konten, kategori, id_wilayah, status, keterangan_lama, hapus_foto, foto_utama,
// published_at untuk edit), $errors, $fotoLama, $wilayahList (modul berwilayah), $tombolSimpan.
?>
<link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">
<style>
    #editorKonten { min-height: 360px; font-size: 1rem; background: #fff; }
    .ql-toolbar.ql-snow { border-radius: .375rem .375rem 0 0; background: #f8f9fa; }
    .ql-container.ql-snow { border-radius: 0 0 .375rem .375rem; }
    .foto-item { border: 1px solid #dee2e6; border-radius: .5rem; padding: .75rem; background: #fff; }
    .foto-item img { width: 100%; height: 130px; object-fit: cover; border-radius: .375rem; background: #e9ecef; }
    .foto-item.akan-dihapus { opacity: .5; }
    .foto-item.akan-dihapus img { filter: grayscale(1); }
</style>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" id="formBerita">
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <label class="form-label">Judul <?= htmlspecialchars($modul['nama']) ?> <span class="text-danger">*</span></label>
                    <input type="text" name="judul" class="form-control form-control-lg" maxlength="255"
                           placeholder="Tambahkan judul" value="<?= htmlspecialchars($form['judul']) ?>" required>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-body">
                    <label class="form-label">Konten <?= htmlspecialchars($modul['nama']) ?> <span class="text-danger">*</span></label>
                    <div id="editorKonten"><?= saringKontenBerita($form['konten']) ?></div>
                    <input type="hidden" name="konten" id="inputKonten">
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                        <div>
                            <label class="form-label mb-0">Gambar &amp; Keterangan</label>
                            <div class="form-text mt-0">
                                Maksimal <?= MAKS_FOTO_BERITA ?> gambar, jpg/png/webp, masing-masing maks. 3MB.
                                Foto utama tampil di daftar dan di bagian atas halaman.
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="tombolTambahGambar">
                            <i class="bi bi-images"></i> Tambah Gambar
                        </button>
                        <input type="file" id="pilihGambar" class="d-none" accept=".jpg,.jpeg,.png,.webp" multiple>
                    </div>

                    <div id="daftarFoto" class="d-grid gap-2">
                        <?php foreach ($fotoLama as $f): $fid = (int) $f['id']; $dihapus = !empty($form['hapus_foto'][$fid]); ?>
                            <div class="foto-item <?= $dihapus ? 'akan-dihapus' : '' ?>" data-foto>
                                <div class="row g-3">
                                    <div class="col-sm-4">
                                        <img src="../<?= htmlspecialchars($f['file']) ?>" alt="">
                                    </div>
                                    <div class="col-sm-8">
                                        <textarea name="keterangan_lama[<?= $fid ?>]" class="form-control mb-2" rows="3" maxlength="500"
                                                  placeholder="Narasi / keterangan gambar..."><?= htmlspecialchars((string) ($form['keterangan_lama'][$fid] ?? '')) ?></textarea>
                                        <div class="d-flex gap-3 flex-wrap">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="foto_utama" value="lama-<?= $fid ?>" id="utama-lama-<?= $fid ?>"
                                                    <?= $form['foto_utama'] === 'lama-' . $fid ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="utama-lama-<?= $fid ?>">Foto utama</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input cek-hapus" type="checkbox" name="hapus_foto[<?= $fid ?>]" value="1" id="hapus-<?= $fid ?>"
                                                    <?= $dihapus ? 'checked' : '' ?>>
                                                <label class="form-check-label text-danger" for="hapus-<?= $fid ?>">Hapus gambar</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div id="kosongFoto" class="text-muted small text-center py-3 <?= $fotoLama ? 'd-none' : '' ?>">
                        <i class="bi bi-image fs-3 d-block"></i> Belum ada gambar.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3 position-sticky" style="top: 80px">
                <div class="card-body">
                    <?php if (count($modul['kategori']) > 1): ?>
                        <label class="form-label">Kategori</label>
                        <select name="kategori" class="form-select mb-3">
                            <?php foreach ($modul['kategori'] as $value => $label): ?>
                                <option value="<?= $value ?>" <?= $form['kategori'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="hidden" name="kategori" value="<?= htmlspecialchars(array_key_first($modul['kategori'])) ?>">
                    <?php endif; ?>
                    <?php if ($modul['wilayah']): ?>
                        <label class="form-label">Wilayah (RT/RW) <span class="text-danger">*</span></label>
                        <select name="id_wilayah" class="form-select mb-3" required>
                            <option value="">-- Pilih Wilayah --</option>
                            <?php foreach ($wilayahList as $w): ?>
                                <option value="<?= (int) $w['id_wilayah'] ?>" <?= (string) $form['id_wilayah'] === (string) $w['id_wilayah'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(labelWilayah($w)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select mb-3">
                        <option value="draft" <?= $form['status'] === 'draft' ? 'selected' : '' ?>>Draft (belum tampil di publik)</option>
                        <option value="publish" <?= $form['status'] === 'publish' ? 'selected' : '' ?>>Terbitkan</option>
                    </select>
                    <?php if (array_key_exists('published_at', $form)): ?>
                        <label class="form-label">Tanggal Terbit</label>
                        <input type="datetime-local" name="published_at" class="form-control" step="1"
                               value="<?= $form['published_at'] ? htmlspecialchars(date('Y-m-d\TH:i:s', strtotime($form['published_at']))) : '' ?>">
                        <div class="form-text mb-3">Kosongkan untuk memakai waktu saat diterbitkan.</div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-save"></i> <?= htmlspecialchars($tombolSimpan) ?></button>
                    <a href="list.php" class="btn btn-outline-secondary w-100 mt-2">Batal</a>
                </div>
            </div>
        </div>
    </div>
</form>

<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
<script>
(function () {
    const MAKS = <?= MAKS_FOTO_BERITA ?>;
    const quill = new Quill('#editorKonten', {
        theme: 'snow',
        placeholder: 'Tulis narasi <?= strtolower($modul['nama']) ?> di sini...',
        modules: {
            toolbar: [
                [{ header: [2, 3, 4, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                [{ align: [] }],
                ['blockquote', 'link'],
                ['clean']
            ]
        }
    });

    const daftar = document.getElementById('daftarFoto');
    const kosong = document.getElementById('kosongFoto');
    const pilih = document.getElementById('pilihGambar');
    let nomorBaru = 0;

    function jumlahFotoAktif() {
        return daftar.querySelectorAll('[data-foto]:not(.akan-dihapus)').length;
    }

    function rapikan() {
        kosong.classList.toggle('d-none', daftar.querySelector('[data-foto]') !== null);
        // Pastikan selalu ada satu foto utama yang dipilih (dan bukan foto yang akan dihapus).
        const terpilih = daftar.querySelector('input[name="foto_utama"]:checked');
        if (!terpilih || terpilih.closest('.akan-dihapus')) {
            const pertama = daftar.querySelector('[data-foto]:not(.akan-dihapus) input[name="foto_utama"]');
            if (pertama) pertama.checked = true;
        }
    }

    document.getElementById('tombolTambahGambar').addEventListener('click', function () { pilih.click(); });

    pilih.addEventListener('change', function () {
        const files = Array.from(this.files);
        const sisa = MAKS - jumlahFotoAktif();
        if (files.length > sisa) {
            alert('Maksimal ' + MAKS + ' gambar per berita. Hanya ' + Math.max(sisa, 0) + ' gambar pertama yang ditambahkan.');
        }
        files.slice(0, Math.max(sisa, 0)).forEach(function (file) {
            const n = nomorBaru++;
            const item = document.createElement('div');
            item.className = 'foto-item';
            item.setAttribute('data-foto', '');
            item.innerHTML =
                '<div class="row g-3">' +
                  '<div class="col-sm-4"><img alt=""><div class="small text-muted mt-1 text-truncate"></div></div>' +
                  '<div class="col-sm-8">' +
                    '<textarea name="keterangan_baru[' + n + ']" class="form-control mb-2" rows="3" maxlength="500" placeholder="Narasi / keterangan gambar..."></textarea>' +
                    '<div class="d-flex gap-3 flex-wrap align-items-center">' +
                      '<div class="form-check"><input class="form-check-input" type="radio" name="foto_utama" value="baru-' + n + '" id="utama-baru-' + n + '">' +
                      '<label class="form-check-label" for="utama-baru-' + n + '">Foto utama</label></div>' +
                      '<button type="button" class="btn btn-link btn-sm text-danger p-0 hapus-baru"><i class="bi bi-x-circle"></i> Batal tambahkan</button>' +
                      '<span class="badge bg-info-subtle text-info-emphasis">Baru</span>' +
                    '</div>' +
                  '</div>' +
                '</div>';

            // Setiap gambar punya input file sendiri supaya keterangannya tetap berpasangan.
            const input = document.createElement('input');
            input.type = 'file';
            input.name = 'foto_baru[' + n + ']';
            input.className = 'd-none';
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            item.appendChild(input);

            item.querySelector('img').src = URL.createObjectURL(file);
            item.querySelector('.text-muted').textContent = file.name;
            item.querySelector('.hapus-baru').addEventListener('click', function () { item.remove(); rapikan(); });
            daftar.appendChild(item);
        });
        this.value = '';
        rapikan();
    });

    daftar.addEventListener('change', function (e) {
        if (e.target.classList.contains('cek-hapus')) {
            e.target.closest('[data-foto]').classList.toggle('akan-dihapus', e.target.checked);
            rapikan();
        }
    });

    document.getElementById('formBerita').addEventListener('submit', function (e) {
        if (quill.getText().trim() === '') {
            e.preventDefault();
            alert('Konten berita wajib diisi.');
            quill.focus();
            return;
        }
        document.getElementById('inputKonten').value = quill.root.innerHTML;
    });

    rapikan();
})();
</script>
