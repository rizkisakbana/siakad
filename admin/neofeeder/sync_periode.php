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

$page_title = "Sinkronisasi Periode";
$page_subtitle = "Sinkronisasi tahun akademik/periode dari Neo Feeder PDDikti";

$config = get_neofeeder_config($conn);

function ambil_periode_feeder($conn)
{
    $hasil = neofeeder_request(
        $conn,
        'GetPeriode',
        '',
        '',
        '',
        '',
        null,
        'Periode'
    );

    if ($hasil['success'] && !empty($hasil['data'])) {
        return $hasil;
    }

    $hasil_semester = neofeeder_request(
        $conn,
        'GetSemester',
        '',
        'id_semester DESC',
        '',
        '',
        null,
        'Periode'
    );

    return $hasil_semester;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_periode'])) {

    $response = ambil_periode_feeder($conn);

    if (!$response['success']) {
        set_alert("error", "Gagal mengambil periode dari Neo Feeder: " . $response['message']);
    } else {

        $data_feeder = $response['data'] ?? [];

        $berhasil_insert = 0;
        $berhasil_update = 0;
        $gagal = 0;
        $pesan_gagal = [];
        $periode_diproses = [];

        foreach ($data_feeder as $item) {

            $periode_pelaporan_raw = $item['periode_pelaporan'] ?? ($item['id_semester'] ?? '');
            $periode_pelaporan = mysqli_real_escape_string($conn, $periode_pelaporan_raw);
            $tipe_periode = mysqli_real_escape_string($conn, $item['tipe_periode'] ?? '');
            $nama_semester_feeder = mysqli_real_escape_string($conn, $item['nama_semester'] ?? '');
            $raw_feeder_data = mysqli_real_escape_string($conn, json_encode($item, JSON_UNESCAPED_UNICODE));

            if (empty($periode_pelaporan)) {
                $gagal++;
                $pesan_gagal[] = "Data periode tidak memiliki periode_pelaporan atau id_semester.";
                continue;
            }

            if (in_array($periode_pelaporan, $periode_diproses, true)) {
                continue;
            }

            $periode_diproses[] = $periode_pelaporan;

            $id_feeder = $periode_pelaporan;

            $tahun_awal = substr($periode_pelaporan, 0, 4);
            $kode_semester = substr($periode_pelaporan, -1);

            $tahun_ajaran = $tahun_awal . "/" . ($tahun_awal + 1);

            if ($kode_semester == '1') {
                $semester_lokal = 'Ganjil';
            } elseif ($kode_semester == '2') {
                $semester_lokal = 'Genap';
            } elseif ($kode_semester == '3') {
                $semester_lokal = 'Pendek';
            } else {
                $semester_lokal = 'Ganjil';
            }

            if (empty($nama_semester_feeder)) {
                $nama_semester_feeder = $semester_lokal;
            }

            $status_lokal = 'nonaktif';

            $lokal = nf_query_one($conn, "
                SELECT id_tahun 
                FROM tahun_akademik
                WHERE id_feeder = '$id_feeder'
                OR id_semester_feeder = '$id_feeder'
                OR periode_pelaporan = '$periode_pelaporan'
                OR (tahun = '$tahun_ajaran' AND semester = '$semester_lokal')
                LIMIT 1
            ");

            if ($lokal) {
                $id_tahun = intval($lokal['id_tahun']);

                $update = mysqli_query($conn, "
                    UPDATE tahun_akademik SET
                        id_feeder = '$id_feeder',
                        id_semester_feeder = '$id_feeder',
                        periode_pelaporan = '$periode_pelaporan',
                        tahun = '$tahun_ajaran',
                        tahun_ajaran = '$tahun_ajaran',
                        semester = '$semester_lokal',
                        kode_semester = '$kode_semester',
                        nama_semester = '$nama_semester_feeder',
                        tipe_periode = '$tipe_periode',
                        raw_feeder_data = '$raw_feeder_data',
                        status_sync_feeder = 'sudah',
                        last_sync_feeder = NOW(),
                        last_error_feeder = NULL,
                        status = '$status_lokal'
                    WHERE id_tahun = '$id_tahun'
                ");

                if ($update) {
                    $berhasil_update++;
                } else {
                    $gagal++;
                    $pesan_gagal[] = "Gagal update periode: $id_feeder - $tahun_ajaran $semester_lokal";
                }

            } else {
                $insert = mysqli_query($conn, "
                    INSERT INTO tahun_akademik
                    (
                        id_feeder,
                        id_semester_feeder,
                        periode_pelaporan,
                        tahun,
                        tahun_ajaran,
                        semester,
                        kode_semester,
                        nama_semester,
                        tipe_periode,
                        raw_feeder_data,
                        status_sync_feeder,
                        last_sync_feeder,
                        last_error_feeder,
                        status
                    )
                    VALUES
                    (
                        '$id_feeder',
                        '$id_feeder',
                        '$periode_pelaporan',
                        '$tahun_ajaran',
                        '$tahun_ajaran',
                        '$semester_lokal',
                        '$kode_semester',
                        '$nama_semester_feeder',
                        '$tipe_periode',
                        '$raw_feeder_data',
                        'sudah',
                        NOW(),
                        NULL,
                        '$status_lokal'
                    )
                ");

                if ($insert) {
                    $berhasil_insert++;
                } else {
                    $gagal++;
                    $pesan_gagal[] = "Gagal insert periode: $id_feeder - $tahun_ajaran $semester_lokal";
                }
            }
        }

        simpan_log(
            $conn,
            $_SESSION['id_user'],
            "Sinkronisasi periode Neo Feeder. Insert: $berhasil_insert, Update: $berhasil_update, Gagal: $gagal",
            "Neo Feeder"
        );

        $_SESSION['sync_periode_summary'] = [
            'total' => count($data_feeder),
            'insert' => $berhasil_insert,
            'update' => $berhasil_update,
            'gagal' => $gagal,
            'pesan_gagal' => $pesan_gagal
        ];

        if ($gagal > 0) {
            set_alert("warning", "Sinkronisasi periode selesai dengan beberapa data gagal.");
        } else {
            set_alert("success", "Sinkronisasi periode berhasil.");
        }

        header("Location: sync_periode.php");
        exit;
    }
}

$total_periode = nf_count($conn, "
    SELECT COUNT(*) AS total FROM tahun_akademik
");

$total_sinkron = nf_count($conn, "
    SELECT COUNT(*) AS total
    FROM tahun_akademik
    WHERE (id_semester_feeder IS NOT NULL AND id_semester_feeder != '')
    OR (id_feeder IS NOT NULL AND id_feeder != '')
");

$total_belum = $total_periode - $total_sinkron;
if ($total_belum < 0)
    $total_belum = 0;

$data_periode = nf_fetch_all($conn, "
    SELECT *
    FROM tahun_akademik
    ORDER BY tahun DESC, semester ASC
");

$summary = $_SESSION['sync_periode_summary'] ?? null;
unset($_SESSION['sync_periode_summary']);

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
            <h2 class="text-xl font-bold text-slate-800">Sinkronisasi Periode</h2>
            <p class="text-sm text-slate-500">
                Mengambil tahun akademik/periode dari Neo Feeder dan mencocokkannya dengan data lokal.
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <a href="sinkronisasi.php"
                class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                <i class="fa-solid fa-arrow-left mr-2"></i>
                Kembali
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
            <p class="text-sm text-slate-500">Status Neo Feeder</p>
            <div class="mt-3">
                <span class="inline-flex px-4 py-2 rounded-full text-sm font-bold <?= $status_class; ?>">
                    <?= $status_label; ?>
                </span>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Total Periode Lokal</p>
            <h2 class="text-3xl font-bold text-blue-700 mt-2"><?= number_format($total_periode); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Sudah Sinkron</p>
            <h2 class="text-3xl font-bold text-green-700 mt-2"><?= number_format($total_sinkron); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Belum Sinkron</p>
            <h2 class="text-3xl font-bold text-orange-700 mt-2"><?= number_format($total_belum); ?></h2>
        </div>

    </section>

    <?php if ($summary): ?>
        <section class="grid grid-cols-1 sm:grid-cols-4 gap-5 mb-6">
            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
                <p class="text-sm text-slate-500">Data Dari Feeder</p>
                <h2 class="text-3xl font-bold text-blue-700 mt-2"><?= number_format($summary['total']); ?></h2>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
                <p class="text-sm text-slate-500">Insert Baru</p>
                <h2 class="text-3xl font-bold text-green-700 mt-2"><?= number_format($summary['insert']); ?></h2>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
                <p class="text-sm text-slate-500">Update Data</p>
                <h2 class="text-3xl font-bold text-purple-700 mt-2"><?= number_format($summary['update']); ?></h2>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
                <p class="text-sm text-slate-500">Gagal</p>
                <h2 class="text-3xl font-bold text-red-700 mt-2"><?= number_format($summary['gagal']); ?></h2>
            </div>
        </section>

        <?php if (!empty($summary['pesan_gagal'])): ?>
            <div class="mb-6 p-5 rounded-2xl bg-yellow-50 border border-yellow-200 text-yellow-700">
                <h3 class="font-bold mb-3">Catatan Data Gagal</h3>
                <ul class="list-disc pl-5 text-sm space-y-1">
                    <?php foreach ($summary['pesan_gagal'] as $pesan): ?>
                        <li><?= htmlspecialchars($pesan); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg border border-slate-100 p-4 sm:p-6">

            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Data Tahun Akademik Lokal</h3>
                    <p class="text-sm text-slate-500 mt-1">
                        Periode yang memiliki ID Feeder berarti sudah termapping dengan Neo Feeder.
                    </p>
                </div>

                <form method="POST">
                    <button type="submit" name="sync_periode" value="1"
                        onclick="return confirm('Sinkronisasi periode dari Neo Feeder sekarang?')"
                        class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                        <i class="fa-solid fa-rotate mr-2"></i>
                        Sinkronkan Periode
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200">

                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100 text-slate-700">
                        <tr>
                            <th class="px-4 py-3 text-left w-16">No</th>
                            <th class="px-4 py-3 text-left">Tahun Akademik</th>
                            <th class="px-4 py-3 text-left hidden lg:table-cell">Semester</th>
                            <th class="px-4 py-3 text-left hidden lg:table-cell">ID Feeder</th>
                            <th class="px-4 py-3 text-left hidden lg:table-cell">Status Lokal</th>
                            <th class="px-4 py-3 text-left hidden lg:table-cell">Status Sync</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        <?php if (!empty($data_periode)): ?>
                            <?php $no = 1; ?>
                            <?php foreach ($data_periode as $row): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3"><?= $no++; ?></td>

                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-slate-800">
                                            <?= htmlspecialchars($row['tahun']); ?>
                                        </div>
                                        <div class="text-xs text-slate-500">
                                            <?= htmlspecialchars(ucfirst($row['semester'])); ?>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 hidden lg:table-cell capitalize">
                                        <?= htmlspecialchars($row['semester']); ?>
                                    </td>

                                    <td class="px-4 py-3 hidden lg:table-cell">
                                        <?= !empty($row['id_semester_feeder']) ? htmlspecialchars($row['id_semester_feeder']) : (!empty($row['id_feeder']) ? htmlspecialchars($row['id_feeder']) : '-'); ?>
                                    </td>

                                    <td class="px-4 py-3 hidden lg:table-cell">
                                        <span
                                            class="px-3 py-1 rounded-full text-xs font-bold <?= $row['status'] == 'aktif' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-700'; ?>">
                                            <?= htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 hidden lg:table-cell">
                                        <?php if (!empty($row['id_semester_feeder']) || !empty($row['id_feeder'])): ?>
                                            <span
                                                class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">Sinkron</span>
                                        <?php else: ?>
                                            <span
                                                class="px-3 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-700">Belum</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-slate-500">
                                    Data tahun akademik belum tersedia.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

            </div>

        </div>

        <aside class="space-y-6">

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Alur Sinkronisasi Periode</h3>

                <ol class="list-decimal pl-5 text-sm text-slate-600 space-y-2">
                    <li>Sistem mengambil token Neo Feeder.</li>
                    <li>Sistem mencoba fungsi <strong>GetPeriode</strong>.</li>
                    <li>Jika kosong, sistem mencoba <strong>GetSemester</strong>.</li>
                    <li>Data dicocokkan ke tabel <strong>tahun_akademik</strong>.</li>
                    <li>Jika periode aktif ditemukan, sistem memastikan hanya satu tahun akademik aktif.</li>
                </ol>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Catatan Penting</h3>

                <div class="space-y-3 text-sm text-slate-600">
                    <p>
                        Pastikan tabel <strong>tahun_akademik</strong> memiliki kolom <strong>id_feeder</strong>.
                    </p>

                    <p>
                        Periode sangat penting karena menjadi dasar Jadwal, KRS, Nilai, KHS, dan pelaporan PDDikti.
                    </p>
                </div>
            </div>

        </aside>

    </section>

</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
