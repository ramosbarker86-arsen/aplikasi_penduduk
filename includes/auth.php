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

// Mengembalikan path relatif ke root aplikasi, supaya link konsisten baik diakses
// dari root (index.php) maupun dari dalam folder penduduk/ (penduduk/list.php, dll).
function basePath()
{
    $script = $_SERVER['SCRIPT_NAME'];
    return (strpos($script, '/penduduk/') !== false || strpos($script, '/wilayah/') !== false) ? '../' : '';
}
