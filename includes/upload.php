<?php

function upload_file($file, $folder_tujuan, $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'], $max_size = 2097152)
{
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return [
            'status' => false,
            'message' => 'File tidak ditemukan atau gagal diunggah.',
            'filename' => null
        ];
    }

    $nama_file = $file['name'];
    $tmp_file = $file['tmp_name'];
    $ukuran_file = $file['size'];

    $ext = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed_ext)) {
        return [
            'status' => false,
            'message' => 'Format file tidak diizinkan.',
            'filename' => null
        ];
    }

    if ($ukuran_file > $max_size) {
        return [
            'status' => false,
            'message' => 'Ukuran file terlalu besar.',
            'filename' => null
        ];
    }

    if (!is_dir($folder_tujuan)) {
        mkdir($folder_tujuan, 0777, true);
    }

    $nama_baru = date('YmdHis') . '_' . uniqid() . '.' . $ext;
    $path_upload = rtrim($folder_tujuan, '/') . '/' . $nama_baru;

    if (move_uploaded_file($tmp_file, $path_upload)) {
        return [
            'status' => true,
            'message' => 'File berhasil diunggah.',
            'filename' => $nama_baru
        ];
    }

    return [
        'status' => false,
        'message' => 'File gagal dipindahkan.',
        'filename' => null
    ];
}
?>