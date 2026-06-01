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
    sync_set_result_alert(sync_peserta_kelas_to_feeder($conn, sync_limit_from_post()));
}

$cards = [
    ['label' => 'Peserta Kelas', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM peserta_kelas_kuliah"))],
    ['label' => 'Belum/Gagal', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM peserta_kelas_kuliah WHERE status_sync_feeder IN ('belum','gagal')"))],
    ['label' => 'Sudah Sync', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM peserta_kelas_kuliah WHERE status_sync_feeder='sudah'"))],
    ['label' => 'KRS Detail', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM krs_detail"))],
];
$rows = [
    ['InsertPesertaKelasKuliah', 'Mengirim peserta kelas dari KRS detail.', sync_badge('siap')],
    ['Validasi wajib', 'ID kelas kuliah Feeder dan ID registrasi mahasiswa Feeder harus tersedia.', sync_badge('siap')],
];

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
render_sync_akademik_page('Sync Peserta Kelas', 'Push peserta kelas/KRS ke NeoFeeder/PDDikti.', $cards, $rows, 'sync');
require_once "../../includes/footer.php";
