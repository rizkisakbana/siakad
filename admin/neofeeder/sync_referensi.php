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

$page_title = "Sinkronisasi Referensi PDDikti";
$page_subtitle = "Sinkronisasi data referensi dari Neo Feeder PDDikti";

$config = get_neofeeder_config($conn);

$daftar_ref = [
    'agama' => [
        'label' => 'Agama',
        'act' => 'GetAgama',
        'id_keys' => ['id_agama', 'kode_agama', 'id'],
        'nama_keys' => ['nama_agama', 'agama', 'nama']
    ],
    'jalur_masuk' => [
        'label' => 'Jalur Masuk',
        'act' => 'GetJalurMasuk',
        'id_keys' => ['id_jalur_masuk', 'kode_jalur_masuk', 'id'],
        'nama_keys' => ['nama_jalur_masuk', 'jalur_masuk', 'nama']
    ],
    'jenis_pendaftaran' => [
        'label' => 'Jenis Pendaftaran',
        'act' => 'GetJenisPendaftaran',
        'id_keys' => ['id_jenis_daftar', 'id_jenis_pendaftaran', 'kode_jenis_pendaftaran', 'id'],
        'nama_keys' => ['nama_jenis_daftar', 'nama_jenis_pendaftaran', 'jenis_pendaftaran', 'nama']
    ],
    'status_mahasiswa' => [
        'label' => 'Status Mahasiswa',
        'act' => 'GetStatusMahasiswa',
        'id_keys' => ['id_status_mahasiswa', 'kode_status_mahasiswa', 'id'],
        'nama_keys' => ['nama_status_mahasiswa', 'status_mahasiswa', 'nama']
    ],
    'jenis_keluar' => [
        'label' => 'Jenis Keluar',
        'act' => 'GetJenisKeluar',
        'id_keys' => ['id_jenis_keluar', 'kode_jenis_keluar', 'id'],
        'nama_keys' => ['nama_jenis_keluar', 'jenis_keluar', 'nama']
    ],
    'semester' => [
        'label' => 'Semester',
        'act' => 'GetSemester',
        'id_keys' => ['id_semester', 'kode_semester', 'id'],
        'nama_keys' => ['nama_semester', 'semester', 'nama']
    ],
    'tahun_ajaran' => [
        'label' => 'Tahun Ajaran',
        'act' => 'GetTahunAjaran',
        'id_keys' => ['id_tahun_ajaran', 'kode_tahun_ajaran', 'id'],
        'nama_keys' => ['nama_tahun_ajaran', 'tahun_ajaran', 'nama']
    ],
    'jenjang_pendidikan' => [
        'label' => 'Jenjang Pendidikan',
        'act' => 'GetJenjangPendidikan',
        'id_keys' => ['id_jenjang_didik', 'id_jenjang_pendidikan', 'kode_jenjang_pendidikan', 'id'],
        'nama_keys' => ['nama_jenjang_didik', 'nama_jenjang_pendidikan', 'jenjang_pendidikan', 'nama']
    ],
    'wilayah' => [
        'label' => 'Wilayah Indonesia',
        'act' => 'GetWilayah',
        'id_keys' => ['id_wilayah'],
        'nama_keys' => ['nama_wilayah'],
        'filter' => "id_negara = 'ID'",
        'order' => 'nama_wilayah ASC',
        'limit' => 5000
    ],
    'negara' => [
        'label' => 'Negara',
        'act' => 'GetNegara',
        'id_keys' => ['id_negara'],
        'nama_keys' => ['nama_negara'],
        'limit' => ''
    ],
    'pekerjaan' => [
        'label' => 'Pekerjaan',
        'act' => 'GetPekerjaan',
        'id_keys' => ['id_pekerjaan', 'kode_pekerjaan'],
        'nama_keys' => ['nama_pekerjaan'],
        'limit' => ''
    ],
    'penghasilan' => [
        'label' => 'Penghasilan',
        'act' => 'GetPenghasilan',
        'id_keys' => ['id_penghasilan'],
        'nama_keys' => ['nama_penghasilan'],
        'limit' => ''
    ],
    'alat_transportasi' => [
        'label' => 'Alat Transportasi',
        'act' => 'GetAlatTransportasi',
        'id_keys' => ['id_alat_transportasi', 'kode_alat_transportasi'],
        'nama_keys' => ['nama_alat_transportasi'],
        'limit' => ''
    ],
    'pembiayaan' => [
        'label' => 'Pembiayaan',
        'act' => 'GetPembiayaan',
        'id_keys' => ['id_pembiayaan', 'kode_pembiayaan'],
        'nama_keys' => ['nama_pembiayaan'],
        'limit' => ''
    ],
    'kebutuhan_khusus' => [
        'label' => 'Kebutuhan Khusus',
        'act' => 'GetKebutuhanKhusus',
        'id_keys' => ['id_kebutuhan_khusus'],
        'nama_keys' => ['nama_kebutuhan_khusus'],
        'limit' => ''
    ],
    'jenis_evaluasi' => [
        'label' => 'Jenis Evaluasi',
        'act' => 'GetJenisEvaluasi',
        'id_keys' => ['id_jenis_evaluasi', 'kode_jenis_evaluasi', 'id'],
        'nama_keys' => ['nama_jenis_evaluasi', 'jenis_evaluasi', 'nama']
    ],
    'ikatan_kerja_sdm' => [
        'label' => 'Ikatan Kerja SDM',
        'act' => 'GetIkatanKerjaSdm',
        'id_keys' => ['id_ikatan_kerja', 'id_ikatan_kerja_sdm', 'kode_ikatan_kerja', 'id'],
        'nama_keys' => ['nama_ikatan_kerja', 'nama_ikatan_kerja_sdm', 'ikatan_kerja_sdm', 'nama']
    ],
    'status_keaktifan_pegawai' => [
        'label' => 'Status Keaktifan Pegawai',
        'act' => 'GetStatusKeaktifanPegawai',
        'id_keys' => ['id_status_aktif', 'id_status_keaktifan_pegawai', 'kode_status_aktif', 'id'],
        'nama_keys' => ['nama_status_aktif', 'nama_status_keaktifan_pegawai', 'status_keaktifan_pegawai', 'nama']
    ],
    'status_kepegawaian' => [
        'label' => 'Status Kepegawaian',
        'act' => 'GetStatusKepegawaian',
        'id_keys' => ['id_status_kepegawaian', 'kode_status_kepegawaian', 'id'],
        'nama_keys' => ['nama_status_kepegawaian', 'status_kepegawaian', 'nama']
    ],
    'jabatan_fungsional' => [
        'label' => 'Jabatan Fungsional',
        'act' => 'GetJabfung',
        'id_keys' => ['id_jabatan_fungsional', 'id_jabfung', 'kode_jabfung', 'id'],
        'nama_keys' => ['nama_jabatan_fungsional', 'nama_jabfung', 'jabatan_fungsional', 'nama']
    ],
    'pangkat_golongan' => [
        'label' => 'Pangkat Golongan',
        'act' => 'GetPangkatGolongan',
        'id_keys' => ['id_pangkat_golongan', 'kode_pangkat_golongan', 'id'],
        'nama_keys' => ['nama_pangkat_golongan', 'pangkat_golongan', 'nama']
    ],
    'jenis_sertifikasi' => [
        'label' => 'Jenis Sertifikasi',
        'act' => 'GetJenisSertifikasi',
        'id_keys' => ['id_jenis_sertifikasi', 'kode_jenis_sertifikasi', 'id'],
        'nama_keys' => ['nama_jenis_sertifikasi', 'jenis_sertifikasi', 'nama']
    ],
    'lembaga_pengangkat' => [
        'label' => 'Lembaga Pengangkat',
        'act' => 'GetLembagaPengangkat',
        'id_keys' => ['id_lembaga_pengangkat', 'kode_lembaga_pengangkat', 'id'],
        'nama_keys' => ['nama_lembaga_pengangkat', 'lembaga_pengangkat', 'nama']
    ],
];

