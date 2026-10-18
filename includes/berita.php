<?php
// Helper untuk modul berita (berita/ untuk admin, berita.php & berita_baca.php untuk publik).

// Saring HTML dari editor supaya hanya tag & atribut aman yang tersisa (mencegah XSS di halaman publik).
// Tag lain dibuka (isinya dipertahankan); script/style/iframe dan sejenisnya dibuang beserta isinya.
function saringKontenBerita(string $html): string
{
    $izinTag = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'h2', 'h3', 'h4',
                'ul', 'ol', 'li', 'blockquote', 'a', 'span'];
    $buangTag = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button',
                 'textarea', 'select', 'link', 'meta', 'svg', 'math', 'img', 'video', 'audio'];

    if (trim($html) === '') {
        return '';
    }

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="akar">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $akar = $doc->getElementById('akar');
    if (!$akar) {
        return '';
    }

    $saring = function (DOMNode $node) use (&$saring, $izinTag, $buangTag) {
        // Salin daftar anak dulu karena struktur diubah selama perulangan.
        foreach (iterator_to_array($node->childNodes) as $anak) {
            if ($anak instanceof DOMComment || $anak instanceof DOMProcessingInstruction) {
                $node->removeChild($anak);
                continue;
            }
            if (!($anak instanceof DOMElement)) {
                continue;
            }

            $tag = strtolower($anak->nodeName);
            if (in_array($tag, $buangTag, true)) {
                $node->removeChild($anak);
                continue;
            }

            $saring($anak);

            if (!in_array($tag, $izinTag, true)) {
                while ($anak->firstChild) {
                    $node->insertBefore($anak->firstChild, $anak);
                }
                $node->removeChild($anak);
                continue;
            }

            foreach (iterator_to_array($anak->attributes) as $attr) {
                $nama = strtolower($attr->name);
                $nilai = trim($attr->value);
                $boleh = ($nama === 'class' && preg_match('/^(ql-[a-z0-9-]+\s*)+$/', $nilai))
                      || ($tag === 'a' && $nama === 'href' && preg_match('~^(https?://|mailto:|/|#)~i', $nilai));
                if (!$boleh) {
                    $anak->removeAttribute($attr->name);
                }
            }
            if ($tag === 'a' && $anak->hasAttribute('href')) {
                $anak->setAttribute('target', '_blank');
                $anak->setAttribute('rel', 'noopener noreferrer');
            }
        }
    };
    $saring($akar);

    $hasil = '';
    foreach ($akar->childNodes as $anak) {
        $hasil .= $doc->saveHTML($anak);
    }
    return trim($hasil);
}

// Konten dianggap kosong jika setelah tag dibuang tidak ada teks (editor kosong menghasilkan "<p><br></p>").
function kontenBeritaKosong(string $html): bool
{
    return trim(html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8'), " \t\n\r\0\x0B\xC2\xA0") === '';
}

// Slug unik dari judul, contoh "Kerja Bakti RT 03" -> "kerja-bakti-rt-03" (-2, -3, ... jika sudah dipakai).
function buatSlugBerita(PDO $pdo, string $judul, int $kecualiId = 0): string
{
    $dasar = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $judul)), '-'));
    $dasar = substr($dasar ?: 'berita', 0, 180);

    $cek = $pdo->prepare('SELECT COUNT(*) FROM berita WHERE slug = ? AND id <> ?');
    $slug = $dasar;
    for ($i = 2; ; $i++) {
        $cek->execute([$slug, $kecualiId]);
        if (!$cek->fetchColumn()) {
            return $slug;
        }
        $slug = $dasar . '-' . $i;
    }
}

// Kategori berita; Pengumuman & Kegiatan tampil sebagai tab di halaman Home.
const KATEGORI_BERITA = ['berita' => 'Berita', 'pengumuman' => 'Pengumuman', 'kegiatan' => 'Kegiatan'];

// Pengaturan menu yang memakai tabel berita (lihat includes/konten_*.php):
//   berita/   -> admin, kategori Berita & Pengumuman
//   kegiatan/ -> semua pengguna login, kategori Kegiatan + wilayah sesuai lingkup pengguna
const MODUL_BERITA = ['nama' => 'Berita', 'ikon' => 'bi-newspaper', 'kategori' => ['berita' => 'Berita', 'pengumuman' => 'Pengumuman'], 'wilayah' => false];
const MODUL_KEGIATAN = ['nama' => 'Kegiatan', 'ikon' => 'bi-calendar-event', 'kategori' => ['kegiatan' => 'Kegiatan'], 'wilayah' => true, 'cetak' => true];

// Kondisi SQL: baris berita (alias b) termasuk modul ini dan, untuk modul berwilayah, dalam lingkup pengguna.
function kondisiModulKonten(PDO $pdo, array $modul): string
{
    $kategori = implode(',', array_map([$pdo, 'quote'], array_keys($modul['kategori'])));
    return "b.kategori IN ($kategori)" . ($modul['wilayah'] ? ' AND ' . filterWilayahPenduduk('b.id_wilayah') : '');
}

