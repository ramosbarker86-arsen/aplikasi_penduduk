<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT id, username, password, nama_lengkap, level, status_cd FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'Username atau password salah.';
        } elseif ($user['status_cd'] !== 'active') {
            // Status dicek setelah password benar, supaya status akun tidak bocor ke orang lain.
            $error = 'Akun Anda tidak aktif. Hubungi administrator.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['level'] = $user['level'];
            header('Location: index.php');
            exit;
        }
    }
}

// Form login ada di kotak "Member Login" halaman Home, jadi halaman login menampilkan Home beserta pesan error-nya.
$loginError = $error;
require __DIR__ . '/home.php';