function ambil_nilai_ref($item, $keys)
{
    foreach ($keys as $key) {
        if (isset($item[$key]) && $item[$key] !== '') {
            return $item[$key];
        }
    }

    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_referensi'])) {
    $jenis_dipilih = $_POST['jenis_ref'] ?? [];

    if (empty($jenis_dipilih)) {
        set_alert("error", "Pilih minimal satu referensi yang akan disinkronkan.");
    } else {
        $total_data = 0;
        $berhasil_insert = 0;
        $berhasil_update = 0;
        $gagal = 0;
        $pesan_gagal = [];

        foreach ($jenis_dipilih as $jenis_ref) {
            if (!isset($daftar_ref[$jenis_ref])) {
                continue;
            }

            $ref = $daftar_ref[$jenis_ref];

            $filter = $ref['filter'] ?? '';
            $order = $ref['order'] ?? '';
            $limit = $ref['limit'] ?? '';

            $response = neofeeder_request(
                $conn,
                $ref['act'],
                $filter,
                $order,
                $limit,
                '',
                null,
                'Referensi PDDikti'
            );

            if (!$response['success']) {
                $gagal++;
                $pesan_gagal[] = $ref['label'] . ": " . $response['message'];
                continue;
            }

            $data_feeder = $response['data'] ?? [];
            $total_data += count($data_feeder);

            foreach ($data_feeder as $item) {
                $id_feeder_raw = ambil_nilai_ref($item, $ref['id_keys']);
                $nama_ref_raw = ambil_nilai_ref($item, $ref['nama_keys']);

                $id_induk_feeder_raw = null;

                if ($jenis_ref == 'wilayah') {
                    $id_induk_feeder_raw = $item['id_induk_wilayah']
                        ?? $item['id_induk']
                        ?? $item['id_wilayah_induk']
                        ?? null;
                }

                $kode_ref_raw = $item['kode'] ?? $item['kode_ref'] ?? $id_feeder_raw;

                $jenis_db = mysqli_real_escape_string($conn, $jenis_ref);
                $source_act = mysqli_real_escape_string($conn, $ref['act']);
                $id_feeder = mysqli_real_escape_string($conn, $id_feeder_raw);
                $kode_ref = mysqli_real_escape_string($conn, $kode_ref_raw);
                $nama_ref = mysqli_real_escape_string($conn, $nama_ref_raw);
                $raw_data = mysqli_real_escape_string($conn, json_encode($item, JSON_UNESCAPED_UNICODE));

                $id_induk_feeder = !empty($id_induk_feeder_raw)
                    ? mysqli_real_escape_string($conn, $id_induk_feeder_raw)
                    : null;

                $lokal = nf_query_one($conn, "
                    SELECT id_ref
                    FROM ref_pddikti
                    WHERE jenis_ref = '$jenis_db'
                    AND id_feeder = '$id_feeder'
                    LIMIT 1
                ");

                if ($lokal) {
                    $id_ref = intval($lokal['id_ref']);

                    $update = mysqli_query($conn, "
                        UPDATE ref_pddikti SET
                            source_act = '$source_act',
                            id_induk_feeder = " . ($id_induk_feeder ? "'$id_induk_feeder'" : "NULL") . ",
                            kode_ref = '$kode_ref',
                            nama_ref = '$nama_ref',
                            raw_data = '$raw_data',
                            status = 'aktif',
                            last_sync_feeder = NOW(),
                            last_error_feeder = NULL
                        WHERE id_ref = '$id_ref'
                    ");

                    if ($update) {
                        $berhasil_update++;
                    } else {
                        $gagal++;
                        $pesan_gagal[] = $ref['label'] . ": gagal update $nama_ref.";
                    }
                } else {
                    $insert = mysqli_query($conn, "
                        INSERT INTO ref_pddikti
                        (
                            jenis_ref,
                            source_act,
                            id_feeder,
                            id_induk_feeder,
                            kode_ref,
                            nama_ref,
                            raw_data,
                            status,
                            last_sync_feeder,
                            last_error_feeder
                        )
                        VALUES
                        (
                            '$jenis_db',
                            '$source_act',
                            '$id_feeder',
                            " . ($id_induk_feeder ? "'$id_induk_feeder'" : "NULL") . ",
                            '$kode_ref',
                            '$nama_ref',
                            '$raw_data',
                            'aktif',
                            NOW(),
                            NULL
                        )
                    ");

                    if ($insert) {
                        $berhasil_insert++;
                    } else {
                        $gagal++;
                        $pesan_gagal[] = $ref['label'] . ": gagal insert $nama_ref.";
                    }
                }
            }
        }

        simpan_log(
            $conn,
            $_SESSION['id_user'],
            "Sinkronisasi referensi PDDikti. Insert: $berhasil_insert, Update: $berhasil_update, Gagal: $gagal",
            "Neo Feeder"
        );

        $_SESSION['sync_referensi_summary'] = [
            'total' => $total_data,
            'insert' => $berhasil_insert,
            'update' => $berhasil_update,
            'gagal' => $gagal,
            'pesan_gagal' => $pesan_gagal
        ];

        if ($gagal > 0) {
            set_alert("warning", "Sinkronisasi referensi selesai dengan beberapa data gagal.");
        } else {
            set_alert("success", "Sinkronisasi referensi berhasil.");
        }

        header("Location: sync_referensi.php");
        exit;
    }
}

$total_ref = nf_count($conn, "
    SELECT COUNT(*) AS total FROM ref_pddikti
");

$total_jenis = nf_count($conn, "
    SELECT COUNT(DISTINCT jenis_ref) AS total FROM ref_pddikti
");

$data_ringkasan = nf_fetch_all($conn, "
    SELECT jenis_ref, COUNT(*) AS total
    FROM ref_pddikti
    GROUP BY jenis_ref
    ORDER BY jenis_ref ASC
");

$summary = $_SESSION['sync_referensi_summary'] ?? null;
unset($_SESSION['sync_referensi_summary']);

$status_class = "bg-red-100 text-red-700";
$status_label = "Disconnected";

if ($config && ($config['status'] ?? '') == 'connected') {
    $status_class = "bg-green-100 text-green-700";
    $status_label = "Connected";
}

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
require_once __DIR__ . "/../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <?php show_alert(); ?>

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Sinkronisasi Referensi PDDikti</h2>
            <p class="text-sm text-slate-500">
                Mengambil data referensi dari Neo Feeder untuk validasi mahasiswa, dosen, KRS, nilai, dan pelaporan.
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <a href="data_pull.php"
                class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                <i class="fa-solid fa-arrow-left mr-2"></i>
                Kembali
            </a>

            <a href="../sinkronisasi/log_sinkronisasi.php"
                class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-purple-700 hover:bg-purple-800 text-white font-semibold">
                <i class="fa-solid fa-clock-rotate-left mr-2"></i>
                Log
            </a>
        </div>
    </div>

    <section class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Status Neo Feeder</p>
            <div class="mt-3">
                <span class="inline-flex px-4 py-2 rounded-full text-sm font-bold <?= $status_class; ?>">
                    <?= $status_label; ?>
                </span>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Total Referensi</p>
            <h2 class="text-3xl font-bold text-blue-700 mt-2"><?= number_format($total_ref); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Jenis Referensi</p>
            <h2 class="text-3xl font-bold text-green-700 mt-2"><?= number_format($total_jenis); ?></h2>
        </div>

    </section>

    <?php if ($summary): ?>
        <section class="grid grid-cols-1 sm:grid-cols-4 gap-5 mb-6">
            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
                <p class="text-sm text-slate-500">Data Feeder</p>
                <h2 class="text-3xl font-bold text-blue-700 mt-2"><?= number_format($summary['total']); ?></h2>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
                <p class="text-sm text-slate-500">Insert</p>
                <h2 class="text-3xl font-bold text-green-700 mt-2"><?= number_format($summary['insert']); ?></h2>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
                <p class="text-sm text-slate-500">Update</p>
                <h2 class="text-3xl font-bold text-purple-700 mt-2"><?= number_format($summary['update']); ?></h2>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
                <p class="text-sm text-slate-500">Gagal</p>
                <h2 class="text-3xl font-bold text-red-700 mt-2"><?= number_format($summary['gagal']); ?></h2>
            </div>
        </section>
    <?php endif; ?>

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

            <h3 class="text-lg font-bold text-slate-800 mb-4">Pilih Referensi yang Akan Disinkronkan</h3>

            <form method="POST">

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 mb-6">
                    <?php foreach ($daftar_ref as $key => $ref): ?>
                        <label
                            class="flex items-start gap-3 p-4 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer">
                            <input type="checkbox" name="jenis_ref[]" value="<?= htmlspecialchars($key); ?>"
                                class="mt-1 w-4 h-4">
                            <span>
                                <span class="block font-semibold text-slate-800">
                                    <?= htmlspecialchars($ref['label']); ?>
                                </span>
                                <span class="block text-xs text-slate-500">
                                    <?= htmlspecialchars($ref['act']); ?>
                                </span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <button type="submit" name="sync_referensi" value="1"
                    onclick="return confirm('Sinkronisasi referensi PDDikti sekarang?')"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                    <i class="fa-solid fa-rotate mr-2"></i>
                    Sinkronkan Referensi
                </button>

                <a href="sync_wilayah.php"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-green-700 hover:bg-green-800 text-white font-semibold">
                    Sinkronisasi Wilayah Khusus
                </a>

            </form>

            <?php if (!empty($summary['pesan_gagal'])): ?>
                <div class="mt-6 p-5 rounded-2xl bg-yellow-50 border border-yellow-200 text-yellow-700">
                    <h3 class="font-bold mb-3">Catatan Data Gagal</h3>
                    <ul class="list-disc pl-5 text-sm space-y-1">
                        <?php foreach ($summary['pesan_gagal'] as $pesan): ?>
                            <li><?= htmlspecialchars($pesan); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

        </div>

        <aside class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

            <h3 class="text-lg font-bold text-slate-800 mb-4">Ringkasan Referensi Lokal</h3>

            <div class="space-y-3 text-sm">
                <?php if (!empty($data_ringkasan)): ?>
                    <?php foreach ($data_ringkasan as $row): ?>
                        <div class="flex justify-between gap-4 border-b pb-3">
                            <span class="text-slate-500"><?= htmlspecialchars($row['jenis_ref']); ?></span>
                            <span class="font-semibold text-slate-800"><?= number_format($row['total']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-slate-500">Belum ada referensi yang disinkronkan.</p>
                <?php endif; ?>
            </div>

        </aside>

    </section>

</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
