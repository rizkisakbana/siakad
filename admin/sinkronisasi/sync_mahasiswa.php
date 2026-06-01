<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";
require_once "../../includes/log_aktivitas.php";
require_once "../../includes/neofeeder_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Sinkronisasi Mahasiswa";
$page_subtitle = "Sinkronisasi data mahasiswa SIAKAD ke NeoFeeder PDDikti";

$id_mahasiswa_filter = intval($_GET['id'] ?? 0);

function kolom_ada($conn, $table, $column)
{
    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);

    $q = mysqli_query($conn, "SHOW COLUMNS FROM $table LIKE '$column'");
    return $q && mysqli_num_rows($q) > 0;
}

function nilai($value)
{
    return trim((string)($value ?? ''));
}

function validasi_mahasiswa_feeder($m)
{
    $error = [];

    if (empty($m['nim'])) $error[] = "NIM kosong.";
    if (empty($m['nama_mahasiswa'])) $error[] = "Nama mahasiswa kosong.";
    if (empty($m['jenis_kelamin'])) $error[] = "Jenis kelamin kosong.";
    if (empty($m['tempat_lahir'])) $error[] = "Tempat lahir kosong.";
    if (empty($m['tanggal_lahir'])) $error[] = "Tanggal lahir kosong.";
    if (empty($m['nik'])) $error[] = "NIK kosong.";
    if (empty($m['id_agama_feeder'])) $error[] = "Agama PDDikti kosong.";
    if (empty($m['id_prodi_feeder'])) $error[] = "ID Prodi Feeder kosong.";
    if (empty($m['id_jalur_masuk_feeder'])) $error[] = "Jalur masuk PDDikti kosong.";
    if (empty($m['id_jenis_pendaftaran_feeder'])) $error[] = "Jenis pendaftaran PDDikti kosong.";
    if (empty($m['id_periode_masuk'])) $error[] = "Periode masuk kosong.";

    return $error;
}