// Wilayah aktif dalam lingkup pengguna (admin: semua), plus $termasukId supaya wilayah lama tetap bisa dipilih.
function daftarWilayahKonten(PDO $pdo, ?int $termasukId = null): array
{
    $stmt = $pdo->prepare("SELECT id_wilayah, propinsi, Kabupaten, kecamatan, kelurahan, rw, rt FROM wilayah
                           WHERE (status_cd = 'active' AND " . filterWilayahPenduduk('id_wilayah') . ") OR id_wilayah = ?
                           ORDER BY kelurahan ASC, rw ASC, rt ASC");
    $stmt->execute([(int) $termasukId]);
    return $stmt->fetchAll();
}

function labelWilayah(array $w): string
{
    return 'RT ' . $w['rt'] . '/RW ' . $w['rw'] . ' - ' . $w['kelurahan'] . ', ' . $w['kecamatan'] . ', ' . $w['Kabupaten'];
}

// Batas foto per berita (PHP XAMPP mengizinkan max_file_uploads = 20 per kiriman form).
const MAKS_FOTO_BERITA = 10;

// Foto-foto sebuah berita, urut tampil (foto pertama = foto utama).
function daftarFotoBerita(PDO $pdo, int $beritaId): array
{
    $stmt = $pdo->prepare('SELECT id, file, keterangan, urutan FROM berita_foto WHERE berita_id = ? ORDER BY urutan ASC, id ASC');
    $stmt->execute([$beritaId]);
    return $stmt->fetchAll();
}

// Periksa semua file baru di $_FILES[$field] (input name="foto_baru[n]") tanpa menyimpannya.
// Mengembalikan [n => ['tmp' => ..., 'ext' => ...]]; baris tanpa file dilewati.
function periksaUploadFotoBerita(string $field): array
{
    $hasil = [];
    if (empty($_FILES[$field]['name']) || !is_array($_FILES[$field]['name'])) {
        return $hasil;
    }

    $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
    foreach ($_FILES[$field]['name'] as $n => $nama) {
        $error = $_FILES[$field]['error'][$n];
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $label = '"' . $nama . '"';
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Gagal mengunggah gambar ' . $label . '.');
        }
        $ext = strtolower(pathinfo($nama, PATHINFO_EXTENSION));
        $tmp = $_FILES[$field]['tmp_name'][$n];
        if (!in_array($ext, $allowedExt, true) || @getimagesize($tmp) === false) {
            throw new RuntimeException('Gambar ' . $label . ' harus berupa jpg, jpeg, png, atau webp.');
        }
        if ($_FILES[$field]['size'][$n] > 3 * 1024 * 1024) {
            throw new RuntimeException('Ukuran gambar ' . $label . ' maksimal 3MB.');
        }
        $hasil[(int) $n] = ['tmp' => $tmp, 'ext' => $ext];
    }
    return $hasil;
}

// Simpan perubahan galeri foto sebuah berita dari form (dipanggil di dalam transaksi):
//   $fotoLama            foto yang sudah ada (daftarFotoBerita)
//   $fotoBaru            hasil periksaUploadFotoBerita
//   $input['keterangan_lama'][id], $input['hapus_foto'][id], $input['keterangan_baru'][n],
//   $input['foto_utama'] = 'lama-<id>' atau 'baru-<n>'
// Mengembalikan [file yang perlu dihapus dari disk, file baru yang sudah dipindah].
// Hapus file lama baru setelah commit; jika transaksi gagal, hapus file baru.
function simpanFotoBerita(PDO $pdo, int $beritaId, array $fotoLama, array $fotoBaru, array $input): array
{
    $ket = function ($v) {
        $v = trim((string) $v);
        return $v === '' ? null : mb_substr($v, 0, 500);
    };

    $hapusFile = [];
    $fileBaru = [];
    $urutan = [];

    $updateKet = $pdo->prepare('UPDATE berita_foto SET keterangan = ? WHERE id = ? AND berita_id = ?');
    $hapus = $pdo->prepare('DELETE FROM berita_foto WHERE id = ? AND berita_id = ?');
    foreach ($fotoLama as $f) {
        if (!empty($input['hapus_foto'][$f['id']])) {
            $hapus->execute([$f['id'], $beritaId]);
            $hapusFile[] = $f['file'];
            continue;
        }
        $updateKet->execute([$ket($input['keterangan_lama'][$f['id']] ?? $f['keterangan']), $f['id'], $beritaId]);
        $urutan['lama-' . $f['id']] = (int) $f['id'];
    }

    $destDir = __DIR__ . '/../uploads/berita/';
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    $tambah = $pdo->prepare('INSERT INTO berita_foto (berita_id, file, keterangan, urutan) VALUES (?,?,?,0)');
    foreach ($fotoBaru as $n => $f) {
        $path = 'uploads/berita/berita_' . uniqid() . '_' . $n . '.' . $f['ext'];
        if (!move_uploaded_file($f['tmp'], __DIR__ . '/../' . $path)) {
            throw new RuntimeException('Gagal menyimpan gambar ke server.');
        }
        $fileBaru[] = $path;
        $tambah->execute([$beritaId, $path, $ket($input['keterangan_baru'][$n] ?? '')]);
        $urutan['baru-' . $n] = (int) $pdo->lastInsertId();
    }

    // Foto utama dipindah ke depan; sisanya mengikuti urutan lama lalu foto baru.
    $utama = (string) ($input['foto_utama'] ?? '');
    if (isset($urutan[$utama])) {
        $urutan = [$utama => $urutan[$utama]] + $urutan;
    }
    $setUrutan = $pdo->prepare('UPDATE berita_foto SET urutan = ? WHERE id = ? AND berita_id = ?');
    $i = 0;
    foreach ($urutan as $id) {
        $setUrutan->execute([$i++, $id, $beritaId]);
    }

    return [$hapusFile, $fileBaru];
}

function hapusFileBerita(?string $path): void
{
    if ($path && strpos($path, 'uploads/berita/') === 0) {
        $full = __DIR__ . '/../' . $path;
        if (is_file($full)) unlink($full);
    }
}

// Potongan teks polos untuk daftar berita.
function ringkasanBerita(string $html, int $panjang = 180): string
{
    $teks = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace('<', ' <', $html)), ENT_QUOTES, 'UTF-8')));
    return mb_strlen($teks) > $panjang ? rtrim(mb_substr($teks, 0, $panjang)) . '…' : $teks;
}
