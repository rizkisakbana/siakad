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
    sync_set_result_alert(sync_kurikulum_to_feeder($conn, sync_limit_from_post()));
}

$cards = [
    ['label' => 'Kurikulum', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM kurikulum"))],
    ['label' => 'Belum/Gagal', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM kurikulum WHERE status_sync_feeder IN ('belum','gagal')"))],
    ['label' => 'Sudah Sync', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM kurikulum WHERE status_sync_feeder='sudah'"))],
    ['label' => 'Matkul Kurikulum', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM matkul_kurikulum"))],
];
$rows = [
    ['InsertKurikulum', 'Mengirim kurikulum aktif yang belum/gagal sync ke NeoFeeder.', sync_badge('siap')],
    ['Validasi wajib', 'ID prodi Feeder dan semester mulai Feeder harus terisi.', sync_badge('siap')],
];

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
render_sync_akademik_page('Sync Kurikulum', 'Push data kurikulum SIAKAD ke NeoFeeder/PDDikti.', $cards, $rows, 'sync');
require_once "../../includes/footer.php";
