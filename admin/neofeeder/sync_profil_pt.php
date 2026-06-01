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

$page_title = "Sinkronisasi Profil PT";
$page_subtitle = "Sinkronisasi profil perguruan tinggi dari Neo Feeder PDDikti";

$config = get_neofeeder_config($conn);
$summary = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_profil_pt'])) {

    $response = neofeeder_request(
        $conn,
        'GetProfilPT',
        '',
        '',
        1,
        0,
        null,
        'Profil PT'
    );

    if (!$response['success']) {
        set_alert("error", "Gagal mengambil Profil PT dari Neo Feeder: " . $response['message']);
    } else {

        $data_feeder = $response['data'] ?? [];

        if (isset($data_feeder[0])) {
            $data_feeder = $data_feeder[0];
        }

        if (empty($data_feeder)) {
            set_alert("error", "Data Profil PT dari Neo Feeder kosong.");
        } else {

            $id_feeder = mysqli_real_escape_string($conn, $data_feeder['id_perguruan_tinggi'] ?? '');
            $kode_pt = mysqli_real_escape_string($conn, $data_feeder['kode_perguruan_tinggi'] ?? '');
            $nama_pt = mysqli_real_escape_string($conn, $data_feeder['nama_perguruan_tinggi'] ?? '');
            $raw_feeder_data = mysqli_real_escape_string($conn, json_encode($data_feeder, JSON_UNESCAPED_UNICODE));

            $telepon = mysqli_real_escape_string($conn, $data_feeder['telepon'] ?? '');
            $faximile = mysqli_real_escape_string($conn, $data_feeder['faximile'] ?? '');
            $email = mysqli_real_escape_string($conn, $data_feeder['email'] ?? '');
            $website = mysqli_real_escape_string($conn, $data_feeder['website'] ?? '');

            $jalan = mysqli_real_escape_string($conn, $data_feeder['jalan'] ?? '');
            $dusun = mysqli_real_escape_string($conn, $data_feeder['dusun'] ?? '');
            $rt_rw = mysqli_real_escape_string($conn, $data_feeder['rt_rw'] ?? '');
            $kelurahan = mysqli_real_escape_string($conn, $data_feeder['kelurahan'] ?? '');
            $kode_pos = mysqli_real_escape_string($conn, $data_feeder['kode_pos'] ?? '');

            $id_wilayah = mysqli_real_escape_string($conn, $data_feeder['id_wilayah'] ?? '');
            $nama_wilayah = mysqli_real_escape_string($conn, $data_feeder['nama_wilayah'] ?? '');
            $lintang_bujur = mysqli_real_escape_string($conn, $data_feeder['lintang_bujur'] ?? '');

            $bank = mysqli_real_escape_string($conn, $data_feeder['bank'] ?? '');
            $unit_cabang = mysqli_real_escape_string($conn, $data_feeder['unit_cabang'] ?? '');
            $nomor_rekening = mysqli_real_escape_string($conn, $data_feeder['nomor_rekening'] ?? '');

            $mbs = mysqli_real_escape_string($conn, $data_feeder['mbs'] ?? '0');
            $luas_tanah_milik = floatval($data_feeder['luas_tanah_milik'] ?? 0);
            $luas_tanah_bukan_milik = floatval($data_feeder['luas_tanah_bukan_milik'] ?? 0);

            $sk_pendirian = mysqli_real_escape_string($conn, $data_feeder['sk_pendirian'] ?? '');

            $tanggal_sk_pendirian = "NULL";
            if (!empty($data_feeder['tanggal_sk_pendirian'])) {
                $tanggal_sk_pendirian = "'" . date('Y-m-d', strtotime($data_feeder['tanggal_sk_pendirian'])) . "'";
            }

            $id_status_milik = mysqli_real_escape_string($conn, $data_feeder['id_status_milik'] ?? '');
            $nama_status_milik = mysqli_real_escape_string($conn, $data_feeder['nama_status_milik'] ?? '');
            $status_perguruan_tinggi = mysqli_real_escape_string($conn, $data_feeder['status_perguruan_tinggi'] ?? '');

            $sk_izin_operasional = mysqli_real_escape_string($conn, $data_feeder['sk_izin_operasional'] ?? '');

            $tanggal_izin_operasional = "NULL";
            if (!empty($data_feeder['tanggal_izin_operasional'])) {
                $tanggal_izin_operasional = "'" . date('Y-m-d', strtotime($data_feeder['tanggal_izin_operasional'])) . "'";
            }

            if (empty($id_feeder) || empty($kode_pt) || empty($nama_pt)) {
                set_alert("error", "Data Profil PT tidak lengkap. ID Feeder, Kode PT, dan Nama PT wajib tersedia.");
            } else {

                $profil = nf_query_one($conn, "
                    SELECT id_pt 
                    FROM profil_pt
                    WHERE id_feeder = '$id_feeder'
                    OR kode_perguruan_tinggi = '$kode_pt'
                    LIMIT 1
                ");

                if ($profil) {
                    $id_pt = intval($profil['id_pt']);

                    $simpan = mysqli_query($conn, "
                        UPDATE profil_pt SET
                            id_feeder = '$id_feeder',
                            id_perguruan_tinggi_feeder = '$id_feeder',
                            kode_perguruan_tinggi = '$kode_pt',
                            nama_perguruan_tinggi = '$nama_pt',
                            telepon = '$telepon',
                            faximile = '$faximile',
                            email = '$email',
                            website = '$website',
                            jalan = '$jalan',
                            dusun = '$dusun',
                            rt_rw = '$rt_rw',
                            kelurahan = '$kelurahan',
                            kode_pos = '$kode_pos',
                            id_wilayah = '$id_wilayah',
                            id_wilayah_feeder = '$id_wilayah',
                            nama_wilayah = '$nama_wilayah',
                            lintang_bujur = '$lintang_bujur',
                            bank = '$bank',
                            unit_cabang = '$unit_cabang',
                            nomor_rekening = '$nomor_rekening',
                            mbs = '$mbs',
                            luas_tanah_milik = '$luas_tanah_milik',
                            luas_tanah_bukan_milik = '$luas_tanah_bukan_milik',
                            sk_pendirian = '$sk_pendirian',
                            tanggal_sk_pendirian = $tanggal_sk_pendirian,
                            id_status_milik = '$id_status_milik',
                            nama_status_milik = '$nama_status_milik',
                            status_perguruan_tinggi = '$status_perguruan_tinggi',
                            sk_izin_operasional = '$sk_izin_operasional',
                            tanggal_izin_operasional = $tanggal_izin_operasional,
                            status_sinkron = 'sudah',
                            raw_feeder_data = '$raw_feeder_data',
                            status_sync_feeder = 'sudah',
                            last_sync_feeder = NOW(),
                            last_error_feeder = NULL
                        WHERE id_pt = '$id_pt'
                    ");

                    $mode = "update";
                } else {
                    $simpan = mysqli_query($conn, "
                        INSERT INTO profil_pt
                        (
                            id_feeder,
                            id_perguruan_tinggi_feeder,
                            kode_perguruan_tinggi,
                            nama_perguruan_tinggi,
                            telepon,
                            faximile,
                            email,
                            website,
                            jalan,
                            dusun,
                            rt_rw,
                            kelurahan,
                            kode_pos,
                            id_wilayah,
                            id_wilayah_feeder,
                            nama_wilayah,
                            lintang_bujur,
                            bank,
                            unit_cabang,
                            nomor_rekening,
                            mbs,
                            luas_tanah_milik,
                            luas_tanah_bukan_milik,
                            sk_pendirian,
                            tanggal_sk_pendirian,
                            id_status_milik,
                            nama_status_milik,
                            status_perguruan_tinggi,
                            sk_izin_operasional,
                            tanggal_izin_operasional,
                            status_sinkron,
                            raw_feeder_data,
                            status_sync_feeder,
                            last_sync_feeder,
                            last_error_feeder
                        )
                        VALUES
                        (
                            '$id_feeder',
                            '$id_feeder',
                            '$kode_pt',
                            '$nama_pt',
                            '$telepon',
                            '$faximile',
                            '$email',
                            '$website',
                            '$jalan',
                            '$dusun',
                            '$rt_rw',
                            '$kelurahan',
                            '$kode_pos',
                            '$id_wilayah',
                            '$id_wilayah',
                            '$nama_wilayah',
                            '$lintang_bujur',
                            '$bank',
                            '$unit_cabang',
                            '$nomor_rekening',
                            '$mbs',
                            '$luas_tanah_milik',
                            '$luas_tanah_bukan_milik',
                            '$sk_pendirian',
                            $tanggal_sk_pendirian,
                            '$id_status_milik',
                            '$nama_status_milik',
                            '$status_perguruan_tinggi',
                            '$sk_izin_operasional',
                            $tanggal_izin_operasional,
                            'sudah',
                            '$raw_feeder_data',
                            'sudah',
                            NOW(),
                            NULL
                        )
                    ");

                    $mode = "insert";
                }

                if ($simpan) {
                    mysqli_query($conn, "
                        UPDATE neofeeder_config SET
                            nama_pt = '$nama_pt',
                            kode_pt = '$kode_pt'
                        WHERE id_config = '{$config['id_config']}'
                    ");

                    simpan_log(
                        $conn,
                        $_SESSION['id_user'],
                        "Sinkronisasi Profil PT dari Neo Feeder berhasil ($mode): $kode_pt - $nama_pt",
                        "Neo Feeder"
                    );

                    $_SESSION['sync_profil_pt_summary'] = [
                        'mode' => $mode,
                        'kode_pt' => $kode_pt,
                        'nama_pt' => $nama_pt,
                        'id_feeder' => $id_feeder
                    ];

                    set_alert("success", "Sinkronisasi Profil PT berhasil.");
                    header("Location: sync_profil_pt.php");
                    exit;
                } else {
                    set_alert("error", "Gagal menyimpan Profil PT ke database lokal.");
                }
            }
        }
    }
}

$profil_pt = nf_query_one($conn, "
    SELECT *
    FROM profil_pt
    ORDER BY id_pt DESC
    LIMIT 1
");

$summary = $_SESSION['sync_profil_pt_summary'] ?? null;
unset($_SESSION['sync_profil_pt_summary']);

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
            <h2 class="text-xl font-bold text-slate-800">Sinkronisasi Profil PT</h2>
            <p class="text-sm text-slate-500">
                Mengambil Profil Perguruan Tinggi dari Neo Feeder/PDDikti dan menyimpannya ke database SIAKAD.
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
            <p class="text-sm text-slate-500">Kode PT</p>
            <h2 class="text-2xl font-bold text-blue-700 mt-2">
                <?= htmlspecialchars($profil_pt['kode_perguruan_tinggi'] ?? '-'); ?>
            </h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Status Sinkron</p>
            <h2 class="text-2xl font-bold text-green-700 mt-2 capitalize">
                <?= htmlspecialchars($profil_pt['status_sinkron'] ?? 'belum'); ?>
            </h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Terakhir Update</p>
            <h2 class="text-sm font-bold text-slate-700 mt-3">
                <?= !empty($profil_pt['updated_at']) ? tanggal_jam_indonesia($profil_pt['updated_at']) : (!empty($profil_pt['created_at']) ? tanggal_jam_indonesia($profil_pt['created_at']) : '-'); ?>
            </h2>
        </div>

    </section>

    <?php if ($summary): ?>
        <div class="mb-6 p-5 rounded-2xl bg-green-50 border border-green-200 text-green-700">
            <h3 class="font-bold mb-2">Ringkasan Sinkronisasi</h3>
            <p class="text-sm">
                Mode: <strong><?= htmlspecialchars(strtoupper($summary['mode'])); ?></strong><br>
                Kode PT: <strong><?= htmlspecialchars($summary['kode_pt']); ?></strong><br>
                Nama PT: <strong><?= htmlspecialchars($summary['nama_pt']); ?></strong><br>
                ID Feeder: <strong><?= htmlspecialchars($summary['id_feeder']); ?></strong>
            </p>
        </div>
    <?php endif; ?>

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Profil Perguruan Tinggi Lokal</h3>
                    <p class="text-sm text-slate-500 mt-1">
                        Data ini akan digunakan untuk identitas kampus, laporan, kop surat, dan pengaturan SIAKAD.
                    </p>
                </div>

                <form method="POST">
                    <button type="submit"
                            name="sync_profil_pt"
                            value="1"
                            onclick="return confirm('Sinkronisasi Profil PT dari Neo Feeder sekarang?')"
                            class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                        <i class="fa-solid fa-rotate mr-2"></i>
                        Sinkronkan Profil PT
                    </button>
                </form>
            </div>

            <?php if ($profil_pt): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 sm:col-span-2">
                        <p class="text-xs text-slate-500 mb-1">Nama Perguruan Tinggi</p>
                        <p class="font-semibold text-slate-800">
                            <?= htmlspecialchars($profil_pt['nama_perguruan_tinggi']); ?>
                        </p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs text-slate-500 mb-1">ID Feeder</p>
                        <p class="font-semibold text-slate-800 break-all">
                            <?= htmlspecialchars($profil_pt['id_feeder'] ?? '-'); ?>
                        </p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs text-slate-500 mb-1">Kode PT</p>
                        <p class="font-semibold text-slate-800">
                            <?= htmlspecialchars($profil_pt['kode_perguruan_tinggi'] ?? '-'); ?>
                        </p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs text-slate-500 mb-1">Telepon</p>
                        <p class="font-semibold text-slate-800">
                            <?= htmlspecialchars($profil_pt['telepon'] ?? '-'); ?>
                        </p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs text-slate-500 mb-1">Faximile</p>
                        <p class="font-semibold text-slate-800">
                            <?= htmlspecialchars($profil_pt['faximile'] ?? '-'); ?>
                        </p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs text-slate-500 mb-1">Email</p>
                        <p class="font-semibold text-slate-800 break-all">
                            <?= htmlspecialchars($profil_pt['email'] ?? '-'); ?>
                        </p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs text-slate-500 mb-1">Website</p>
                        <p class="font-semibold text-slate-800 break-all">
                            <?= htmlspecialchars($profil_pt['website'] ?? '-'); ?>
                        </p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 sm:col-span-2">
                        <p class="text-xs text-slate-500 mb-1">Alamat</p>
                        <p class="font-semibold text-slate-800">
                            <?= htmlspecialchars($profil_pt['jalan'] ?? '-'); ?>,
                            <?= htmlspecialchars($profil_pt['kelurahan'] ?? '-'); ?>,
                            <?= htmlspecialchars($profil_pt['nama_wilayah'] ?? '-'); ?>,
                            Kode Pos <?= htmlspecialchars($profil_pt['kode_pos'] ?? '-'); ?>
                        </p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs text-slate-500 mb-1">SK Pendirian</p>
                        <p class="font-semibold text-slate-800">
                            <?= htmlspecialchars($profil_pt['sk_pendirian'] ?? '-'); ?>
                        </p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs text-slate-500 mb-1">Tanggal SK Pendirian</p>
                        <p class="font-semibold text-slate-800">
                            <?= !empty($profil_pt['tanggal_sk_pendirian']) ? tanggal_indonesia($profil_pt['tanggal_sk_pendirian']) : '-'; ?>
                        </p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs text-slate-500 mb-1">Status Milik</p>
                        <p class="font-semibold text-slate-800">
                            <?= htmlspecialchars($profil_pt['nama_status_milik'] ?? '-'); ?>
                        </p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs text-slate-500 mb-1">Status PT</p>
                        <p class="font-semibold text-slate-800">
                            <?= htmlspecialchars($profil_pt['status_perguruan_tinggi'] ?? '-'); ?>
                        </p>
                    </div>

                </div>
            <?php else: ?>
                <div class="p-6 rounded-2xl bg-yellow-50 border border-yellow-200 text-yellow-700">
                    Data Profil PT belum tersedia. Silakan klik tombol <strong>Sinkronkan Profil PT</strong>.
                </div>
            <?php endif; ?>

        </div>

        <aside class="space-y-6">

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Alur Sinkronisasi</h3>

                <ol class="list-decimal pl-5 text-sm text-slate-600 space-y-2">
                    <li>Sistem mengambil token Neo Feeder.</li>
                    <li>Sistem memanggil fungsi <strong>GetProfilPT</strong>.</li>
                    <li>Data disimpan ke tabel <strong>profil_pt</strong>.</li>
                    <li>Jika data sudah ada, sistem melakukan update.</li>
                    <li>Nama PT dan kode PT ikut diperbarui pada pengaturan Neo Feeder.</li>
                </ol>
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

        </aside>

    </section>

</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
