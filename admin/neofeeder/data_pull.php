<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/helper.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/../../includes/log_aktivitas.php";
require_once __DIR__ . "/../../includes/neofeeder_helper.php";
require_once __DIR__ . "/neofeeder_admin_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Data Pull NeoFeeder";
$page_subtitle = "Pusat pengambilan data dari NeoFeeder/PDDikti ke database SIAKAD";

$config = get_neofeeder_config($conn);

function hitung_data_pull($conn, $tabel, $field_feeder = 'id_feeder')
{
    $tabel = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $tabel);
    $field_feeder = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $field_feeder);

    if (!nf_table_exists($conn, $tabel)) {
        return ['total' => 0, 'sinkron' => 0, 'belum' => 0];
    }

    $total = nf_count($conn, "SELECT COUNT(*) AS total FROM `$tabel`");
    $sinkron = 0;

    if (nf_column_exists($conn, $tabel, $field_feeder)) {
        $sinkron = nf_count($conn, "
            SELECT COUNT(*) AS total
            FROM `$tabel`
            WHERE `$field_feeder` IS NOT NULL
            AND `$field_feeder` != ''
        ");
    }

    return [
        'total' => $total,
        'sinkron' => $sinkron,
        'belum' => max(0, $total - $sinkron)
    ];
}

