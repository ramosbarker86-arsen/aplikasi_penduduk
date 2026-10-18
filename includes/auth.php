<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin()
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . basePath() . 'login.php');
        exit;
    }
}

function currentUser()
{
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? '',
        'nama_lengkap' => $_SESSION['nama_lengkap'] ?? '',
        'level' => $_SESSION['level'] ?? 'user',
    ];
}

function isAdmin()
{
    return ($_SESSION['level'] ?? '') === 'admin';
}

// NIK dan No. KK lengkap hanya untuk admin. Selain admin, bagian tengahnya disamarkan dengan *,
// contoh 3201234567890001 -> 320123******0001.
function tampilNik($nik)
{
    return samarkanNomor($nik);
}

function tampilNoKk($noKk)
{
    return samarkanNomor($noKk);
}

function samarkanNomor($nomor)
{
    $nomor = (string) $nomor;
    if (isAdmin() || $nomor === '') {
        return $nomor;
    }

    $len = strlen($nomor);
    if ($len >= 12) {
        [$depan, $belakang] = [6, 4];
    } elseif ($len > 4) {
        [$depan, $belakang] = [2, 2];
    } else {
        return str_repeat('*', $len);
    }

    return substr($nomor, 0, $depan) . str_repeat('*', $len - $depan - $belakang) . substr($nomor, -$belakang);
}

// Kondisi SQL lingkup wilayah penduduk untuk pengguna yang sedang login.
// Admin melihat semua. Selain admin, dibatasi menurut sub_level dan id_wilayah di tabel users:
//   rt        -> hanya wilayah (RT) miliknya
//   rw        -> semua RT dalam RW yang sama (kelurahan, kecamatan, kabupaten, propinsi sama)
//   kelurahan -> semua RT/RW dalam kelurahan yang sama
// Tanpa sub_level atau wilayah, pengguna tidak melihat data apa pun.
// $kolom = kolom id_wilayah yang difilter, misalnya 'p.id_wilayah'. Data diambil dari database
// (bukan session) supaya perubahan sub_level/wilayah oleh admin langsung berlaku.
function filterWilayahPenduduk(string $kolom = 'p.id_wilayah'): string
{
    if (isAdmin()) {
        return '1=1';
    }

    static $user = null;
    if ($user === null) {
        $stmt = getDB()->prepare('SELECT sub_level, id_wilayah FROM users WHERE id = ?');
        $stmt->execute([currentUser()['id']]);
        $user = $stmt->fetch() ?: ['sub_level' => null, 'id_wilayah' => null];
    }

    $idWilayah = (int) $user['id_wilayah'];
    if (!$idWilayah || !$user['sub_level']) {
        return '1=0';
    }

    $samaKelurahan = 'w2.kelurahan = wu.kelurahan AND w2.kecamatan = wu.kecamatan
                      AND w2.Kabupaten = wu.Kabupaten AND w2.propinsi = wu.propinsi';
    switch ($user['sub_level']) {
        case 'rt':
            return "$kolom = $idWilayah";
        case 'rw':
            return "$kolom IN (SELECT w2.id_wilayah FROM wilayah w2 JOIN wilayah wu ON wu.id_wilayah = $idWilayah
                              WHERE w2.rw = wu.rw AND $samaKelurahan)";
        case 'kelurahan':
            return "$kolom IN (SELECT w2.id_wilayah FROM wilayah w2 JOIN wilayah wu ON wu.id_wilayah = $idWilayah
                              WHERE $samaKelurahan)";
        default:
            return '1=0';
    }
}

function requireAdmin()
{
    requireLogin();
    if (!isAdmin()) {
        $_SESSION['flash_error'] = 'Halaman ini hanya bisa diakses oleh admin.';
        header('Location: ' . basePath() . 'index.php');
        exit;
    }
}

// Mengembalikan path relatif ke root aplikasi, supaya link konsisten baik diakses
// dari root (index.php) maupun dari dalam subfolder (penduduk/, wilayah/, users/, surat/, berita/, kegiatan/).
function basePath()
{
    $script = $_SERVER['SCRIPT_NAME'];
    foreach (['/penduduk/', '/wilayah/', '/users/', '/surat/', '/berita/', '/kegiatan/'] as $dir) {
        if (strpos($script, $dir) !== false) {
            return '../';
        }
    }
    return '';
}
