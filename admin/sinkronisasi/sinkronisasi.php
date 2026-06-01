<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";
require_once "../../includes/log_aktivitas.php";
require_once "../../includes/neofeeder_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Sinkronisasi Neo Feeder";
$page_subtitle = "Pusat sinkronisasi data SIAKAD dengan Neo Feeder PDDikti";

$config = get_neofeeder_config($conn);

function hitung_data($conn, $tabel, $field_feeder = 'id_feeder')
{
    $total = 0;
    $sinkron = 0;
    $belum = 0;

    $cek_tabel = mysqli_query($conn, "SHOW TABLES LIKE '$tabel'");
    if (!$cek_tabel || mysqli_num_rows($cek_tabel) < 1) {
        return [
            'total' => 0,
            'sinkron' => 0,
            'belum' => 0
        ];
    }

    $q_total = mysqli_query($conn, "SELECT COUNT(*) AS total FROM $tabel");
    if ($q_total) {
        $total = mysqli_fetch_assoc($q_total)['total'] ?? 0;
    }

    $cek_field = mysqli_query($conn, "SHOW COLUMNS FROM $tabel LIKE '$field_feeder'");
    if ($cek_field && mysqli_num_rows($cek_field) > 0) {
        $q_sinkron = mysqli_query($conn, "
            SELECT COUNT(*) AS total 
            FROM $tabel 
            WHERE $field_feeder IS NOT NULL 
            AND $field_feeder != ''
        ");

        if ($q_sinkron) {
            $sinkron = mysqli_fetch_assoc($q_sinkron)['total'] ?? 0;
        }
    }

    $belum = $total - $sinkron;
    if ($belum < 0)
        $belum = 0;

    return [
        'total' => $total,
        'sinkron' => $sinkron,
        'belum' => $belum
    ];
}

$prodi = hitung_data($conn, 'prodi');
$dosen = hitung_data($conn, 'dosen');
$mahasiswa = hitung_data($conn, 'mahasiswa');
$matakuliah = hitung_data($conn, 'mata_kuliah');
$kurikulum = hitung_data($conn, 'kurikulum');

$kelas_kuliah = hitung_data($conn, 'jadwal_kuliah', 'id_kelas_kuliah_feeder');

$total_log = 0;
$q_log = mysqli_query($conn, "SELECT COUNT(*) AS total FROM neofeeder_log");
if ($q_log) {
    $total_log = mysqli_fetch_assoc($q_log)['total'] ?? 0;
}

$total_log_gagal = 0;
$q_log_gagal = mysqli_query($conn, "SELECT COUNT(*) AS total FROM neofeeder_log WHERE status = 'failed'");
if ($q_log_gagal) {
    $total_log_gagal = mysqli_fetch_assoc($q_log_gagal)['total'] ?? 0;
}

$status_class = "bg-red-100 text-red-700";
$status_label = "Disconnected";

if ($config && ($config['status'] ?? '') == 'connected') {
    $status_class = "bg-green-100 text-green-700";
    $status_label = "Connected";
}

simpan_log(
    $conn,
    $_SESSION['id_user'],
    "Membuka halaman pusat sinkronisasi Neo Feeder",
    "Neo Feeder"
);

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <?php show_alert(); ?>

    <!-- <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Sinkronisasi Neo Feeder</h2>
            <p class="text-sm text-slate-500">
                Pusat integrasi data SIAKAD dengan Neo Feeder/PDDikti.
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <a href="pengaturan.php"
                class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                <i class="fa-solid fa-gear mr-2"></i>
                Pengaturan
            </a>

            <a href="test_koneksi.php"
                class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                <i class="fa-solid fa-plug mr-2"></i>
                Test Koneksi
            </a>

            <a href="log_sinkronisasi.php"
                class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-purple-700 hover:bg-purple-800 text-white font-semibold">
                <i class="fa-solid fa-clock-rotate-left mr-2"></i>
                Log
            </a>
        </div>
    </div> -->

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Status Koneksi</p>
            <div class="mt-3">
                <span class="inline-flex px-4 py-2 rounded-full text-sm font-bold <?= $status_class; ?>">
                    <?= $status_label; ?>
                </span>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Environment</p>
            <h2 class="text-2xl font-bold text-blue-700 mt-2 capitalize">
                <?= htmlspecialchars($config['environment'] ?? '-'); ?>
            </h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Total Log</p>
            <h2 class="text-3xl font-bold text-purple-700 mt-2">
                <?= number_format($total_log); ?>
            </h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Log Gagal</p>
            <h2 class="text-3xl font-bold text-red-700 mt-2">
                <?= number_format($total_log_gagal); ?>
            </h2>
        </div>

    </section>

    <?php if (!$config): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-2xl p-5 mb-6">
            <strong>Konfigurasi Neo Feeder belum tersedia.</strong>
            Silakan isi pengaturan terlebih dahulu sebelum melakukan sinkronisasi.
        </div>
    <?php endif; ?>

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-4 sm:p-6">

        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Daftar Modul Sinkronisasi</h2>
                <p class="text-sm text-slate-500">
                    Sinkronisasi dilakukan bertahap agar data dapat divalidasi sebelum dikirim ke Neo Feeder.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
                <a href="pengaturan.php"
                    class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                    <i class="fa-solid fa-gear mr-2"></i>
                    Pengaturan
                </a>

                <a href="test_koneksi.php"
                    class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                    <i class="fa-solid fa-plug mr-2"></i>
                    Test Koneksi
                </a>

                <a href="log_sinkronisasi.php"
                    class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-purple-700 hover:bg-purple-800 text-white font-semibold">
                    <i class="fa-solid fa-clock-rotate-left mr-2"></i>
                    Log
                </a>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-200">

            <table class="min-w-full text-sm">
                <thead class="bg-slate-100 text-slate-700">
                    <tr>
                        <th class="px-4 py-3 text-left w-16">No</th>
                        <th class="px-4 py-3 text-left">Modul</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Fungsi Neo Feeder</th>
                        <th class="px-4 py-3 text-center hidden lg:table-cell">Total Lokal</th>
                        <th class="px-4 py-3 text-center hidden lg:table-cell">Sudah Sinkron</th>
                        <th class="px-4 py-3 text-center hidden lg:table-cell">Belum Sinkron</th>
                        <th class="px-4 py-3 text-center w-48">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">

                    <?php
                    $modul = [
                        [
                            'kategori' => 'Identitas & Referensi Utama',
                            'items' => [
                                [
                                    'nama' => 'Profil Perguruan Tinggi',
                                    'icon' => 'fa-building-columns',
                                    'fungsi' => 'GetProfilPT',
                                    'data' => hitung_data($conn, 'profil_pt'),
                                    'url' => 'sync_profil_pt.php',
                                    'status' => 'Siap'
                                ],
                                [
                                    'nama' => 'Program Studi',
                                    'icon' => 'fa-graduation-cap',
                                    'fungsi' => 'GetProdi',
                                    'data' => $prodi,
                                    'url' => 'sync_prodi.php',
                                    'status' => 'Siap'
                                ],
                                [
                                    'nama' => 'Periode / Tahun Akademik',
                                    'icon' => 'fa-calendar',
                                    'fungsi' => 'GetPeriode / GetSemester / GetTahunAjaran',
                                    'data' => hitung_data($conn, 'tahun_akademik'),
                                    'url' => 'sync_periode.php',
                                    'status' => 'Perlu dibuat'
                                ],
                                [
                                    'nama' => 'Referensi PDDikti',
                                    'icon' => 'fa-database',
                                    'fungsi' => 'GetAgama, GetJalurMasuk, GetStatusMahasiswa, GetWilayah, dll',
                                    'data' => hitung_data($conn, 'ref_pddikti'),
                                    'url' => 'sync_referensi.php',
                                    'status' => 'Perlu dibuat'
                                ],
                            ]
                        ],

                        [
                            'kategori' => 'Master Akademik',
                            'items' => [
                                [
                                    'nama' => 'Dosen',
                                    'icon' => 'fa-user-tie',
                                    'fungsi' => 'GetListDosen / DetailBiodataDosen',
                                    'data' => $dosen,
                                    'url' => 'sync_dosen.php',
                                    'status' => 'Perlu mapping id_feeder'
                                ],
                                [
                                    'nama' => 'Mahasiswa',
                                    'icon' => 'fa-user-graduate',
                                    'fungsi' => 'GetListMahasiswa / GetBiodataMahasiswa',
                                    'data' => $mahasiswa,
                                    'url' => 'sync_mahasiswa.php',
                                    'status' => 'Perlu mapping referensi'
                                ],
                                [
                                    'nama' => 'Kurikulum',
                                    'icon' => 'fa-book-open',
                                    'fungsi' => 'GetListKurikulum',
                                    'data' => $kurikulum,
                                    'url' => 'sync_kurikulum.php',
                                    'status' => 'Perlu mapping prodi'
                                ],
                                [
                                    'nama' => 'Mata Kuliah',
                                    'icon' => 'fa-book',
                                    'fungsi' => 'GetListMataKuliah',
                                    'data' => $matakuliah,
                                    'url' => 'sync_matakuliah.php',
                                    'status' => 'Perlu mapping prodi/kurikulum'
                                ],
                                [
                                    'nama' => 'Mata Kuliah Kurikulum',
                                    'icon' => 'fa-layer-group',
                                    'fungsi' => 'GetMatkulKurikulum',
                                    'data' => hitung_data($conn, 'mata_kuliah'),
                                    'url' => 'sync_matkul_kurikulum.php',
                                    'status' => 'Perlu dibuat setelah kurikulum'
                                ],
                            ]
                        ],

                        [
                            'kategori' => 'Perkuliahan',
                            'items' => [
                                [
                                    'nama' => 'Kelas Kuliah / Jadwal',
                                    'icon' => 'fa-calendar-days',
                                    'fungsi' => 'GetListKelasKuliah / InsertKelasKuliah',
                                    'data' => $kelas_kuliah,
                                    'url' => 'sync_kelas_kuliah.php',
                                    'status' => 'Dibuat setelah modul jadwal'
                                ],
                                [
                                    'nama' => 'Dosen Pengajar Kelas',
                                    'icon' => 'fa-chalkboard-user',
                                    'fungsi' => 'GetDosenPengajarKelasKuliah / InsertDosenPengajarKelasKuliah',
                                    'data' => hitung_data($conn, 'jadwal_kuliah'),
                                    'url' => 'sync_dosen_pengajar.php',
                                    'status' => 'Dibuat setelah jadwal'
                                ],
                                [
                                    'nama' => 'Peserta Kelas / KRS',
                                    'icon' => 'fa-users',
                                    'fungsi' => 'GetPesertaKelasKuliah / InsertPesertaKelasKuliah',
                                    'data' => hitung_data($conn, 'krs_detail'),
                                    'url' => 'sync_peserta_kelas.php',
                                    'status' => 'Dibuat setelah KRS'
                                ],
                                [
                                    'nama' => 'Nilai Perkuliahan',
                                    'icon' => 'fa-square-poll-vertical',
                                    'fungsi' => 'GetListNilaiPerkuliahanKelas / UpdateNilaiPerkuliahanKelas',
                                    'data' => hitung_data($conn, 'nilai'),
                                    'url' => 'sync_nilai.php',
                                    'status' => 'Dibuat setelah nilai'
                                ],
                                [
                                    'nama' => 'Aktivitas Kuliah Mahasiswa',
                                    'icon' => 'fa-chart-line',
                                    'fungsi' => 'GetListPerkuliahanMahasiswa / InsertPerkuliahanMahasiswa',
                                    'data' => hitung_data($conn, 'aktivitas_kuliah_mahasiswa'),
                                    'url' => 'sync_akm.php',
                                    'status' => 'Dibuat setelah KHS'
                                ],
                            ]
                        ],

                        [
                            'kategori' => 'Pelaporan Lanjutan',
                            'items' => [
                                [
                                    'nama' => 'Mahasiswa Lulus / DO',
                                    'icon' => 'fa-user-check',
                                    'fungsi' => 'GetListMahasiswaLulusDO / InsertMahasiswaLulusDO',
                                    'data' => hitung_data($conn, 'mahasiswa_lulus_do'),
                                    'url' => 'sync_lulus_do.php',
                                    'status' => 'Dibuat setelah status akhir mahasiswa'
                                ],
                                [
                                    'nama' => 'Prestasi Mahasiswa',
                                    'icon' => 'fa-trophy',
                                    'fungsi' => 'GetListPrestasiMahasiswa / InsertPrestasiMahasiswa',
                                    'data' => hitung_data($conn, 'prestasi_mahasiswa'),
                                    'url' => 'sync_prestasi.php',
                                    'status' => 'Opsional'
                                ],
                                [
                                    'nama' => 'Aktivitas Mahasiswa / MBKM',
                                    'icon' => 'fa-people-group',
                                    'fungsi' => 'GetListAktivitasMahasiswa / InsertAktivitasMahasiswa',
                                    'data' => hitung_data($conn, 'aktivitas_mahasiswa'),
                                    'url' => 'sync_aktivitas_mahasiswa.php',
                                    'status' => 'Opsional'
                                ],
                                [
                                    'nama' => 'Konversi Kampus Merdeka',
                                    'icon' => 'fa-right-left',
                                    'fungsi' => 'GetListKonversiKampusMerdeka',
                                    'data' => hitung_data($conn, 'konversi_mbkm'),
                                    'url' => 'sync_konversi_mbkm.php',
                                    'status' => 'Opsional'
                                ],
                                [
                                    'nama' => 'Rekap Pelaporan',
                                    'icon' => 'fa-chart-pie',
                                    'fungsi' => 'GetRekapLaporan / GetRekapKRSMahasiswa / GetRekapKHSMahasiswa',
                                    'data' => hitung_data($conn, 'neofeeder_log'),
                                    'url' => 'rekap_pelaporan.php',
                                    'status' => 'Monitoring'
                                ],
                            ]
                        ],
                    ];
                    ?>

                    <?php $no = 1; ?>
                    <?php foreach ($modul as $group): ?>
                        <tr class="bg-slate-50">
                            <td colspan="7" class="px-4 py-3 font-bold text-slate-700">
                                <?= htmlspecialchars($group['kategori']); ?>
                            </td>
                        </tr>

                        <?php foreach ($group['items'] as $m): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <?= $no++; ?>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-11 h-11 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center">
                                            <i class="fa-solid <?= $m['icon']; ?>"></i>
                                        </div>

                                        <div>
                                            <div class="font-semibold text-slate-800">
                                                <?= htmlspecialchars($m['nama']); ?>
                                            </div>

                                            <div class="text-xs text-slate-500 lg:hidden">
                                                Total: <?= number_format($m['data']['total']); ?> •
                                                Sinkron: <?= number_format($m['data']['sinkron']); ?>
                                            </div>

                                            <div class="text-xs text-blue-600 mt-1">
                                                <?= htmlspecialchars($m['status']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <?= htmlspecialchars($m['fungsi']); ?>
                                </td>

                                <td class="px-4 py-3 text-center hidden lg:table-cell">
                                    <?= number_format($m['data']['total']); ?>
                                </td>

                                <td class="px-4 py-3 text-center hidden lg:table-cell">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">
                                        <?= number_format($m['data']['sinkron']); ?>
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-center hidden lg:table-cell">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-700">
                                        <?= number_format($m['data']['belum']); ?>
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="<?= $m['url']; ?>"
                                            class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold text-xs">
                                            <i class="fa-solid fa-rotate mr-2"></i>
                                            Sinkron
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>

                </tbody>
            </table>

        </div>

    </section>

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Urutan Sinkronisasi Aman</h3>

            <ol class="list-decimal pl-5 text-sm text-slate-600 space-y-2">
                <li>Test koneksi Neo Feeder.</li>
                <li>Tarik dan mapping Program Studi.</li>
                <li>Sinkronisasi Dosen.</li>
                <li>Sinkronisasi Mahasiswa.</li>
                <li>Sinkronisasi Kurikulum dan Mata Kuliah.</li>
                <li>Baru lanjut Jadwal, KRS, dan Nilai.</li>
            </ol>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Catatan Integrasi</h3>

            <div class="text-sm text-slate-600 space-y-3">
                <p>
                    Web Service Neo Feeder menyediakan fungsi untuk Prodi, Mahasiswa, Dosen, Mata Kuliah, Kurikulum,
                    Kelas Kuliah, Peserta Kelas, Nilai, dan Rekap Pelaporan. :contentReference[oaicite:0]{index=0}
                </p>

                <p>
                    Setiap data lokal wajib menyimpan ID Feeder agar update berikutnya tidak membuat data ganda.
                </p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Akses Cepat</h3>

            <div class="space-y-3">
                <a href="pengaturan.php"
                    class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold">
                    <i class="fa-solid fa-gear mr-2"></i>
                    Pengaturan Neo Feeder
                </a>

                <a href="test_koneksi.php"
                    class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-blue-100 hover:bg-blue-200 text-blue-700 font-semibold">
                    <i class="fa-solid fa-plug mr-2"></i>
                    Test Koneksi
                </a>

                <a href="log_sinkronisasi.php"
                    class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-purple-100 hover:bg-purple-200 text-purple-700 font-semibold">
                    <i class="fa-solid fa-clock-rotate-left mr-2"></i>
                    Log Sinkronisasi
                </a>
            </div>
        </div>

    </section>

</main>

<?php require_once "../../includes/footer.php"; ?>