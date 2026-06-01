<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";
require_once "../../includes/log_aktivitas.php";
require_once "../../includes/neofeeder_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Sinkronisasi Wilayah PDDikti";

$limit = 5000;
$offset = intval($_GET['offset'] ?? 0);
$summary = null;

if (isset($_POST['sync_wilayah'])) {

    $response = neofeeder_request(
        $conn,
        'GetWilayah',
        '',
        'nama_wilayah ASC',
        $limit,
        $offset,
        null,
        'Wilayah PDDikti'
    );

    if (!$response['success']) {
        set_alert("error", "Gagal mengambil data wilayah: " . $response['message']);
    } else {
        $data_feeder = $response['data'] ?? [];

        $insert = 0;
        $update = 0;
        $gagal = 0;

        foreach ($data_feeder as $item) {

            $id_feeder_raw = $item['id_wilayah'] ?? '';
            $nama_ref_raw = $item['nama_wilayah'] ?? '';
            $id_induk_raw = $item['id_induk_wilayah'] ?? null;

            if (empty($id_feeder_raw) || empty($nama_ref_raw)) {
                $gagal++;
                continue;
            }

            $jenis_ref = 'wilayah';
            $id_feeder = mysqli_real_escape_string($conn, $id_feeder_raw);
            $id_induk = !empty($id_induk_raw) ? "'" . mysqli_real_escape_string($conn, $id_induk_raw) . "'" : "NULL";
            $kode_ref = mysqli_real_escape_string($conn, $id_feeder_raw);
            $nama_ref = mysqli_real_escape_string($conn, $nama_ref_raw);
            $raw_data = mysqli_real_escape_string($conn, json_encode($item, JSON_UNESCAPED_UNICODE));

            $cek = mysqli_query($conn, "
                SELECT id_ref
                FROM ref_pddikti
                WHERE jenis_ref = 'wilayah'
                AND id_feeder = '$id_feeder'
                LIMIT 1
            ");

            if ($cek && mysqli_num_rows($cek) > 0) {
                $lokal = mysqli_fetch_assoc($cek);
                $id_ref = intval($lokal['id_ref']);

                $q = mysqli_query($conn, "
                    UPDATE ref_pddikti SET
                        id_induk_feeder = $id_induk,
                        kode_ref = '$kode_ref',
                        nama_ref = '$nama_ref',
                        raw_data = '$raw_data',
                        status = 'aktif'
                    WHERE id_ref = '$id_ref'
                ");

                $q ? $update++ : $gagal++;

            } else {
                $q = mysqli_query($conn, "
                    INSERT INTO ref_pddikti
                    (
                        jenis_ref,
                        id_feeder,
                        id_induk_feeder,
                        kode_ref,
                        nama_ref,
                        raw_data,
                        status
                    )
                    VALUES
                    (
                        '$jenis_ref',
                        '$id_feeder',
                        $id_induk,
                        '$kode_ref',
                        '$nama_ref',
                        '$raw_data',
                        'aktif'
                    )
                ");

                $q ? $insert++ : $gagal++;
            }
        }

        simpan_log(
            $conn,
            $_SESSION['id_user'],
            "Sinkronisasi wilayah PDDikti offset $offset. Insert: $insert, Update: $update, Gagal: $gagal",
            "Neo Feeder"
        );

        $_SESSION['sync_wilayah_summary'] = [
            'offset' => $offset,
            'limit' => $limit,
            'total_feeder' => count($data_feeder),
            'insert' => $insert,
            'update' => $update,
            'gagal' => $gagal,
            'next_offset' => $offset + $limit
        ];

        set_alert("success", "Sinkronisasi wilayah berhasil diproses.");
        header("Location: sync_wilayah.php?offset=" . $offset);
        exit;
    }
}

$total_wilayah = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM ref_pddikti
    WHERE jenis_ref = 'wilayah'
"))['total'] ?? 0;

$summary = $_SESSION['sync_wilayah_summary'] ?? null;
unset($_SESSION['sync_wilayah_summary']);

$data_wilayah = mysqli_query($conn, "
    SELECT *
    FROM ref_pddikti
    WHERE jenis_ref = 'wilayah'
    ORDER BY nama_ref ASC
    LIMIT 20
");

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <?php show_alert(); ?>

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Sinkronisasi Wilayah PDDikti</h2>
            <p class="text-sm text-slate-500">
                Sinkronisasi wilayah dilakukan bertahap per 5000 data agar tidak membebani NeoFeeder.
            </p>
        </div>

        <a href="sync_referensi.php"
           class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
            Kembali
        </a>
    </div>

    <section class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Total Wilayah Lokal</p>
            <h2 class="text-3xl font-bold text-blue-700 mt-2">
                <?= number_format($total_wilayah); ?>
            </h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Offset Saat Ini</p>
            <h2 class="text-3xl font-bold text-purple-700 mt-2">
                <?= number_format($offset); ?>
            </h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Limit Per Sync</p>
            <h2 class="text-3xl font-bold text-green-700 mt-2">
                <?= number_format($limit); ?>
            </h2>
        </div>

    </section>

    <?php if ($summary): ?>
        <section class="grid grid-cols-1 sm:grid-cols-4 gap-5 mb-6">
            <div class="bg-white rounded-2xl shadow border p-5">
                <p class="text-sm text-slate-500">Data Feeder</p>
                <h2 class="text-3xl font-bold text-blue-700 mt-2"><?= number_format($summary['total_feeder']); ?></h2>
            </div>
            <div class="bg-white rounded-2xl shadow border p-5">
                <p class="text-sm text-slate-500">Insert</p>
                <h2 class="text-3xl font-bold text-green-700 mt-2"><?= number_format($summary['insert']); ?></h2>
            </div>
            <div class="bg-white rounded-2xl shadow border p-5">
                <p class="text-sm text-slate-500">Update</p>
                <h2 class="text-3xl font-bold text-purple-700 mt-2"><?= number_format($summary['update']); ?></h2>
            </div>
            <div class="bg-white rounded-2xl shadow border p-5">
                <p class="text-sm text-slate-500">Gagal</p>
                <h2 class="text-3xl font-bold text-red-700 mt-2"><?= number_format($summary['gagal']); ?></h2>
            </div>
        </section>
    <?php endif; ?>

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6 mb-6">

        <h3 class="text-lg font-bold text-slate-800 mb-4">Proses Sinkronisasi</h3>

        <form method="POST" class="flex flex-col sm:flex-row gap-3 mb-4">
            <button type="submit"
                    name="sync_wilayah"
                    value="1"
                    onclick="return confirm('Sinkronisasi wilayah offset <?= $offset; ?> sekarang?')"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                Sinkronkan Offset <?= number_format($offset); ?>
            </button>

            <a href="sync_wilayah.php?offset=<?= $offset + $limit; ?>"
               class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-green-100 hover:bg-green-200 text-green-700 font-semibold">
                Lanjut Offset <?= number_format($offset + $limit); ?>
            </a>

            <?php if ($offset >= $limit): ?>
                <a href="sync_wilayah.php?offset=<?= max(0, $offset - $limit); ?>"
                   class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold">
                    Kembali Offset <?= number_format(max(0, $offset - $limit)); ?>
                </a>
            <?php endif; ?>
        </form>

        <div class="p-4 rounded-xl bg-yellow-50 border border-yellow-200 text-yellow-700 text-sm">
            Jika hasil Data Feeder sudah 0, berarti data wilayah sudah selesai ditarik sampai offset terakhir.
        </div>

    </section>

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

        <h3 class="text-lg font-bold text-slate-800 mb-4">Contoh Data Wilayah Lokal</h3>

        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-100">
                    <tr>
                        <th class="px-4 py-3 text-left">ID Feeder</th>
                        <th class="px-4 py-3 text-left">Induk</th>
                        <th class="px-4 py-3 text-left">Nama Wilayah</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if ($data_wilayah && mysqli_num_rows($data_wilayah) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($data_wilayah)): ?>
                            <tr>
                                <td class="px-4 py-3"><?= htmlspecialchars($row['id_feeder']); ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($row['id_induk_feeder'] ?? '-'); ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($row['nama_ref']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-slate-500">
                                Data wilayah belum tersedia.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </section>

</main>

<?php require_once "../../includes/footer.php"; ?>