function ambil_id_response($response, $keys)
{
    if (!isset($response['data'])) return '';

    $data = $response['data'];

    if (isset($data[0]) && is_array($data[0])) {
        $data = $data[0];
    }

    foreach ($keys as $key) {
        if (!empty($data[$key])) {
            return $data[$key];
        }
    }

    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_mahasiswa'])) {

    $where = "WHERE 1=1";

    if (!empty($_POST['mode']) && $_POST['mode'] == 'belum') {
        $where .= " AND (m.status_sync_feeder IS NULL OR m.status_sync_feeder IN ('belum','gagal'))";
    }

    if (!empty($_POST['id_mahasiswa'])) {
        $id_post = intval($_POST['id_mahasiswa']);
        $where .= " AND m.id_mahasiswa = '$id_post'";
    }

    $query = mysqli_query($conn, "
        SELECT 
            m.*,
            COALESCE(NULLIF(p.id_prodi_feeder, ''), p.id_feeder) AS id_prodi_feeder,
            p.kode_prodi,
            p.nama_prodi,
            COALESCE(NULLIF(ta.id_semester_feeder, ''), ta.id_feeder) AS id_periode_masuk
        FROM mahasiswa m
        LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
        LEFT JOIN tahun_akademik ta 
            ON ta.tahun LIKE CONCAT(m.angkatan, '/%')
            AND ta.semester = 'Ganjil'
        $where
        ORDER BY m.id_mahasiswa ASC
    ");

    $total = 0;
    $berhasil = 0;
    $gagal = 0;
    $pesan_gagal = [];

    if ($query && mysqli_num_rows($query) > 0) {

        while ($m = mysqli_fetch_assoc($query)) {

            $total++;

            $validasi = validasi_mahasiswa_feeder($m);

            if (!empty($validasi)) {
                $gagal++;
                $pesan_gagal[] = $m['nim'] . " - " . $m['nama_mahasiswa'] . ": " . implode(" ", $validasi);

                mysqli_query($conn, "
                    UPDATE mahasiswa SET
                        status_sync_feeder = 'gagal',
                        last_sync_feeder = NOW()
                    WHERE id_mahasiswa = '{$m['id_mahasiswa']}'
                ");

                continue;
            }

            $nama_ibu = !empty($m['nama_ibu']) ? $m['nama_ibu'] : '-';

            $record_biodata = [
                "nama_mahasiswa" => nilai($m['nama_mahasiswa']),
                "jenis_kelamin" => nilai($m['jenis_kelamin']),
                "tempat_lahir" => nilai($m['tempat_lahir']),
                "tanggal_lahir" => nilai($m['tanggal_lahir']),
                "id_agama" => nilai($m['id_agama_feeder']),
                "nik" => nilai($m['nik']),
                "nisn" => nilai($m['nisn']),
                "npwp" => nilai($m['npwp']),
                "id_negara" => !empty($m['id_negara_feeder']) ? nilai($m['id_negara_feeder']) : "ID",
                "jalan" => nilai($m['alamat']),
                "id_wilayah" => nilai($m['id_wilayah_feeder']),
                "kode_pos" => nilai($m['kode_pos']),
                "email" => nilai($m['email']),
                "handphone" => nilai($m['no_hp']),
                "nama_ibu_kandung" => nilai($nama_ibu)
            ];

            if (!empty($m['id_alat_transportasi_feeder'])) {
                $record_biodata["id_alat_transportasi"] = nilai($m['id_alat_transportasi_feeder']);
            }

            if (!empty($m['id_pekerjaan_ayah_feeder'])) {
                $record_biodata["id_pekerjaan_ayah"] = nilai($m['id_pekerjaan_ayah_feeder']);
            }

            if (!empty($m['id_pekerjaan_ibu_feeder'])) {
                $record_biodata["id_pekerjaan_ibu"] = nilai($m['id_pekerjaan_ibu_feeder']);
            }

            if (!empty($m['id_penghasilan_ayah_feeder'])) {
                $record_biodata["id_penghasilan_ayah"] = nilai($m['id_penghasilan_ayah_feeder']);
            }

            if (!empty($m['id_penghasilan_ibu_feeder'])) {
                $record_biodata["id_penghasilan_ibu"] = nilai($m['id_penghasilan_ibu_feeder']);
            }

            $id_biodata_feeder = $m['id_biodata_feeder'] ?? '';

            if (empty($id_biodata_feeder) && !empty($m['id_feeder']) && empty($m['id_registrasi_feeder'])) {
                $id_biodata_feeder = $m['id_feeder'];
            }

            if (!empty($id_biodata_feeder)) {
                $record_biodata["id_mahasiswa"] = $id_biodata_feeder;

                $res_biodata = neofeeder_request(
                    $conn,
                    "UpdateBiodataMahasiswa",
                    "",
                    "",
                    "",
                    "",
                    $record_biodata,
                    "Mahasiswa"
                );
            } else {
                $res_biodata = neofeeder_request(
                    $conn,
                    "InsertBiodataMahasiswa",
                    "",
                    "",
                    "",
                    "",
                    $record_biodata,
                    "Mahasiswa"
                );

                $id_biodata_feeder = ambil_id_response($res_biodata, [
                    'id_mahasiswa',
                    'id_biodata_mahasiswa'
                ]);
            }

            if (!$res_biodata['success'] || empty($id_biodata_feeder)) {
                $gagal++;
                $pesan = $res_biodata['message'] ?? 'Gagal sinkron biodata mahasiswa.';
                $pesan_gagal[] = $m['nim'] . " - " . $m['nama_mahasiswa'] . ": " . $pesan;

                mysqli_query($conn, "
                    UPDATE mahasiswa SET
                        status_sync_feeder = 'gagal',
                        last_sync_feeder = NOW()
                    WHERE id_mahasiswa = '{$m['id_mahasiswa']}'
                ");

                continue;
            }

            $id_registrasi_feeder = $m['id_registrasi_feeder'] ?? '';

            $record_riwayat = [
                "id_mahasiswa" => $id_biodata_feeder,
                "nim" => nilai($m['nim']),
                "id_jenis_daftar" => nilai($m['id_jenis_pendaftaran_feeder']),
                "id_jalur_daftar" => nilai($m['id_jalur_masuk_feeder']),
                "id_periode_masuk" => nilai($m['id_periode_masuk']),
                "tanggal_daftar" => !empty($m['tanggal_masuk']) ? nilai($m['tanggal_masuk']) : date('Y-m-d'),
                "id_perguruan_tinggi" => "",
                "id_prodi" => nilai($m['id_prodi_feeder']),
                "sks_diakui" => "0"
            ];

            if (!empty($id_registrasi_feeder)) {
                $record_riwayat["id_registrasi_mahasiswa"] = $id_registrasi_feeder;

                $res_riwayat = neofeeder_request(
                    $conn,
                    "UpdateRiwayatPendidikanMahasiswa",
                    "",
                    "",
                    "",
                    "",
                    $record_riwayat,
                    "Mahasiswa"
                );
            } else {
                $res_riwayat = neofeeder_request(
                    $conn,
                    "InsertRiwayatPendidikanMahasiswa",
                    "",
                    "",
                    "",
                    "",
                    $record_riwayat,
                    "Mahasiswa"
                );

                $id_registrasi_feeder = ambil_id_response($res_riwayat, [
                    'id_registrasi_mahasiswa',
                    'id_reg_pd'
                ]);
            }

            if (!$res_riwayat['success'] || empty($id_registrasi_feeder)) {
                $gagal++;
                $pesan = $res_riwayat['message'] ?? 'Gagal sinkron riwayat pendidikan mahasiswa.';
                $pesan_gagal[] = $m['nim'] . " - " . $m['nama_mahasiswa'] . ": " . $pesan;

                mysqli_query($conn, "
                    UPDATE mahasiswa SET
                        id_biodata_feeder = '$id_biodata_feeder',
                        id_feeder = '$id_biodata_feeder',
                        id_prodi_feeder = '" . mysqli_real_escape_string($conn, $m['id_prodi_feeder']) . "',
                        id_periode_masuk_feeder = '" . mysqli_real_escape_string($conn, $m['id_periode_masuk']) . "',
                        status_sync_feeder = 'gagal',
                        last_sync_feeder = NOW()
                    WHERE id_mahasiswa = '{$m['id_mahasiswa']}'
                ");

                continue;
            }

            $id_biodata_db = mysqli_real_escape_string($conn, $id_biodata_feeder);
            $id_registrasi_db = mysqli_real_escape_string($conn, $id_registrasi_feeder);

            mysqli_query($conn, "
                UPDATE mahasiswa SET
                    id_feeder = '$id_biodata_db',
                    id_biodata_feeder = '$id_biodata_db',
                    id_registrasi_feeder = '$id_registrasi_db',
                    id_prodi_feeder = '" . mysqli_real_escape_string($conn, $m['id_prodi_feeder']) . "',
                    id_periode_masuk_feeder = '" . mysqli_real_escape_string($conn, $m['id_periode_masuk']) . "',
                    status_sync_feeder = 'sudah',
                    last_sync_feeder = NOW()
                WHERE id_mahasiswa = '{$m['id_mahasiswa']}'
            ");

            $berhasil++;
        }

    } else {
        set_alert("warning", "Tidak ada data mahasiswa yang perlu disinkronkan.");
    }

    simpan_log(
        $conn,
        $_SESSION['id_user'],
        "Sinkronisasi mahasiswa ke NeoFeeder. Total: $total, Berhasil: $berhasil, Gagal: $gagal",
        "Neo Feeder"
    );

    $_SESSION['sync_mahasiswa_summary'] = [
        'total' => $total,
        'berhasil' => $berhasil,
        'gagal' => $gagal,
        'pesan_gagal' => $pesan_gagal
    ];

    if ($gagal > 0) {
        set_alert("warning", "Sinkronisasi mahasiswa selesai dengan beberapa data gagal.");
    } else {
        set_alert("success", "Sinkronisasi mahasiswa berhasil.");
    }

    header("Location: sync_mahasiswa.php");
    exit;
}

$total_mahasiswa = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM mahasiswa
"))['total'] ?? 0;

$total_sudah = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM mahasiswa 
    WHERE status_sync_feeder = 'sudah'
"))['total'] ?? 0;

$total_belum = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM mahasiswa 
    WHERE status_sync_feeder IS NULL OR status_sync_feeder = 'belum'
"))['total'] ?? 0;

$total_gagal = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM mahasiswa 
    WHERE status_sync_feeder = 'gagal'
"))['total'] ?? 0;

$where_list = "";
if ($id_mahasiswa_filter > 0) {
    $where_list = "WHERE m.id_mahasiswa = '$id_mahasiswa_filter'";
}

$data_mahasiswa = mysqli_query($conn, "
    SELECT 
        m.*,
        p.nama_prodi,
        p.kode_prodi,
        COALESCE(NULLIF(p.id_prodi_feeder, ''), p.id_feeder) AS id_prodi_feeder
    FROM mahasiswa m
    LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
    $where_list
    ORDER BY m.id_mahasiswa DESC
    LIMIT 50
");

$summary = $_SESSION['sync_mahasiswa_summary'] ?? null;
unset($_SESSION['sync_mahasiswa_summary']);

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <?php show_alert(); ?>

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Sinkronisasi Mahasiswa</h2>
            <p class="text-sm text-slate-500">
                Mengirim data mahasiswa dari SIAKAD ke NeoFeeder/PDDikti.
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <a href="sinkronisasi.php"
               class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                Kembali
            </a>

            <a href="log_sinkronisasi.php"
               class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-purple-700 hover:bg-purple-800 text-white font-semibold">
                Log
            </a>
        </div>
    </div>

    <section class="grid grid-cols-1 sm:grid-cols-4 gap-5 mb-6">
        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Total Mahasiswa</p>
            <h2 class="text-3xl font-bold text-blue-700 mt-2"><?= number_format($total_mahasiswa); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Sudah Sinkron</p>
            <h2 class="text-3xl font-bold text-green-700 mt-2"><?= number_format($total_sudah); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Belum Sinkron</p>
            <h2 class="text-3xl font-bold text-orange-700 mt-2"><?= number_format($total_belum); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Gagal</p>
            <h2 class="text-3xl font-bold text-red-700 mt-2"><?= number_format($total_gagal); ?></h2>
        </div>
    </section>

    <?php if ($summary): ?>
        <section class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">
            <div class="bg-white rounded-2xl shadow border p-5">
                <p class="text-sm text-slate-500">Diproses</p>
                <h2 class="text-3xl font-bold text-blue-700 mt-2"><?= number_format($summary['total']); ?></h2>
            </div>

            <div class="bg-white rounded-2xl shadow border p-5">
                <p class="text-sm text-slate-500">Berhasil</p>
                <h2 class="text-3xl font-bold text-green-700 mt-2"><?= number_format($summary['berhasil']); ?></h2>
            </div>

            <div class="bg-white rounded-2xl shadow border p-5">
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

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6 mb-6">

        <h3 class="text-lg font-bold text-slate-800 mb-4">Proses Sinkronisasi</h3>

        <form method="POST" class="flex flex-col sm:flex-row gap-3">
            <?php if ($id_mahasiswa_filter > 0): ?>
                <input type="hidden" name="id_mahasiswa" value="<?= $id_mahasiswa_filter; ?>">
            <?php endif; ?>

            <button type="submit"
                    name="sync_mahasiswa"
                    value="1"
                    onclick="return confirm('Sinkronisasi mahasiswa ke NeoFeeder sekarang?')"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                Sinkronkan <?= $id_mahasiswa_filter > 0 ? 'Mahasiswa Ini' : 'Semua Data'; ?>
            </button>

            <?php if ($id_mahasiswa_filter <= 0): ?>
                <button type="submit"
                        name="sync_mahasiswa"
                        value="1"
                        onclick="document.getElementById('mode_sync').value='belum'; return confirm('Sinkronkan hanya data belum/gagal?')"
                        class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-green-700 hover:bg-green-800 text-white font-semibold">
                    Sinkron Belum/Gagal
                </button>

                <input type="hidden" name="mode" id="mode_sync" value="">
            <?php endif; ?>
        </form>

    </section>

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

        <h3 class="text-lg font-bold text-slate-800 mb-4">Data Mahasiswa</h3>

        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-100 text-slate-700">
                    <tr>
                        <th class="px-4 py-3 text-left">NIM</th>
                        <th class="px-4 py-3 text-left">Nama</th>
                        <th class="px-4 py-3 text-left">Prodi</th>
                        <th class="px-4 py-3 text-left">ID Biodata</th>
                        <th class="px-4 py-3 text-left">ID Registrasi</th>
                        <th class="px-4 py-3 text-left">Status</th>
                    </tr>
                </thead>

                <tbody class="divide-y">
                    <?php if ($data_mahasiswa && mysqli_num_rows($data_mahasiswa) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($data_mahasiswa)): ?>
                            <tr>
                                <td class="px-4 py-3"><?= htmlspecialchars($row['nim']); ?></td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold"><?= htmlspecialchars($row['nama_mahasiswa']); ?></div>
                                    <div class="text-xs text-slate-500"><?= htmlspecialchars($row['nik'] ?? '-'); ?></div>
                                </td>
                                <td class="px-4 py-3">
                                    <?= htmlspecialchars($row['nama_prodi'] ?? '-'); ?>
                                    <div class="text-xs text-slate-500">
                                        ID Prodi Feeder: <?= htmlspecialchars($row['id_prodi_feeder'] ?? '-'); ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3 break-all"><?= htmlspecialchars($row['id_biodata_feeder'] ?? '-'); ?></td>
                                <td class="px-4 py-3 break-all"><?= htmlspecialchars($row['id_registrasi_feeder'] ?? '-'); ?></td>
                                <td class="px-4 py-3">
                                    <?php if (($row['status_sync_feeder'] ?? 'belum') == 'sudah'): ?>
                                        <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-bold">Sudah</span>
                                    <?php elseif (($row['status_sync_feeder'] ?? 'belum') == 'gagal'): ?>
                                        <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-bold">Gagal</span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 rounded-full bg-orange-100 text-orange-700 text-xs font-bold">Belum</span>
                                    <?php endif; ?>

                                    <?php if (!empty($row['last_sync_feeder'])): ?>
                                        <div class="text-xs text-slate-500 mt-1">
                                            <?= tanggal_jam_indonesia($row['last_sync_feeder']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                Data mahasiswa tidak tersedia.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>

            </table>
        </div>

    </section>

</main>

<?php require_once "../../includes/footer.php"; ?>
