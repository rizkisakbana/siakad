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
    rebuild_nilai_pelaporan($conn);
    sync_set_result_alert(sync_nilai_to_feeder($conn, sync_limit_from_post()));
}

$cards = [
    ['label' => 'Nilai', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM nilai"))],
    ['label' => 'Publish', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM nilai WHERE status_publish='publish'"))],
    ['label' => 'Belum/Gagal', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM nilai WHERE status_sync_feeder IN ('belum','gagal')"))],
    ['label' => 'Sudah Sync', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM nilai WHERE status_sync_feeder='sudah'"))],
];
$rows = [
    ['UpdateNilaiPerkuliahanKelas', 'Mengirim nilai publish ke NeoFeeder.', sync_badge('siap')],
    ['Validasi wajib', 'ID kelas kuliah Feeder dan ID registrasi mahasiswa Feeder harus tersedia.', sync_badge('siap')],
];

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
render_sync_akademik_page('Sync Nilai', 'Push nilai perkuliahan ke NeoFeeder/PDDikti.', $cards, $rows, 'sync');
require_once "../../includes/footer.php";