function hitung_ref($conn, $jenis_ref)
{
    $jenis_ref = mysqli_real_escape_string($conn, $jenis_ref);

    if (!nf_table_exists($conn, 'ref_pddikti')) {
        return ['total' => 0, 'sinkron' => 0, 'belum' => 0];
    }

    $total = nf_count($conn, "
        SELECT COUNT(*) AS total
        FROM ref_pddikti
        WHERE jenis_ref = '$jenis_ref'
    ");

    return [
        'total' => $total,
        'sinkron' => $total,
        'belum' => 0
    ];
}

$total_log = 0;
$total_log_failed = 0;

$total_log = nf_count($conn, "SELECT COUNT(*) AS total FROM neofeeder_log");
$total_log_failed = nf_count($conn, "SELECT COUNT(*) AS total FROM neofeeder_log WHERE status = 'failed'");

$status_class = "bg-red-100 text-red-700";
$status_label = "Disconnected";

if ($config && ($config['status'] ?? '') == 'connected') {
    $status_class = "bg-green-100 text-green-700";
    $status_label = "Connected";
}

$pull_groups = [
    [
        'kategori' => 'Identitas Perguruan Tinggi',
        'items' => [
            [
                'nama' => 'Profil Perguruan Tinggi',
                'icon' => 'fa-building-columns',
                'fungsi' => 'GetProfilPT',
                'data' => hitung_data_pull($conn, 'profil_pt'),
                'url' => 'sync_profil_pt.php',
                'status' => 'Siap',
                'keterangan' => 'Mengambil identitas resmi perguruan tinggi dari NeoFeeder.'
            ],
            [
                'nama' => 'Program Studi',
                'icon' => 'fa-graduation-cap',
                'fungsi' => 'GetProdi',
                'data' => hitung_data_pull($conn, 'prodi'),
                'url' => 'sync_prodi.php',
                'status' => 'Siap',
                'keterangan' => 'Mengambil data program studi yang terdaftar pada akun PT.'
            ],
        ]
    ],
    [
        'kategori' => 'Referensi PDDikti',
        'items' => [
            [
                'nama' => 'Referensi Umum',
                'icon' => 'fa-database',
                'fungsi' => 'GetAgama, GetJalurMasuk, GetStatusMahasiswa, GetIkatanKerjaSdm, dll',
                'data' => hitung_data_pull($conn, 'ref_pddikti'),
                'url' => 'sync_referensi.php',
                'status' => 'Siap',
                'keterangan' => 'Mengambil referensi utama PDDikti ke tabel ref_pddikti.'
            ],
            [
                'nama' => 'Wilayah',
                'icon' => 'fa-map-location-dot',
                'fungsi' => 'GetWilayah',
                'data' => hitung_ref($conn, 'wilayah'),
                'url' => 'sync_wilayah.php',
                'status' => 'Khusus / Bertahap',
                'keterangan' => 'Mengambil data wilayah secara bertahap karena datanya besar.'
            ],
            [
                'nama' => 'Semester / Tahun Ajaran',
                'icon' => 'fa-calendar-days',
                'fungsi' => 'GetSemester / GetTahunAjaran / GetPeriode',
                'data' => hitung_data_pull($conn, 'tahun_akademik'),
                'url' => 'sync_periode.php',
                'status' => 'Siap',
                'keterangan' => 'Mengambil periode akademik sebagai dasar KRS, kelas, dan nilai.'
            ],
        ]
    ],
    [
        'kategori' => 'Master Akademik',
        'items' => [
            [
                'nama' => 'Mahasiswa Aktif',
                'icon' => 'fa-user-graduate',
                'fungsi' => 'GetListMahasiswa',
                'data' => hitung_data_pull($conn, 'mahasiswa', 'id_biodata_feeder'),
                'url' => 'pull_mahasiswa.php',
                'status' => 'Siap',
                'keterangan' => 'Menarik mahasiswa aktif berdasarkan prodi dan angkatan.'
            ],
            [
                'nama' => 'Dosen',
                'icon' => 'fa-user-tie',
                'fungsi' => 'GetListDosen / DetailBiodataDosen',
                'data' => hitung_data_pull($conn, 'dosen'),
                'url' => 'pull_dosen.php',
                'status' => 'Siap',
                'keterangan' => 'Menarik data dosen dari NeoFeeder ke master dosen lokal.'
            ],
            [
                'nama' => 'Kurikulum',
                'icon' => 'fa-book-open',
                'fungsi' => 'GetListKurikulum',
                'data' => hitung_data_pull($conn, 'kurikulum', 'id_kurikulum_feeder'),
                'url' => 'pull_kurikulum.php',
                'status' => 'Siap',
                'keterangan' => 'Mengambil kurikulum yang sudah terdaftar di NeoFeeder.'
            ],
            [
                'nama' => 'Mata Kuliah',
                'icon' => 'fa-book',
                'fungsi' => 'GetListMataKuliah',
                'data' => hitung_data_pull($conn, 'mata_kuliah', 'id_matkul_feeder'),
                'url' => 'pull_matakuliah.php',
                'status' => 'Siap',
                'keterangan' => 'Mengambil daftar mata kuliah resmi dari NeoFeeder.'
            ],
            [
                'nama' => 'Mata Kuliah Kurikulum',
                'icon' => 'fa-layer-group',
                'fungsi' => 'GetMatkulKurikulum',
                'data' => hitung_data_pull($conn, 'matkul_kurikulum', 'id_matkul_feeder'),
                'url' => 'pull_matkul_kurikulum.php',
                'status' => 'Siap',
                'keterangan' => 'Mengambil relasi mata kuliah pada setiap kurikulum.'
            ],
        ]
    ],
    [
        'kategori' => 'Data Perkuliahan',
        'items' => [
            [
                'nama' => 'Kelas Kuliah',
                'icon' => 'fa-chalkboard',
                'fungsi' => 'GetListKelasKuliah / GetDetailKelasKuliah',
                'data' => hitung_data_pull($conn, 'kelas_kuliah', 'id_kelas_kuliah_feeder'),
                'url' => 'pull_kelas_kuliah.php',
                'status' => 'Siap',
                'keterangan' => 'Menarik kelas kuliah yang sudah tercatat pada NeoFeeder.'
            ],
            [
                'nama' => 'Dosen Pengajar Kelas',
                'icon' => 'fa-chalkboard-user',
                'fungsi' => 'GetDosenPengajarKelasKuliah',
                'data' => hitung_data_pull($conn, 'dosen_pengajar_kelas', 'id_kelas_kuliah_feeder'),
                'url' => 'pull_dosen_pengajar.php',
                'status' => 'Siap setelah kelas kuliah',
                'keterangan' => 'Menarik dosen pengajar pada kelas kuliah.'
            ],
            [
                'nama' => 'Peserta Kelas / KRS',
                'icon' => 'fa-users',
                'fungsi' => 'GetPesertaKelasKuliah / GetKRSMahasiswa',
                'data' => hitung_data_pull($conn, 'peserta_kelas_kuliah', 'id_kelas_kuliah_feeder'),
                'url' => 'pull_peserta_kelas.php',
                'status' => 'Siap setelah kelas kuliah',
                'keterangan' => 'Menarik peserta kelas dari NeoFeeder ke data KRS lokal.'
            ],
            [
                'nama' => 'Nilai Perkuliahan',
                'icon' => 'fa-square-poll-vertical',
                'fungsi' => 'GetListNilaiPerkuliahanKelas / GetDetailNilaiPerkuliahanKelas',
                'data' => hitung_data_pull($conn, 'nilai', 'id_kelas_kuliah_feeder'),
                'url' => 'pull_nilai.php',
                'status' => 'Siap setelah peserta/KRS',
                'keterangan' => 'Menarik nilai perkuliahan dari NeoFeeder.'
            ],
        ]
    ],
    [
        'kategori' => 'Pelaporan Mahasiswa',
        'items' => [
            [
                'nama' => 'Aktivitas Kuliah Mahasiswa',
                'icon' => 'fa-chart-line',
                'fungsi' => 'GetListPerkuliahanMahasiswa',
                'data' => hitung_data_pull($conn, 'aktivitas_kuliah_mahasiswa', 'id_registrasi_mahasiswa_feeder'),
                'url' => 'pull_akm.php',
                'status' => 'Siap',
                'keterangan' => 'Menarik data AKM per semester.'
            ],
            [
                'nama' => 'Mahasiswa Lulus / DO',
                'icon' => 'fa-user-check',
                'fungsi' => 'GetListMahasiswaLulusDO / GetDetailMahasiswaLulusDO',
                'data' => hitung_data_pull($conn, 'mahasiswa_lulus_do', 'id_registrasi_mahasiswa_feeder'),
                'url' => 'pull_lulus_do.php',
                'status' => 'Siap',
                'keterangan' => 'Menarik data mahasiswa yang lulus, keluar, atau DO.'
            ],
            [
                'nama' => 'Transkrip Mahasiswa',
                'icon' => 'fa-file-signature',
                'fungsi' => 'GetTranskripMahasiswa / GetRiwayatNilaiMahasiswa',
                'data' => hitung_data_pull($conn, 'transkrip_mahasiswa', 'id_registrasi_mahasiswa_feeder'),
                'url' => 'pull_transkrip.php',
                'status' => 'Siap setelah mahasiswa',
                'keterangan' => 'Menarik transkrip resmi dan riwayat nilai mahasiswa.'
            ],
            [
                'nama' => 'Prestasi Mahasiswa',
                'icon' => 'fa-trophy',
                'fungsi' => 'GetListPrestasiMahasiswa',
                'data' => hitung_data_pull($conn, 'prestasi_mahasiswa'),
                'url' => 'pull_prestasi.php',
                'status' => 'Opsional',
                'keterangan' => 'Menarik data prestasi mahasiswa jika tersedia.'
            ],
        ]
    ],
];

simpan_log(
    $conn,
    $_SESSION['id_user'],
    "Membuka halaman data pull NeoFeeder",
    "Neo Feeder"
);

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
require_once __DIR__ . "/../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <?php show_alert(); ?>

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Data Pull NeoFeeder</h2>
            <p class="text-sm text-slate-500">
                Pusat pengambilan data dari NeoFeeder/PDDikti ke database SIAKAD.
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
                <?= number_format($total_log_failed); ?>
            </h2>
        </div>

    </section>

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-4 sm:p-6">

        <div class="mb-6">
            <h3 class="text-lg font-bold text-slate-800">Daftar Data yang Dapat Diambil</h3>
            <p class="text-sm text-slate-500 mt-1">
                Pull data digunakan untuk mengambil data dari NeoFeeder ke SIAKAD sebagai data awal atau pembaruan master data.
            </p>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-200">

            <table class="min-w-full text-sm">
                <thead class="bg-slate-100 text-slate-700">
                    <tr>
                        <th class="px-4 py-3 text-left w-16">No</th>
                        <th class="px-4 py-3 text-left">Data Pull</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Fungsi NeoFeeder</th>
                        <th class="px-4 py-3 text-center hidden lg:table-cell">Total Lokal</th>
                        <th class="px-4 py-3 text-center hidden lg:table-cell">Sudah Mapping</th>
                        <th class="px-4 py-3 text-center hidden lg:table-cell">Belum Mapping</th>
                        <th class="px-4 py-3 text-center w-44">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <?php $no = 1; ?>
                    <?php foreach ($pull_groups as $group): ?>
                        <tr class="bg-slate-50">
                            <td colspan="7" class="px-4 py-3 font-bold text-slate-700">
                                <?= htmlspecialchars($group['kategori']); ?>
                            </td>
                        </tr>

                        <?php foreach ($group['items'] as $item): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3"><?= $no++; ?></td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-11 h-11 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center">
                                            <i class="fa-solid <?= $item['icon']; ?>"></i>
                                        </div>

                                        <div>
                                            <div class="font-semibold text-slate-800">
                                                <?= htmlspecialchars($item['nama']); ?>
                                            </div>

                                            <div class="text-xs text-slate-500">
                                                <?= htmlspecialchars($item['keterangan']); ?>
                                            </div>

                                            <div class="text-xs text-blue-600 mt-1">
                                                <?= htmlspecialchars($item['status']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <?= htmlspecialchars($item['fungsi']); ?>
                                </td>

                                <td class="px-4 py-3 text-center hidden lg:table-cell">
                                    <?= number_format($item['data']['total']); ?>
                                </td>

                                <td class="px-4 py-3 text-center hidden lg:table-cell">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">
                                        <?= number_format($item['data']['sinkron']); ?>
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-center hidden lg:table-cell">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-700">
                                        <?= number_format($item['data']['belum']); ?>
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex justify-center">
                                        <a href="<?= htmlspecialchars($item['url']); ?>"
                                           class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold text-xs">
                                            <i class="fa-solid fa-download mr-2"></i>
                                            Pull Data
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
            <h3 class="text-lg font-bold text-slate-800 mb-4">Urutan Pull yang Disarankan</h3>

            <ol class="list-decimal pl-5 text-sm text-slate-600 space-y-2">
                <li>Profil Perguruan Tinggi.</li>
                <li>Program Studi.</li>
                <li>Referensi PDDikti.</li>
                <li>Semester / Tahun Akademik.</li>
                <li>Mahasiswa aktif.</li>
                <li>Dosen.</li>
                <li>Kurikulum, Mata Kuliah, dan Mata Kuliah Kurikulum.</li>
            </ol>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Catatan Penting</h3>

            <p class="text-sm text-slate-600">
                Pull data digunakan untuk mengambil data dari NeoFeeder sebagai sumber awal.
                Setelah data masuk ke SIAKAD, perubahan harian tetap dilakukan di SIAKAD,
                lalu dikirim kembali melalui menu sinkronisasi.
            </p>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Akses Cepat</h3>

            <div class="space-y-3">
                <a href="../sinkronisasi/data_sync.php"
                   class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-green-100 hover:bg-green-200 text-green-700 font-semibold">
                    <i class="fa-solid fa-upload mr-2"></i>
                    Data Sync ke NeoFeeder
                </a>

                <a href="pengaturan.php"
                   class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold">
                    <i class="fa-solid fa-gear mr-2"></i>
                    Pengaturan NeoFeeder
                </a>

                <a href="log_sinkronisasi.php"
                   class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-purple-100 hover:bg-purple-200 text-purple-700 font-semibold">
                    <i class="fa-solid fa-clock-rotate-left mr-2"></i>
                    Log Integrasi
                </a>
            </div>
        </div>

    </section>

</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
