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
    sync_set_result_alert(sync_dosen_pengajar_to_feeder($conn, sync_limit_from_post()));
}

$cards = [
    ['label' => 'Dosen Pengajar', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM dosen_pengajar_kelas"))],
    ['label' => 'Belum/Gagal', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM dosen_pengajar_kelas WHERE status_sync_feeder IN ('belum','gagal')"))],
    ['label' => 'Sudah Sync', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM dosen_pengajar_kelas WHERE status_sync_feeder='sudah'"))],
    ['label' => 'Kelas Feeder Siap', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM kelas_kuliah WHERE COALESCE(id_kelas_kuliah_feeder,'')<>''"))],
];
$rows = [
    ['InsertDosenPengajarKelasKuliah', 'Mengirim dosen pengajar kelas dari jadwal kuliah.', sync_badge('siap')],
    ['Validasi wajib', 'ID kelas kuliah Feeder dan ID dosen Feeder harus sudah terisi.', sync_badge('siap')],
];

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
render_sync_akademik_page('Sync Dosen Pengajar', 'Push dosen pengajar kelas kuliah ke NeoFeeder/PDDikti.', $cards, $rows, 'sync');
require_once "../../includes/footer.php";
