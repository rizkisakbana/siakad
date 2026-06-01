<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/alert.php";
require_once "../../includes/neofeeder_helper.php";
require_once "../../includes/neofeeder_sync_akademik_helper.php";
require_once "../../includes/sync_akademik_page_helper.php";
require_once "../../includes/sync_akademik_controller.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sync_set_result_alert(sync_matakuliah_to_feeder($conn, sync_limit_from_post()));
}

$cards = [
    ['label' => 'Mata Kuliah', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM mata_kuliah"))],
    ['label' => 'Belum/Gagal', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM mata_kuliah WHERE status_sync_feeder IN ('belum','gagal')"))],
    ['label' => 'Sudah Sync', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM mata_kuliah WHERE status_sync_feeder='sudah'"))],
    ['label' => 'Relasi Kurikulum', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM matkul_kurikulum"))],
];
$rows = [
    ['InsertMataKuliah', 'Mengirim mata kuliah aktif yang belum/gagal sync.', sync_badge('siap')],
    ['Validasi wajib', 'ID prodi Feeder harus tersedia dari kurikulum/prodi.', sync_badge('siap')],
];

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
render_sync_akademik_page('Sync Mata Kuliah', 'Push data mata kuliah SIAKAD ke NeoFeeder/PDDikti.', $cards, $rows, 'sync');
require_once "../../includes/footer.php";
