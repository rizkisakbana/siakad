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

$page_title = "Pull Dosen NeoFeeder";
$page_subtitle = "Menarik data dosen dari NeoFeeder/PDDikti ke master dosen SIAKAD";

function dosen_value($row, $keys, $default = '')
{
    foreach ($keys as $key) {
        if (isset($row[$key]) && trim((string) $row[$key]) !== '') {
            return trim((string) $row[$key]);
        }
    }

    return $default;
}

function dosen_sql($conn, $value)
{
    $value = trim((string) ($value ?? ''));

    if ($value === '') {
        return "NULL";
    }

    return "'" . mysqli_real_escape_string($conn, $value) . "'";
}

function dosen_date($value)
{
    $value = trim((string) ($value ?? ''));

    if ($value === '') {
        return '';
    }

    $from_feeder = feeder_date_to_mysql($value);
    if ($from_feeder !== '') {
        return $from_feeder;
    }

    if (strpos($value, 'T') !== false) {
        $value = substr($value, 0, 10);
    }

    $time = strtotime($value);
    return $time ? date('Y-m-d', $time) : '';
}

function dosen_jk($value)
{
    $value = strtolower(trim((string) ($value ?? '')));

    if ($value === '') {
        return null;
    }

    if ($value === 'l' || strpos($value, 'laki') !== false || $value === 'pria') {
        return 'L';
    }

    if ($value === 'p' || strpos($value, 'perempuan') !== false || $value === 'wanita') {
        return 'P';
    }

    return null;
}

