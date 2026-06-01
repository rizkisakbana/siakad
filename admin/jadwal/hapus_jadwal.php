<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/alert.php";
require_once "../../includes/log_aktivitas.php";
require_once "../../includes/akademik_inti_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$id_jadwal = intval($_GET['id'] ?? 0);
if ($id_jadwal <= 0) {
    set_alert('error', 'ID jadwal tidak valid.');
    header('Location: data_jadwal.php');
    exit;
}

$q = mysqli_query($conn, "SELECT * FROM jadwal_kuliah WHERE id_jadwal='$id_jadwal' LIMIT 1");
if (!$q || mysqli_num_rows($q) < 1) {
    set_alert('error', 'Data jadwal tidak ditemukan.');
    header('Location: data_jadwal.php');
    exit;
}

$cek_krs = mysqli_query($conn, "SELECT COUNT(*) total FROM krs_detail WHERE id_jadwal='$id_jadwal'");
$total_krs = $cek_krs ? (int)(mysqli_fetch_assoc($cek_krs)['total'] ?? 0) : 0;
if ($total_krs > 0) {
    set_alert('error', 'Jadwal tidak dapat dihapus karena sudah digunakan pada KRS. Nonaktifkan jadwal jika tidak dipakai lagi.');
    header('Location: detail_jadwal.php?id=' . $id_jadwal);
    exit;
}

mysqli_begin_transaction($conn);
try {
    mysqli_query($conn, "DELETE FROM dosen_pengajar_kelas WHERE id_kelas_kuliah IN (SELECT id_kelas_kuliah FROM kelas_kuliah WHERE id_jadwal='$id_jadwal')");
    mysqli_query($conn, "DELETE FROM kelas_kuliah WHERE id_jadwal='$id_jadwal'");
    $hapus = mysqli_query($conn, "DELETE FROM jadwal_kuliah WHERE id_jadwal='$id_jadwal'");
    if (!$hapus) throw new Exception('Gagal menghapus jadwal: ' . mysqli_error($conn));

    rebuild_akademik_inti($conn, false);
    mysqli_commit($conn);

    simpan_log($conn, $_SESSION['id_user'], 'Menghapus jadwal kuliah ID ' . $id_jadwal, 'Jadwal');
    set_alert('success', 'Jadwal berhasil dihapus dan relasi akademik inti dibangun ulang.');
    header('Location: data_jadwal.php');
    exit;
} catch (Throwable $e) {
    mysqli_rollback($conn);
    set_alert('error', $e->getMessage());
    header('Location: detail_jadwal.php?id=' . $id_jadwal);
    exit;
}
