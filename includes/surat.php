<?php
// Helper untuk modul surat keterangan (surat/tambah.php, surat/cetak.php, dll).

// Urutan mengikuti format kertas: kolom kiri dulu, lalu kolom kanan.
const KEPERLUAN_SURAT = [
    'Pengantar KTP', 'Pengantar KK', 'Pengantar Untuk Nikah',
    'Keterangan Kelahiran', 'Keterangan Kematian', 'Keterangan Usaha',
    'Ijin Keramaian', 'Pengantar SKCK', 'Keterangan Tidak Mampu',
    'Keterangan Domisili Usaha', 'Keterangan Domisili', 'Keterangan Lainnya',
];

function bulanRomawi(int $bulan): string
{
    return ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$bulan - 1];
}

function tanggalIndonesia(string $tanggal): string
{
    $bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
              'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $ts = strtotime($tanggal);
    return date('j', $ts) . ' ' . $bulan[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
}

// Nomor surat berikutnya untuk bulan dari $tanggal: no urut/bulan romawi/tahun, contoh 001/X/2026.
// Nomor urut mulai lagi dari 1 setiap bulan. Saat menyimpan, panggil di dalam transaksi
// dengan $lock = true supaya dua penyimpanan bersamaan tidak mendapat nomor yang sama.
function nomorSuratBerikut(PDO $pdo, string $tanggal, bool $lock = false): array
{
    $ts = strtotime($tanggal);
    $bulan = (int) date('n', $ts);
    $tahun = (int) date('Y', $ts);

    $stmt = $pdo->prepare('SELECT COALESCE(MAX(no_urut), 0) FROM surat_keterangan WHERE tahun = ? AND bulan = ?' . ($lock ? ' FOR UPDATE' : ''));
    $stmt->execute([$tahun, $bulan]);
    $noUrut = (int) $stmt->fetchColumn() + 1;

    return [
        'no_urut' => $noUrut,
        'bulan' => $bulan,
        'tahun' => $tahun,
        'nomor_surat' => sprintf('%03d/%s/%d', $noUrut, bulanRomawi($bulan), $tahun),
    ];
}

// "Bogor" -> "Kabupaten Bogor", tapi "Kota Bogor" / "Kabupaten Bogor" dibiarkan apa adanya.
function namaKabupaten(?string $kabupaten): string
{
    $kabupaten = trim((string) $kabupaten);
    return preg_match('/^(kabupaten|kota)\s/i', $kabupaten) ? $kabupaten : 'Kabupaten ' . $kabupaten;
}