function cari_prodi_lokal_dosen($conn, $id_prodi_feeder)
{
    $id_prodi_feeder = trim((string) $id_prodi_feeder);

    if ($id_prodi_feeder === '') {
        return [null, null];
    }

    $id = mysqli_real_escape_string($conn, $id_prodi_feeder);
    $row = nf_query_one($conn, "
        SELECT id_prodi, COALESCE(NULLIF(id_prodi_feeder, ''), NULLIF(id_feeder, '')) AS id_feeder
        FROM prodi
        WHERE id_prodi_feeder = '$id' OR id_feeder = '$id'
        LIMIT 1
    ");

    if ($row) {
        return [$row['id_prodi'], $row['id_feeder']];
    }

    return [null, $id_prodi_feeder];
}

function cari_dosen_lokal($conn, $data)
{
    $conditions = [];

    foreach (['id_dosen_feeder', 'id_feeder', 'nidn', 'nidk', 'email'] as $key) {
        if (!empty($data[$key])) {
            $value = mysqli_real_escape_string($conn, $data[$key]);
            $conditions[] = "$key = '$value'";
        }
    }

    if (empty($conditions)) {
        return null;
    }

    $row = nf_query_one($conn, "
        SELECT id_dosen
        FROM dosen
        WHERE " . implode(' OR ', $conditions) . "
        ORDER BY id_dosen ASC
        LIMIT 1
    ");

    if ($row) {
        return (int) $row['id_dosen'];
    }

    return null;
}

function cari_dosen_lokal_by_feeder($conn, $id_dosen_feeder, $nidn = '')
{
    $conditions = [];

    if (trim((string) $id_dosen_feeder) !== '') {
        $id = mysqli_real_escape_string($conn, trim((string) $id_dosen_feeder));
        $conditions[] = "id_dosen_feeder = '$id'";
        $conditions[] = "id_feeder = '$id'";
    }

    if (trim((string) $nidn) !== '') {
        $nidn = mysqli_real_escape_string($conn, trim((string) $nidn));
        $conditions[] = "nidn = '$nidn'";
    }

    if (empty($conditions)) {
        return null;
    }

    $row = nf_query_one($conn, "
        SELECT id_dosen
        FROM dosen
        WHERE " . implode(' OR ', $conditions) . "
        ORDER BY id_dosen ASC
        LIMIT 1
    ");

    if ($row) {
        return (int) $row['id_dosen'];
    }

    return null;
}

function normalisasi_dosen_feeder($conn, $row)
{
    $id_dosen_feeder = dosen_value($row, ['id_dosen', 'id_dosen_feeder', 'id_feeder']);
    $id_prodi_feeder = dosen_value($row, ['id_prodi', 'id_prodi_feeder', 'id_program_studi', 'id_sms']);
    [$id_prodi_lokal, $id_prodi_final] = cari_prodi_lokal_dosen($conn, $id_prodi_feeder);

    $nama_status = dosen_value($row, ['nama_status_aktif', 'status_aktif', 'status_dosen']);
    $status_lokal = stripos($nama_status, 'tidak') !== false || stripos($nama_status, 'keluar') !== false ? 'nonaktif' : 'aktif';

    return [
        'id_feeder' => $id_dosen_feeder,
        'id_dosen_feeder' => $id_dosen_feeder,
        'id_prodi' => $id_prodi_lokal,
        'id_prodi_feeder' => $id_prodi_final,
        'nidn' => dosen_value($row, ['nidn']),
        'nidk' => dosen_value($row, ['nidk']),
        'nuptk' => dosen_value($row, ['nuptk']),
        'nip' => dosen_value($row, ['nip']),
        'nama_dosen' => dosen_value($row, ['nama_dosen', 'nama']),
        'gelar_depan' => dosen_value($row, ['gelar_depan']),
        'gelar_belakang' => dosen_value($row, ['gelar_belakang']),
        'jenis_kelamin' => dosen_jk(dosen_value($row, ['jenis_kelamin'], null)),
        'tempat_lahir' => dosen_value($row, ['tempat_lahir']),
        'tanggal_lahir' => dosen_date(dosen_value($row, ['tanggal_lahir'])),
        'id_agama_feeder' => dosen_value($row, ['id_agama', 'id_agama_feeder']),
        'agama' => dosen_value($row, ['nama_agama', 'agama']),
        'email' => dosen_value($row, ['email']),
        'no_hp' => dosen_value($row, ['handphone', 'no_hp', 'nomor_hp', 'telepon']),
        'alamat' => dosen_value($row, ['jalan', 'alamat']),
        'id_status_aktif_feeder' => dosen_value($row, ['id_status_aktif', 'id_status_aktif_feeder']),
        'nama_status_aktif' => $nama_status,
        'id_ikatan_kerja_feeder' => dosen_value($row, ['id_ikatan_kerja', 'id_ikatan_kerja_feeder']),
        'nama_ikatan_kerja' => dosen_value($row, ['nama_ikatan_kerja', 'ikatan_kerja']),
        'status' => $status_lokal,
        'raw_feeder_data' => json_encode($row, JSON_UNESCAPED_UNICODE)
    ];
}

function simpan_dosen_feeder($conn, $data)
{
    if (empty($data['nama_dosen'])) {
        return ['status' => 'gagal', 'message' => 'Nama dosen kosong.'];
    }

    $id_dosen = cari_dosen_lokal($conn, $data);

    $fields = [
        'id_feeder', 'id_dosen_feeder', 'id_prodi', 'id_prodi_feeder', 'nidn', 'nidk', 'nuptk', 'nip',
        'nama_dosen', 'gelar_depan', 'gelar_belakang', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir',
        'id_agama_feeder', 'agama', 'email', 'no_hp', 'alamat', 'id_status_aktif_feeder',
        'nama_status_aktif', 'id_ikatan_kerja_feeder', 'nama_ikatan_kerja', 'raw_feeder_data', 'status'
    ];

    if ($id_dosen) {
        $sets = [];

        foreach ($fields as $field) {
            $sets[] = "$field = " . dosen_sql($conn, $data[$field] ?? null);
        }

        $sets[] = "status_sync_feeder = 'sudah'";
        $sets[] = "last_sync_feeder = NOW()";
        $sets[] = "last_error_feeder = NULL";

        $ok = mysqli_query($conn, "
            UPDATE dosen SET
                " . implode(",\n                ", $sets) . "
            WHERE id_dosen = '$id_dosen'
        ");

        return $ok
            ? ['status' => 'update', 'message' => null]
            : ['status' => 'gagal', 'message' => mysqli_error($conn)];
    }

    $columns = $fields;
    $columns[] = 'status_sync_feeder';
    $columns[] = 'last_sync_feeder';

    $values = [];
    foreach ($fields as $field) {
        $values[] = dosen_sql($conn, $data[$field] ?? null);
    }

    $values[] = "'sudah'";
    $values[] = "NOW()";

    $ok = mysqli_query($conn, "
        INSERT INTO dosen (" . implode(', ', $columns) . ")
        VALUES (" . implode(', ', $values) . ")
    ");

    return $ok
        ? ['status' => 'insert', 'message' => null]
        : ['status' => 'gagal', 'message' => mysqli_error($conn)];
}

function simpan_penugasan_dosen_feeder($conn, $row)
{
    $id_registrasi = dosen_value($row, ['id_registrasi_dosen']);
    $id_dosen_feeder = dosen_value($row, ['id_dosen']);

    if ($id_registrasi === '' || $id_dosen_feeder === '') {
        return ['status' => 'gagal', 'message' => 'ID registrasi dosen atau ID dosen kosong.'];
    }

    $id_prodi_feeder = dosen_value($row, ['id_prodi', 'id_prodi_feeder', 'id_sms']);
    [$id_prodi_lokal, $id_prodi_final] = cari_prodi_lokal_dosen($conn, $id_prodi_feeder);
    $id_dosen_lokal = cari_dosen_lokal_by_feeder($conn, $id_dosen_feeder, dosen_value($row, ['nidn']));

    $data = [
        'id_registrasi_dosen_feeder' => $id_registrasi,
        'id_dosen' => $id_dosen_lokal,
        'id_dosen_feeder' => $id_dosen_feeder,
        'nidn' => dosen_value($row, ['nidn']),
        'nama_dosen' => dosen_value($row, ['nama_dosen']),
        'jenis_kelamin' => dosen_jk(dosen_value($row, ['jk', 'jenis_kelamin'])),
        'nuptk' => dosen_value($row, ['nuptk']),
        'id_prodi' => $id_prodi_lokal,
        'id_prodi_feeder' => $id_prodi_final,
        'nama_program_studi' => dosen_value($row, ['nama_program_studi', 'nama_prodi']),
        'id_tahun_ajaran_feeder' => dosen_value($row, ['id_tahun_ajaran']),
        'nama_tahun_ajaran' => dosen_value($row, ['nama_tahun_ajaran']),
        'id_perguruan_tinggi_feeder' => dosen_value($row, ['id_perguruan_tinggi']),
        'nama_perguruan_tinggi' => dosen_value($row, ['nama_perguruan_tinggi']),
        'nomor_surat_tugas' => dosen_value($row, ['nomor_surat_tugas']),
        'tanggal_surat_tugas' => dosen_date(dosen_value($row, ['tanggal_surat_tugas'])),
        'mulai_surat_tugas' => dosen_date(dosen_value($row, ['mulai_surat_tugas'])),
        'tanggal_create_feeder' => dosen_date(dosen_value($row, ['tgl_create'])),
        'tanggal_ptk_keluar' => dosen_date(dosen_value($row, ['tgl_ptk_keluar'])),
        'id_status_pegawai_feeder' => dosen_value($row, ['id_stat_pegawai', 'id_status_pegawai']),
        'id_jenis_keluar_feeder' => dosen_value($row, ['id_jns_keluar', 'id_jenis_keluar']),
        'id_ikatan_kerja_feeder' => dosen_value($row, ['id_ikatan_kerja']),
        'is_homebase' => dosen_value($row, ['a_sp_homebase']) === '1' ? '1' : '0',
        'raw_feeder_data' => json_encode($row, JSON_UNESCAPED_UNICODE)
    ];

    $fields = array_keys($data);
    $escaped_id = mysqli_real_escape_string($conn, $id_registrasi);
    $existing = nf_query_one($conn, "
        SELECT id_penugasan
        FROM dosen_penugasan_feeder
        WHERE id_registrasi_dosen_feeder = '$escaped_id'
        LIMIT 1
    ");

    if ($existing) {
        $sets = [];

        foreach ($fields as $field) {
            $sets[] = "$field = " . dosen_sql($conn, $data[$field] ?? null);
        }

        $sets[] = "status_sync_feeder = 'sudah'";
        $sets[] = "last_sync_feeder = NOW()";
        $sets[] = "last_error_feeder = NULL";

        $ok = mysqli_query($conn, "
            UPDATE dosen_penugasan_feeder SET
                " . implode(",\n                ", $sets) . "
            WHERE id_penugasan = '{$existing['id_penugasan']}'
        ");

        return $ok ? ['status' => 'update', 'message' => null] : ['status' => 'gagal', 'message' => mysqli_error($conn)];
    }

    $columns = $fields;
    $columns[] = 'status_sync_feeder';
    $columns[] = 'last_sync_feeder';

    $values = [];
    foreach ($fields as $field) {
        $values[] = dosen_sql($conn, $data[$field] ?? null);
    }
    $values[] = "'sudah'";
    $values[] = "NOW()";

    $ok = mysqli_query($conn, "
        INSERT INTO dosen_penugasan_feeder (" . implode(', ', $columns) . ")
        VALUES (" . implode(', ', $values) . ")
    ");

    return $ok ? ['status' => 'insert', 'message' => null] : ['status' => 'gagal', 'message' => mysqli_error($conn)];
}

$data_prodi = nf_fetch_all($conn, "
    SELECT id_prodi, kode_prodi, nama_prodi, jenjang, COALESCE(NULLIF(id_prodi_feeder, ''), NULLIF(id_feeder, '')) AS id_feeder
    FROM prodi
    ORDER BY nama_prodi ASC
");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pull_dosen'])) {
    $id_prodi_lokal = (int) ($_POST['id_prodi'] ?? 0);
    $limit = max(1, min(500, (int) ($_POST['limit'] ?? 100)));
    $offset = max(0, (int) ($_POST['offset'] ?? 0));
    $ambil_detail = isset($_POST['ambil_detail']);
    $ambil_penugasan = isset($_POST['ambil_penugasan']);

    $prodi_label = "Semua prodi";
    $id_feeder_filter = "";

    if ($id_prodi_lokal > 0) {
        $prodi = nf_query_one($conn, "
            SELECT nama_prodi, COALESCE(NULLIF(id_prodi_feeder, ''), NULLIF(id_feeder, '')) AS id_feeder
            FROM prodi
            WHERE id_prodi = '$id_prodi_lokal'
            LIMIT 1
        ");

        if (!$prodi) {
            set_alert("error", "Program studi tidak ditemukan.");
            header("Location: pull_dosen.php");
            exit;
        }

        $prodi_label = $prodi['nama_prodi'];
        $id_feeder_filter = $prodi['id_feeder'] ?? '';
    }

    /*
     * GetListDosen pada REST NeoFeeder sensitif terhadap nama kolom filter/order.
     * Dokumentasi resmi Postman mencontohkan parameter umum act/token/filter/order/limit/offset,
     * tetapi field yang valid mengikuti view masing-masing endpoint. Untuk dosen,
     * request minimal lebih stabil; filter prodi dilakukan setelah data diterima.
     */
    $response = neofeeder_request($conn, "GetListDosen", "", "", $limit, $offset, null, "Dosen");

    if (!$response['success']) {
        set_alert("error", "Gagal menarik daftar dosen: " . $response['message']);
        header("Location: pull_dosen.php");
        exit;
    }

    $rows = $response['data'] ?? [];
    if (!is_array($rows)) {
        $rows = [];
    }

    $total = count($rows);
    $insert = 0;
    $update = 0;
    $gagal = 0;
    $detail_ok = 0;
    $detail_gagal = 0;
    $penugasan_insert = 0;
    $penugasan_update = 0;
    $penugasan_gagal = 0;
    $pesan_gagal = [];

    mysqli_begin_transaction($conn);

    try {
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $merged = $row;
            $id_dosen_feeder = dosen_value($row, ['id_dosen', 'id_dosen_feeder', 'id_feeder']);

            if ($ambil_detail && $id_dosen_feeder !== '') {
                $safe_id = str_replace("'", "\\'", $id_dosen_feeder);
                $detail = neofeeder_request($conn, "DetailBiodataDosen", "id_dosen = '$safe_id'", "", 1, 0, null, "Dosen");

                if ($detail['success'] && !empty($detail['data'])) {
                    $detail_row = isset($detail['data'][0]) ? $detail['data'][0] : $detail['data'];
                    if (is_array($detail_row)) {
                        $merged = array_merge($row, array_filter($detail_row, function ($value) {
                            return trim((string) $value) !== '';
                        }));
                        $detail_ok++;
                    }
                } else {
                    $detail_gagal++;
                }
            }

            $data = normalisasi_dosen_feeder($conn, $merged);

            if ($id_prodi_lokal > 0 && !empty($data['id_prodi']) && (int) $data['id_prodi'] !== $id_prodi_lokal) {
                continue;
            }

            if ($id_prodi_lokal > 0 && empty($data['id_prodi']) && !empty($id_feeder_filter)) {
                $data['id_prodi'] = $id_prodi_lokal;
                $data['id_prodi_feeder'] = $id_feeder_filter;
            }

            $result = simpan_dosen_feeder($conn, $data);

            if ($result['status'] === 'insert') {
                $insert++;
            } elseif ($result['status'] === 'update') {
                $update++;
            } else {
                $gagal++;
                $label = $data['nidn'] ?: ($data['nidk'] ?: ($data['nama_dosen'] ?: 'Dosen tanpa identitas'));
                $pesan_gagal[] = $label . ": " . $result['message'];
            }
        }

        if ($ambil_penugasan) {
            $penugasan_resp = neofeeder_request($conn, "GetListPenugasanDosen", "", "", $limit, $offset, null, "Penugasan Dosen");

            if ($penugasan_resp['success']) {
                $penugasan_rows = is_array($penugasan_resp['data'] ?? null) ? $penugasan_resp['data'] : [];

                foreach ($penugasan_rows as $row_penugasan) {
                    if (!is_array($row_penugasan)) {
                        continue;
                    }

                    $result_penugasan = simpan_penugasan_dosen_feeder($conn, $row_penugasan);

                    if ($result_penugasan['status'] === 'insert') {
                        $penugasan_insert++;
                    } elseif ($result_penugasan['status'] === 'update') {
                        $penugasan_update++;
                    } else {
                        $penugasan_gagal++;
                        $pesan_gagal[] = "Penugasan " . dosen_value($row_penugasan, ['nama_dosen', 'nidn', 'id_dosen'], 'tanpa identitas') . ": " . $result_penugasan['message'];
                    }
                }
            } else {
                $penugasan_gagal++;
                $pesan_gagal[] = "GetListPenugasanDosen: " . $penugasan_resp['message'];
            }
        }

        mysqli_commit($conn);

        simpan_log(
            $conn,
            $_SESSION['id_user'],
            "Pull dosen dari NeoFeeder. Prodi: $prodi_label, Insert: $insert, Update: $update, Gagal: $gagal, Penugasan Insert: $penugasan_insert, Penugasan Update: $penugasan_update",
            "Neo Feeder"
        );

        $_SESSION['pull_dosen_summary'] = [
            'total' => $total,
            'insert' => $insert,
            'update' => $update,
            'gagal' => $gagal,
            'detail_ok' => $detail_ok,
            'detail_gagal' => $detail_gagal,
            'penugasan_insert' => $penugasan_insert,
            'penugasan_update' => $penugasan_update,
            'penugasan_gagal' => $penugasan_gagal,
            'next_offset' => $offset + $limit,
            'pesan_gagal' => $pesan_gagal
        ];

        set_alert(($gagal > 0 || $penugasan_gagal > 0) ? "warning" : "success", "Pull dosen selesai diproses.");
        header("Location: pull_dosen.php");
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        set_alert("error", "Terjadi kesalahan transaksi: " . $e->getMessage());
        header("Location: pull_dosen.php");
        exit;
    }
}

$summary = $_SESSION['pull_dosen_summary'] ?? null;
unset($_SESSION['pull_dosen_summary']);

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
require_once __DIR__ . "/../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <?php show_alert(); ?>

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Pull Dosen NeoFeeder</h2>
            <p class="text-sm text-slate-500">
                Menarik data dosen dari GetListDosen dan DetailBiodataDosen ke master dosen lokal.
            </p>
        </div>

        <a href="data_pull.php"
            class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
            Kembali
        </a>
    </div>

    <?php if ($summary): ?>
        <section class="grid grid-cols-1 sm:grid-cols-4 xl:grid-cols-6 gap-5 mb-6">
            <div class="bg-white rounded-2xl shadow border p-5">
                <p class="text-sm text-slate-500">Data Feeder</p>
                <h2 class="text-3xl font-bold text-blue-700 mt-2"><?= number_format($summary['total']); ?></h2>
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
            <div class="bg-white rounded-2xl shadow border p-5">
                <p class="text-sm text-slate-500">Detail OK</p>
                <h2 class="text-3xl font-bold text-emerald-700 mt-2"><?= number_format($summary['detail_ok']); ?></h2>
            </div>
            <div class="bg-white rounded-2xl shadow border p-5">
                <p class="text-sm text-slate-500">Detail Gagal</p>
                <h2 class="text-3xl font-bold text-orange-700 mt-2"><?= number_format($summary['detail_gagal']); ?></h2>
            </div>
            <div class="bg-white rounded-2xl shadow border p-5">
                <p class="text-sm text-slate-500">Penugasan Insert</p>
                <h2 class="text-3xl font-bold text-blue-700 mt-2"><?= number_format($summary['penugasan_insert'] ?? 0); ?></h2>
            </div>
            <div class="bg-white rounded-2xl shadow border p-5">
                <p class="text-sm text-slate-500">Penugasan Update</p>
                <h2 class="text-3xl font-bold text-indigo-700 mt-2"><?= number_format($summary['penugasan_update'] ?? 0); ?></h2>
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

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
        <h3 class="text-lg font-bold text-slate-800 mb-4">Filter Pull Dosen</h3>

        <form method="POST" class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Program Studi</label>
                <select name="id_prodi" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                    <option value="">Semua prodi</option>
                    <?php if (!empty($data_prodi)): ?>
                        <?php foreach ($data_prodi as $prodi): ?>
                            <option value="<?= $prodi['id_prodi']; ?>">
                                <?= htmlspecialchars($prodi['nama_prodi']); ?> - <?= htmlspecialchars($prodi['jenjang']); ?>
                                <?= empty($prodi['id_feeder']) ? ' (Belum ada ID Feeder)' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Ambil Detail Biodata</label>
                <label class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-3 w-full text-sm text-slate-700">
                    <input type="checkbox" name="ambil_detail" value="1" checked>
                    Lengkapi data dari DetailBiodataDosen
                </label>
            </div>

            <div class="lg:col-span-2">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Penugasan Dosen</label>
                <label class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-3 w-full text-sm text-slate-700">
                    <input type="checkbox" name="ambil_penugasan" value="1" checked>
                    Tarik GetListPenugasanDosen ke tabel penugasan dosen
                </label>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Limit</label>
                <input type="number" name="limit" value="100" min="1" max="500"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Offset</label>
                <input type="number" name="offset" value="0" min="0"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3">
            </div>

            <div class="lg:col-span-2">
                <button type="submit" name="pull_dosen" value="1"
                    onclick="return confirm('Tarik data dosen dari NeoFeeder ke SIAKAD sekarang?')"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                    Pull Dosen
                </button>
            </div>
        </form>
    </section>

</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
