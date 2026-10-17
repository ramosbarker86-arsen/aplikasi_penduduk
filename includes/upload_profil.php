<?php
// Upload foto profil pengguna, dipakai di users/tambah.php dan users/edit.php.
function uploadFotoProfil(string $field): ?string
{
    if (empty($_FILES[$field]['name'])) {
        return null;
    }

    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Gagal mengunggah foto profil.');
    }

    $allowedExt = ['jpg', 'jpeg', 'png'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true) || @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('Foto profil harus berupa gambar jpg, jpeg, atau png.');
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Ukuran foto profil maksimal 2MB.');
    }

    $destDir = __DIR__ . '/../uploads/profil/';
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    $newName = 'profil_' . uniqid() . '.' . $ext;
    move_uploaded_file($file['tmp_name'], $destDir . $newName);

    return 'uploads/profil/' . $newName;
}
