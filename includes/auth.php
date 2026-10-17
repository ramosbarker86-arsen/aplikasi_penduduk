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
// dari root (index.php) maupun dari dalam subfolder (penduduk/, wilayah/, users/).
function basePath()
{
    $script = $_SERVER['SCRIPT_NAME'];
    foreach (['/penduduk/', '/wilayah/', '/users/'] as $dir) {
        if (strpos($script, $dir) !== false) {
            return '../';
        }
    }
    return '';
}
