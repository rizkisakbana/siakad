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

$page_title = "Sinkronisasi Prodi";
$page_subtitle = "Sinkronisasi data program studi dari Neo Feeder PDDikti";

$config = get_neofeeder_config($conn);

$hasil_sync = null;

function normalisasi_jenjang_prodi($value)
{
    $value = strtoupper(trim((string) $value));

    if ($value === '') {
        return 'D3';
    }

    if (str_contains($value, 'DIPLOMA TIGA') || str_contains($value, 'D-III') || str_contains($value, 'DIII')) {
        return 'D3';
    }

    if (str_contains($value, 'DIPLOMA EMPAT') || str_contains($value, 'SARJANA TERAPAN') || str_contains($value, 'D-IV') || str_contains($value, 'DIV')) {
        return 'D4';
    }

    if (str_contains($value, 'SARJANA') || $value === 'S1') {
        return 'S1';
    }

    if (str_contains($value, 'MAGISTER') || $value === 'S2') {
        return 'S2';
    }

    if (str_contains($value, 'DOKTOR') || $value === 'S3') {
        return 'S3';
    }

    return in_array($value, ['D3', 'D4', 'S1', 'S2', 'S3'], true) ? $value : 'D3';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_prodi'])) {

    $response = neofeeder_request(
        $conn,
        'GetProdi',
        '',
        '',
        '',
        '',
        null,
        'Prodi'
    );

    if (!$response['success']) {
        set_alert("error", "Gagal mengambil data prodi dari Neo Feeder: " . $response['message']);
    } else {
        $data_feeder = $response['data'] ?? [];

        $berhasil_update = 0;
        $berhasil_insert = 0;
        $gagal = 0;
        $pesan_gagal = [];

        foreach ($data_feeder as $item) {

            $id_feeder = mysqli_real_escape_string($conn, $item['id_prodi'] ?? '');
            $kode_prodi = mysqli_real_escape_string($conn, $item['kode_program_studi'] ?? '');
            $nama_prodi = mysqli_real_escape_string($conn, $item['nama_program_studi'] ?? '');
            $nama_jenjang_pendidikan = mysqli_real_escape_string($conn, $item['nama_jenjang_pendidikan'] ?? '');
            $jenjang = mysqli_real_escape_string($conn, normalisasi_jenjang_prodi($item['nama_jenjang_pendidikan'] ?? ''));
            $id_jenjang_pendidikan = mysqli_real_escape_string($conn, $item['id_jenjang_pendidikan'] ?? '');
            $status_prodi_feeder = mysqli_real_escape_string($conn, $item['status'] ?? '');
            $raw_feeder_data = mysqli_real_escape_string($conn, json_encode($item, JSON_UNESCAPED_UNICODE));
            $status = 'aktif';

            if (empty($id_feeder) || empty($kode_prodi) || empty($nama_prodi)) {
                $gagal++;
                $pesan_gagal[] = "Data prodi tidak lengkap dari Neo Feeder.";
                continue;
            }

            $lokal = nf_query_one($conn, "
                SELECT id_prodi 
                FROM prodi
                WHERE id_feeder = '$id_feeder'
                OR id_prodi_feeder = '$id_feeder'
                OR kode_prodi = '$kode_prodi'
                LIMIT 1
            ");

            if ($lokal) {
                $id_prodi_lokal = intval($lokal['id_prodi']);

                $update = mysqli_query($conn, "
                    UPDATE prodi SET
                        id_feeder = '$id_feeder',
                        id_prodi_feeder = '$id_feeder',
                        kode_prodi = '$kode_prodi',
                        nama_prodi = '$nama_prodi',
                        jenjang = '$jenjang',
                        id_jenjang_pendidikan_feeder = '$id_jenjang_pendidikan',
                        nama_jenjang_pendidikan = '$nama_jenjang_pendidikan',
                        status_prodi_feeder = '$status_prodi_feeder',
                        raw_feeder_data = '$raw_feeder_data',
                        status_sync_feeder = 'sudah',
                        last_sync_feeder = NOW(),
                        last_error_feeder = NULL,
                        status = '$status'
                    WHERE id_prodi = '$id_prodi_lokal'
                ");

                if ($update) {
                    $berhasil_update++;
                } else {
                    $gagal++;
                    $pesan_gagal[] = "Gagal update prodi: $kode_prodi - $nama_prodi";
                }

            } else {
                $insert = mysqli_query($conn, "
                    INSERT INTO prodi
                    (
                        id_feeder,
                        id_prodi_feeder,
                        kode_prodi,
                        nama_prodi,
                        jenjang,
                        id_jenjang_pendidikan_feeder,
                        nama_jenjang_pendidikan,
                        status_prodi_feeder,
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
                        '$kode_prodi',
                        '$nama_prodi',
                        '$jenjang',
                        '$id_jenjang_pendidikan',
                        '$nama_jenjang_pendidikan',
                        '$status_prodi_feeder',
                        '$raw_feeder_data',
                        'sudah',
                        NOW(),
                        NULL,
                        '$status'
                    )
                ");

                if ($insert) {
                    $berhasil_insert++;
                } else {
                    $gagal++;
                    $pesan_gagal[] = "Gagal insert prodi: $kode_prodi - $nama_prodi";
                }
            }
        }

        simpan_log(
            $conn,
            $_SESSION['id_user'],
            "Sinkronisasi prodi dari Neo Feeder. Insert: $berhasil_insert, Update: $berhasil_update, Gagal: $gagal",
            "Neo Feeder"
        );

        $_SESSION['sync_prodi_summary'] = [
            'total' => count($data_feeder),
            'insert' => $berhasil_insert,
            'update' => $berhasil_update,
            'gagal' => $gagal,
            'pesan_gagal' => $pesan_gagal
        ];

        if ($gagal > 0) {
            set_alert("warning", "Sinkronisasi selesai dengan beberapa data gagal.");
        } else {
            set_alert("success", "Sinkronisasi prodi berhasil.");
        }

        header("Location: sync_prodi.php");
        exit;
    }
}

$total_prodi = nf_count($conn, "
    SELECT COUNT(*) AS total FROM prodi
");

$total_sinkron = nf_count($conn, "
    SELECT COUNT(*) AS total 
    FROM prodi 
    WHERE (id_prodi_feeder IS NOT NULL AND id_prodi_feeder != '')
    OR (id_feeder IS NOT NULL AND id_feeder != '')
");

$total_belum = $total_prodi - $total_sinkron;
if ($total_belum < 0) $total_belum = 0;

$data_prodi = nf_fetch_all($conn, "
    SELECT *
    FROM prodi
    ORDER BY nama_prodi ASC
");

$summary = $_SESSION['sync_prodi_summary'] ?? null;
unset($_SESSION['sync_prodi_summary']);

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
            <h2 class="text-xl font-bold text-slate-800">Sinkronisasi Program Studi</h2>
            <p class="text-sm text-slate-500">
                Mengambil dan mencocokkan data program studi dari Neo Feeder/PDDikti ke database SIAKAD.
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
            <p class="text-sm text-slate-500">Total Prodi Lokal</p>
            <h2 class="text-3xl font-bold text-blue-700 mt-2">
                <?= number_format($total_prodi); ?>
            </h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Sudah Sinkron</p>
            <h2 class="text-3xl font-bold text-green-700 mt-2">
                <?= number_format($total_sinkron); ?>
            </h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Belum Sinkron</p>
            <h2 class="text-3xl font-bold text-orange-700 mt-2">
                <?= number_format($total_belum); ?>
            </h2>
        </div>

    </section>

    <?php if ($summary): ?>
        <section class="grid grid-cols-1 sm:grid-cols-4 gap-5 mb-6">
            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
                <p class="text-sm text-slate-500">Data Dari Feeder</p>
                <h2 class="text-3xl font-bold text-blue-700 mt-2">
                    <?= number_format($summary['total']); ?>
                </h2>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
                <p class="text-sm text-slate-500">Insert Baru</p>
                <h2 class="text-3xl font-bold text-green-700 mt-2">
                    <?= number_format($summary['insert']); ?>
                </h2>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
                <p class="text-sm text-slate-500">Update Data</p>
                <h2 class="text-3xl font-bold text-purple-700 mt-2">
                    <?= number_format($summary['update']); ?>
                </h2>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
                <p class="text-sm text-slate-500">Gagal</p>
                <h2 class="text-3xl font-bold text-red-700 mt-2">
                    <?= number_format($summary['gagal']); ?>
                </h2>
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
                    <h3 class="text-lg font-bold text-slate-800">Data Program Studi Lokal</h3>
                    <p class="text-sm text-slate-500 mt-1">
                        Prodi yang sudah memiliki ID Feeder berarti sudah termapping dengan Neo Feeder.
                    </p>
                </div>

                <form method="POST">
                    <button type="submit"
                            name="sync_prodi"
                            value="1"
                            onclick="return confirm('Sinkronisasi data prodi dari Neo Feeder sekarang?')"
                            class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                        <i class="fa-solid fa-rotate mr-2"></i>
                        Sinkronkan Prodi
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200">

                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100 text-slate-700">
                        <tr>
                            <th class="px-4 py-3 text-left w-16">No</th>
                            <th class="px-4 py-3 text-left">Program Studi</th>
                            <th class="px-4 py-3 text-left hidden lg:table-cell">Kode</th>
                            <th class="px-4 py-3 text-left hidden lg:table-cell">Jenjang</th>
                            <th class="px-4 py-3 text-left hidden lg:table-cell">ID Feeder</th>
                            <th class="px-4 py-3 text-left hidden lg:table-cell">Status</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        <?php if (!empty($data_prodi)): ?>
                            <?php $no = 1; ?>
                            <?php foreach ($data_prodi as $row): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3"><?= $no++; ?></td>

                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-slate-800">
                                            <?= htmlspecialchars($row['nama_prodi']); ?>
                                        </div>
                                        <div class="text-xs text-slate-500">
                                            <?= htmlspecialchars($row['kode_prodi'] ?? '-'); ?> • <?= htmlspecialchars($row['jenjang'] ?? '-'); ?>
                                        </div>

                                        <div class="lg:hidden mt-2">
                                            <?php if (!empty($row['id_prodi_feeder']) || !empty($row['id_feeder'])): ?>
                                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">
                                                    Sinkron
                                                </span>
                                            <?php else: ?>
                                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-700">
                                                    Belum Sinkron
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 hidden lg:table-cell">
                                        <?= htmlspecialchars($row['kode_prodi'] ?? '-'); ?>
                                    </td>

                                    <td class="px-4 py-3 hidden lg:table-cell">
                                        <?= htmlspecialchars($row['jenjang'] ?? '-'); ?>
                                    </td>

                                    <td class="px-4 py-3 hidden lg:table-cell">
                                        <?= !empty($row['id_prodi_feeder']) ? htmlspecialchars($row['id_prodi_feeder']) : (!empty($row['id_feeder']) ? htmlspecialchars($row['id_feeder']) : '-'); ?>
                                    </td>

                                    <td class="px-4 py-3 hidden lg:table-cell">
                                        <?php if (!empty($row['id_prodi_feeder']) || !empty($row['id_feeder'])): ?>
                                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">
                                                Sinkron
                                            </span>
                                        <?php else: ?>
                                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-700">
                                                Belum Sinkron
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-slate-500">
                                    Data program studi belum tersedia.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

            </div>

        </div>

        <aside class="space-y-6">

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">
                    Alur Sinkronisasi Prodi
                </h3>

                <ol class="list-decimal pl-5 text-sm text-slate-600 space-y-2">
                    <li>Sistem mengambil token Neo Feeder.</li>
                    <li>Sistem memanggil fungsi <strong>GetAllProdi</strong>.</li>
                    <li>Data dicocokkan berdasarkan <strong>id_feeder</strong> atau <strong>kode_prodi</strong>.</li>
                    <li>Jika sudah ada, data lokal diperbarui.</li>
                    <li>Jika belum ada, data prodi baru ditambahkan.</li>
                </ol>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">
                    Catatan Penting
                </h3>

                <div class="space-y-3 text-sm text-slate-600">
                    <p>
                        Sinkronisasi Prodi harus dilakukan sebelum sinkronisasi mahasiswa, dosen, kurikulum, mata kuliah, dan kelas kuliah.
                    </p>

                    <p>
                        Pastikan tabel <strong>prodi</strong> sudah memiliki kolom <strong>id_feeder</strong>.
                    </p>
                </div>
            </div>

        </aside>

    </section>

</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
