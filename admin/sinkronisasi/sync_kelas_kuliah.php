<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/alert.php";
require_once "../../includes/akademik_inti_helper.php";
require_once "../../includes/neofeeder_helper.php";
require_once "../../includes/neofeeder_sync_akademik_helper.php";
require_once "../../includes/sync_akademik_page_helper.php";
require_once "../../includes/sync_akademik_controller.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    rebuild_akademik_inti($conn);
    sync_set_result_alert(sync_kelas_kuliah_to_feeder($conn, sync_limit_from_post()));
}

$cards = [
    ['label' => 'Jadwal', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM jadwal_kuliah"))],
    ['label' => 'Kelas Kuliah', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM kelas_kuliah"))],
    ['label' => 'Belum/Gagal', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM kelas_kuliah WHERE status_sync_feeder IN ('belum','gagal')"))],
    ['label' => 'Sudah Sync', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM kelas_kuliah WHERE status_sync_feeder='sudah'"))],
];
$rows = [
    ['Rebuild Lokal', 'Sebelum push, sistem membangun ulang kelas_kuliah dari jadwal.', sync_badge('siap')],
    ['InsertKelasKuliah', 'Mengirim kelas kuliah yang belum/gagal sync.', sync_badge('siap')],
];

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
render_sync_akademik_page('Sync Kelas Kuliah', 'Push kelas kuliah SIAKAD ke NeoFeeder/PDDikti.', $cards, $rows, 'sync');
require_once "../../includes/footer.php";
