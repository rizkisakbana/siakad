<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/alert.php";
require_once "../../includes/transaksi_pelaporan_helper.php";
require_once "../../includes/neofeeder_helper.php";
require_once "../../includes/neofeeder_sync_akademik_helper.php";
require_once "../../includes/sync_akademik_page_helper.php";
require_once "../../includes/sync_akademik_controller.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    rebuild_lulus_do_pelaporan($conn);
    sync_set_result_alert(sync_lulus_do_to_feeder($conn, sync_limit_from_post()));
}

$cards = [
    ['label' => 'Lulus/DO', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM mahasiswa_lulus_do"))],
    ['label' => 'Belum/Gagal', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM mahasiswa_lulus_do WHERE status_sync_feeder IN ('belum','gagal')"))],
    ['label' => 'Sudah Sync', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM mahasiswa_lulus_do WHERE status_sync_feeder='sudah'"))],
    ['label' => 'Mahasiswa Lulus', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM mahasiswa WHERE status_mahasiswa='lulus'"))],
];
$rows = [
    ['InsertMahasiswaLulusDO', 'Mengirim status akhir mahasiswa ke NeoFeeder.', sync_badge('siap')],
    ['Validasi wajib', 'Registrasi mahasiswa, jenis keluar Feeder, dan tanggal keluar harus tersedia.', sync_badge('siap')],
];

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
render_sync_akademik_page('Sync Lulus/DO', 'Push data mahasiswa lulus, DO, pindah, dan keluar ke NeoFeeder/PDDikti.', $cards, $rows, 'sync');
require_once "../../includes/footer.php";
