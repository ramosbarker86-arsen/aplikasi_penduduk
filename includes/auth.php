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

// Hak akses menu per pengguna: kolom enum('ya','tidak') di tabel users.
const AKSES_MENU = [
    'data_penduduk' => 'Data Penduduk',
    'surat_keterangan' => 'Surat Keterangan',
    'kegiatan' => 'Kegiatan',
    'berita' => 'Berita',
    'master_wilayah' => 'Master Wilayah',
    'pengguna' => 'Pengguna',
];

// Hak akses pengguna yang sedang login, dibaca dari database (bukan session) supaya
// perubahan langsung berlaku. Berlaku juga untuk level admin; fitur khusus admin
// (hapus, edit penduduk, data lengkap tanpa disamarkan) tetap ditentukan oleh level.
function aksesSaya(): array
{
    static $akses = null;
    if ($akses === null) {
        $akses = array_fill_keys(array_keys(AKSES_MENU), false);
        if (!empty($_SESSION['user_id'])) {
            $stmt = getDB()->prepare('SELECT ' . implode(', ', array_keys(AKSES_MENU)) . ' FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $baris = $stmt->fetch() ?: [];
            foreach ($akses as $menu => $_) {
                $akses[$menu] = ($baris[$menu] ?? 'tidak') === 'ya';
            }
        }
    }
    return $akses;
}

function punyaAkses(string $menu): bool
{
    return aksesSaya()[$menu] ?? false;
}

// Hak akses dari form pengguna (checkbox name="akses[menu]" value="ya").
//   $lama  nilai tersimpan (pengguna baru: default kolom tabel)
//   $kunci true jika mengedit akun sendiri -> hak akses tidak bisa diubah (supaya tidak terkunci)
// Pengguna (termasuk admin) hanya boleh memberi/mencabut akses menu yang ia miliki sendiri; menu lain tetap nilai lama.
function aksesDariForm(array $post, array $lama, bool $kunci): array
{
    $hasil = [];
    foreach (AKSES_MENU as $menu => $_) {
        if ($kunci || !punyaAkses($menu)) {
            $hasil[$menu] = ($lama[$menu] ?? 'tidak') === 'ya' ? 'ya' : 'tidak';
        } else {
            $hasil[$menu] = (($post['akses'][$menu] ?? '') === 'ya') ? 'ya' : 'tidak';
        }
    }
    return $hasil;
}

// Nilai default hak akses untuk pengguna baru (sama dengan default kolom di tabel users).
const AKSES_DEFAULT = [
    'data_penduduk' => 'ya', 'surat_keterangan' => 'ya', 'kegiatan' => 'ya',
    'berita' => 'tidak', 'master_wilayah' => 'tidak', 'pengguna' => 'tidak',
];

function requireAkses(string $menu)
{
    requireLogin();
    if (!punyaAkses($menu)) {
        $_SESSION['flash_error'] = 'Anda tidak memiliki akses ke menu ' . (AKSES_MENU[$menu] ?? $menu) . '.';
        header('Location: ' . basePath() . 'index.php');
        exit;
    }
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

// Tanggal lahir lengkap hanya untuk admin; selain admin hanya tahunnya yang terlihat: 17-05-1990 -> **-**-1990.
// $format = cara menampilkan untuk admin (default d-m-Y), misalnya 'tanggalIndonesia'.
function tampilTanggalLahir($tanggal, ?callable $format = null)
{
    $tanggal = (string) $tanggal;
    $ts = $tanggal !== '' ? strtotime($tanggal) : false;
    if ($ts === false) {
        return '';
    }
    if (!isAdmin()) {
        return '**-**-' . date('Y', $ts);
    }
    return $format ? $format($tanggal) : date('d-m-Y', $ts);
}

// Email lengkap hanya untuk admin. Selain admin, nama sebelum @ disamarkan:
// edi.wibowo@gmail.com -> ed********@gmail.com (nama 1-2 huruf jadi ** seluruhnya).
function tampilEmail($email)
{
    $email = (string) $email;
    $at = strrpos($email, '@');
    if (isAdmin() || $email === '' || $at === false) {
        return $email;
    }

    $nama = substr($email, 0, $at);
    $terlihat = strlen($nama) > 2 ? 2 : 0;
    return substr($nama, 0, $terlihat) . str_repeat('*', max(strlen($nama) - $terlihat, 2)) . substr($email, $at);
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
// $ikutiSubLevelAdmin = true: admin yang punya sub_level & wilayah juga dibatasi lingkupnya
// (dipakai laporan, supaya kop "Semua Wilayah RW 09" sesuai isinya). Admin tanpa sub_level tetap semua.
function filterWilayahPenduduk(string $kolom = 'p.id_wilayah', bool $ikutiSubLevelAdmin = false): string
{
    static $user = null;
    if ($user === null) {
        $user = ['sub_level' => null, 'id_wilayah' => null];
        if (!empty($_SESSION['user_id'])) {
            $stmt = getDB()->prepare('SELECT sub_level, id_wilayah FROM users WHERE id = ?');
            $stmt->execute([currentUser()['id']]);
            $user = $stmt->fetch() ?: $user;
        }
    }

    if (isAdmin()) {
        if (!$ikutiSubLevelAdmin || !$user['sub_level'] || !$user['id_wilayah']) {
            return '1=1';
        }
    }

    return sqlLingkupWilayah($user['sub_level'], (int) $user['id_wilayah'], $kolom);
}

// Kondisi SQL lingkup wilayah untuk sub_level (rt/rw/kelurahan) dengan wilayah acuan $idWilayah.
function sqlLingkupWilayah(?string $subLevel, int $idWilayah, string $kolom): string
{
    if (!$idWilayah || !$subLevel) {
        return '1=0';
    }

    $samaKelurahan = 'w2.kelurahan = wu.kelurahan AND w2.kecamatan = wu.kecamatan
                      AND w2.Kabupaten = wu.Kabupaten AND w2.propinsi = wu.propinsi';
    switch ($subLevel) {
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
