<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/alert.php";
require_once "../../includes/sync_akademik_page_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$cards = [
    ['label' => 'Dosen', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM dosen"))],
    ['label' => 'Aktif', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM dosen WHERE status='aktif'"))],
    ['label' => 'Punya ID Feeder', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM dosen WHERE COALESCE(id_dosen_feeder,id_feeder,'') <> ''"))],
    ['label' => 'Belum Sync', 'value' => number_format(sync_count($conn, "SELECT COUNT(*) total FROM dosen WHERE status_sync_feeder IN ('belum','gagal')"))],
];

$rows = [
    ['Biodata Dosen', 'Lengkapi NIDN/NIDK, nama, jenis kelamin, tempat/tanggal lahir, agama, email, dan prodi homebase.', sync_badge(sync_count($conn, "SELECT COUNT(*) total FROM dosen") > 0 ? 'siap' : 'perlu_data')],
    ['ID Dosen Feeder', 'ID Feeder akan terisi dari pull NeoFeeder atau setelah proses insert/update resmi.', sync_badge(sync_count($conn, "SELECT COUNT(*) total FROM dosen WHERE COALESCE(id_dosen_feeder,id_feeder,'') <> ''") > 0 ? 'siap' : 'perlu_data')],
    ['Dosen Pengajar', 'Setelah jadwal dibuat, rebuild kelas kuliah akan membentuk dosen_pengajar_kelas.', sync_badge(sync_count($conn, "SELECT COUNT(*) total FROM dosen_pengajar_kelas") > 0 ? 'siap' : 'perlu_data')],
];

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
render_sync_akademik_page('Sync Dosen', 'Monitoring kesiapan data dosen untuk integrasi NeoFeeder/PDDikti.', $cards, $rows);
require_once "../../includes/footer.php";
