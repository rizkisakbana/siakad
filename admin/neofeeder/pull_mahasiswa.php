<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";
require_once "../../includes/log_aktivitas.php";
require_once "../../includes/neofeeder_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Pull Mahasiswa NeoFeeder";
$page_subtitle = "Menarik data mahasiswa aktif dari NeoFeeder ke SIAKAD";

function esc($conn, $value)
{
    return mysqli_real_escape_string($conn, trim((string) ($value ?? '')));
}

function sql_null($conn, $value)
{
    if ($value === null || trim((string) $value) === '') {
        return "NULL";
    }
    return "'" . mysqli_real_escape_string($conn, trim((string) $value)) . "'";
}

function format_tanggal_feeder($tanggal)
{
    $tanggal = trim((string) $tanggal);

    if ($tanggal == '' || $tanggal == '01-01-1970') {
        return '';
    }

    $d = DateTime::createFromFormat('d-m-Y', $tanggal);
    if ($d)
        return $d->format('Y-m-d');

    $d = DateTime::createFromFormat('Y-m-d', $tanggal);
    if ($d)
        return $d->format('Y-m-d');

    return '';
}

function ambil_data_pertama($response) {
    if (!($response['success'] ?? false)) {
        return [];
    }

    $data = $response['data'] ?? [];

    if (isset($data[0]) && is_array($data[0])) {
        return $data[0];
    }

    return is_array($data) ? $data : [];
}

function get_role_mahasiswa($conn)
{
    $q = mysqli_query($conn, "
        SELECT id_role 
        FROM roles
        WHERE nama_role = 'mahasiswa'
        LIMIT 1
    ");

    if ($q && mysqli_num_rows($q) > 0) {
        return mysqli_fetch_assoc($q)['id_role'];
    }

    return 0;
}

function user_email_sql_aman($conn, $email, $id_user = 0)
{
    $email = trim((string) ($email ?? ''));

    if ($email === '') {
        return "NULL";
    }

    $email_db = mysqli_real_escape_string($conn, $email);
    $id_user = (int) $id_user;
    $exclude = $id_user > 0 ? "AND id_user != '$id_user'" : "";

    $cek = mysqli_query($conn, "
        SELECT id_user
        FROM users
        WHERE email = '$email_db'
        $exclude
        LIMIT 1
    ");

    if ($cek && mysqli_num_rows($cek) > 0) {
        return "NULL";
    }

    return "'$email_db'";
}

$data_prodi = mysqli_query($conn, "
    SELECT id_prodi, kode_prodi, nama_prodi, jenjang, id_feeder, id_prodi_feeder
    FROM prodi
    WHERE status = 'aktif'
    ORDER BY nama_prodi ASC
");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pull_mahasiswa'])) {

    $id_prodi_lokal = intval($_POST['id_prodi'] ?? 0);
    $angkatan = esc($conn, $_POST['angkatan'] ?? '');
    $limit = intval($_POST['limit'] ?? 100);
    $offset = intval($_POST['offset'] ?? 0);
    $hanya_aktif = isset($_POST['hanya_aktif']) ? 1 : 0;

    if ($limit <= 0)
        $limit = 100;
    if ($limit > 500)
        $limit = 500;

    $q_prodi = mysqli_query($conn, "
        SELECT *
        FROM prodi
        WHERE id_prodi = '$id_prodi_lokal'
        LIMIT 1
    ");

    if (!$q_prodi || mysqli_num_rows($q_prodi) < 1) {
        set_alert("error", "Program studi tidak valid.");
    } else {

        $prodi = mysqli_fetch_assoc($q_prodi);

            $id_prodi_feeder_source = $prodi['id_prodi_feeder'] ?: $prodi['id_feeder'];

            if (empty($id_prodi_feeder_source)) {
                set_alert("error", "Program studi belum memiliki ID Feeder. Pull/sinkronkan prodi terlebih dahulu.");
            } else {

            $id_prodi_feeder = esc($conn, $id_prodi_feeder_source);

            $filter = "id_prodi = '$id_prodi_feeder'";

            // if ($hanya_aktif) {
            //     $filter .= " AND id_status_mahasiswa = '1'";
            // }

            if (!empty($angkatan)) {
                $filter .= " AND id_periode LIKE '$angkatan%'";
            }

            $response = neofeeder_request(
                $conn,
                "GetListMahasiswa",
                $filter,
                "",
                $limit,
                $offset,
                null,
                "Pull Mahasiswa"
            );

            if (!$response['success']) {
                set_alert("error", "Gagal menarik data mahasiswa dari NeoFeeder: " . $response['message']);
            } else {

                $data_feeder = $response['data'] ?? [];
                $id_role_mahasiswa = get_role_mahasiswa($conn);

                if (empty($data_feeder)) {
                    $_SESSION['pull_mahasiswa_summary'] = [
                        'total' => 0,
                        'insert' => 0,
                        'update' => 0,
                        'skip' => 0,
                        'gagal' => 0,
                        'pesan_gagal' => [
                            'Data dari GetListMahasiswa kosong. Periksa filter prodi, angkatan, status aktif, limit, dan offset.'
                        ]
                    ];

                    set_alert("warning", "Data GetListMahasiswa kosong.");
                    header("Location: pull_mahasiswa.php");
                    exit;
                }

                if ($id_role_mahasiswa <= 0) {
                    set_alert("error", "Role mahasiswa belum tersedia pada tabel roles.");
                } else {

                    $total = count($data_feeder);
                    $insert = 0;
                    $update = 0;
                    $skip = 0;
                    $gagal = 0;
                    $pesan_gagal = [];

                    mysqli_begin_transaction($conn);

                    try {

                        foreach ($data_feeder as $m) {

                            // if ($hanya_aktif && (($m['id_status_mahasiswa'] ?? '') != '1')) {
                            //     $skip++;
                            //     continue;
                            // }

                            $id_biodata_feeder = esc($conn, $m['id_mahasiswa'] ?? '');
                            $id_registrasi_feeder = esc($conn, $m['id_registrasi_mahasiswa'] ?? '');
                            $nim = esc($conn, $m['nim'] ?? ($m['nipd'] ?? ''));
                            $nama_mahasiswa = esc($conn, $m['nama_mahasiswa'] ?? '');
                            $jenis_kelamin = esc($conn, $m['jenis_kelamin'] ?? '');

                            if (empty($id_biodata_feeder) || empty($nim) || empty($nama_mahasiswa)) {
                                $gagal++;
                                $pesan_gagal[] = "Data Feeder tidak lengkap: id_mahasiswa/NIM/nama kosong.";
                                continue;
                            }

                            $res_biodata = neofeeder_request(
                                $conn,
                                "GetBiodataMahasiswa",
                                "id_mahasiswa = '$id_biodata_feeder'",
                                "",
                                1,
                                0,
                                null,
                                "Pull Biodata Mahasiswa"
                            );

                            $biodata = ambil_data_pertama($res_biodata);

                            $res_riwayat = neofeeder_request(
                                $conn,
                                "GetListRiwayatPendidikanMahasiswa",
                                "id_registrasi_mahasiswa = '$id_registrasi_feeder'",
                                "",
                                1,
                                0,
                                null,
                                "Pull Riwayat Pendidikan Mahasiswa"
                            );

                            $riwayat = ambil_data_pertama($res_riwayat);

                            $tempat_lahir = esc($conn, $biodata['tempat_lahir'] ?? '');
                            $tanggal_lahir = format_tanggal_feeder($m['tanggal_lahir'] ?? ($biodata['tanggal_lahir'] ?? ''));

                            $nik = esc($conn, $biodata['nik'] ?? '');
                            $nisn = esc($conn, $biodata['nisn'] ?? '');
                            $npwp = esc($conn, $biodata['npwp'] ?? '');

                            $id_agama_feeder = esc($conn, $biodata['id_agama'] ?? ($m['id_agama'] ?? ''));
                            $agama = esc($conn, $biodata['nama_agama'] ?? ($m['nama_agama'] ?? ''));

                            $id_negara_feeder = esc($conn, $biodata['id_negara'] ?? 'ID');
                            $kewarganegaraan = esc($conn, $biodata['nama_negara'] ?? 'Indonesia');
                            if (empty($kewarganegaraan))
                                $kewarganegaraan = 'Indonesia';

                            $alamat = esc($conn, $biodata['jalan'] ?? '');
                            $id_wilayah_feeder = esc($conn, $biodata['id_wilayah'] ?? '');
                            $kode_pos = esc($conn, $biodata['kode_pos'] ?? '');

                            $email = esc($conn, $biodata['email'] ?? '');
                            $no_hp = esc($conn, $biodata['handphone'] ?? '');

                            $nama_ibu = esc($conn, $biodata['nama_ibu_kandung'] ?? '');

                            $id_status_mahasiswa_feeder = esc($conn, $m['id_status_mahasiswa'] ?? '');
                            $status_mahasiswa = esc($conn, $m['nama_status_mahasiswa'] ?? '');

                            $id_periode_masuk = esc($conn, $m['id_periode'] ?? ($riwayat['id_periode_masuk'] ?? ''));
                            $tahun_angkatan = !empty($id_periode_masuk) ? substr($id_periode_masuk, 0, 4) : $angkatan;

                            $id_jalur_masuk_feeder = esc($conn, $riwayat['id_jalur_daftar'] ?? '');
                            $jalur_masuk = esc($conn, $riwayat['nama_jalur_masuk'] ?? '');

                            $id_jenis_pendaftaran_feeder = esc($conn, $riwayat['id_jenis_daftar'] ?? '');
                            $jenis_pendaftaran = esc($conn, $riwayat['nama_jenis_daftar'] ?? '');

                            $tanggal_masuk = format_tanggal_feeder($riwayat['tanggal_daftar'] ?? '');

                            if (empty($tanggal_masuk)) {
                                $tanggal_masuk = !empty($tahun_angkatan) ? $tahun_angkatan . "-09-01" : '';
                            }

                            $username = $nim;
                            $password_default = password_hash("123456", PASSWORD_DEFAULT);

                            $cek_mhs = mysqli_query($conn, "
                                SELECT id_mahasiswa, id_user
                                FROM mahasiswa
                                WHERE nim = '$nim'
                                OR id_biodata_feeder = '$id_biodata_feeder'
                                OR id_registrasi_feeder = '$id_registrasi_feeder'
                                LIMIT 1
                            ");

                            if ($cek_mhs && mysqli_num_rows($cek_mhs) > 0) {

                                $lokal = mysqli_fetch_assoc($cek_mhs);
                                $id_mahasiswa_lokal = intval($lokal['id_mahasiswa']);
                                $id_user_lokal = intval($lokal['id_user']);

                                mysqli_query($conn, "
                                    UPDATE users SET
                                        nama_lengkap = '$nama_mahasiswa',
                                        email = " . user_email_sql_aman($conn, $email, $id_user_lokal) . ",
                                        no_hp = " . sql_null($conn, $no_hp) . ",
                                        status = 'aktif'
                                    WHERE id_user = '$id_user_lokal'
                                ");

                                $q_update = mysqli_query($conn, "
                                    UPDATE mahasiswa SET
                                        id_prodi = '$id_prodi_lokal',
                                        id_prodi_feeder = '$id_prodi_feeder',
                                        id_feeder = '$id_biodata_feeder',
                                        id_biodata_feeder = '$id_biodata_feeder',
                                        id_registrasi_feeder = '$id_registrasi_feeder',

                                        nim = '$nim',
                                        nama_mahasiswa = '$nama_mahasiswa',
                                        jenis_kelamin = '$jenis_kelamin',
                                        tempat_lahir = '$tempat_lahir',
                                        tanggal_lahir = " . sql_null($conn, $tanggal_lahir) . ",

                                        agama = '$agama',
                                        id_agama_feeder = '$id_agama_feeder',

                                        nik = " . sql_null($conn, $nik) . ",
                                        nisn = " . sql_null($conn, $nisn) . ",
                                        npwp = " . sql_null($conn, $npwp) . ",

                                        kewarganegaraan = '$kewarganegaraan',
                                        id_negara_feeder = '$id_negara_feeder',

                                        alamat = '$alamat',
                                        id_wilayah_feeder = '$id_wilayah_feeder',
                                        kode_pos = '$kode_pos',

                                        email = " . sql_null($conn, $email) . ",
                                        no_hp = " . sql_null($conn, $no_hp) . ",

                                        nama_ibu = '$nama_ibu',

                                        angkatan = '$tahun_angkatan',
                                        id_periode_masuk_feeder = '$id_periode_masuk',
                                        semester = '1',
                                        tanggal_masuk = " . sql_null($conn, $tanggal_masuk) . ",

                                        status_mahasiswa = '$status_mahasiswa',
                                        id_status_mahasiswa_feeder = '$id_status_mahasiswa_feeder',

                                        jalur_masuk = '$jalur_masuk',
                                        id_jalur_masuk_feeder = '$id_jalur_masuk_feeder',

                                        jenis_pendaftaran = '$jenis_pendaftaran',
                                        id_jenis_pendaftaran_feeder = '$id_jenis_pendaftaran_feeder',

                                        status = 'aktif',
                                        status_sync_feeder = 'sudah',
                                        last_sync_feeder = NOW(),
                                        raw_feeder_data = " . sql_null($conn, json_encode([
                                            'list' => $m,
                                            'biodata' => $biodata,
                                            'riwayat' => $riwayat
                                        ], JSON_UNESCAPED_UNICODE)) . "
                                    WHERE id_mahasiswa = '$id_mahasiswa_lokal'
                                ");

                                if ($q_update) {
                                    $update++;
                                } else {
                                    $gagal++;
                                    $pesan_gagal[] = "$nim - $nama_mahasiswa gagal update: " . mysqli_error($conn);
                                }

                            } else {

                                $cek_user = mysqli_query($conn, "
                                    SELECT id_user
                                    FROM users
                                    WHERE username = '$username'
                                    LIMIT 1
                                ");

                                if ($cek_user && mysqli_num_rows($cek_user) > 0) {
                                    $u = mysqli_fetch_assoc($cek_user);
                                    $id_user = intval($u['id_user']);
                                } else {
                                    $q_user = mysqli_query($conn, "
                                        INSERT INTO users
                                        (
                                            id_role,
                                            username,
                                            password,
                                            nama_lengkap,
                                            email,
                                            no_hp,
                                            status
                                        )
                                        VALUES
                                        (
                                            '$id_role_mahasiswa',
                                            '$username',
                                            '$password_default',
                                            '$nama_mahasiswa',
                                            " . user_email_sql_aman($conn, $email) . ",
                                            " . sql_null($conn, $no_hp) . ",
                                            'aktif'
                                        )
                                    ");

                                    if (!$q_user) {
                                        $gagal++;
                                        $pesan_gagal[] = "$nim - $nama_mahasiswa gagal membuat user: " . mysqli_error($conn);
                                        continue;
                                    }

                                    $id_user = mysqli_insert_id($conn);
                                }

                                $q_insert = mysqli_query($conn, "
                                    INSERT INTO mahasiswa
                                    (
                                        id_user,
                                        id_prodi,
                                        id_prodi_feeder,
                                        id_feeder,
                                        id_biodata_feeder,
                                        id_registrasi_feeder,

                                        nim,
                                        nama_mahasiswa,
                                        jenis_kelamin,
                                        tempat_lahir,
                                        tanggal_lahir,

                                        agama,
                                        id_agama_feeder,

                                        nik,
                                        nisn,
                                        npwp,

                                        kewarganegaraan,
                                        id_negara_feeder,

                                        alamat,
                                        id_wilayah_feeder,
                                        kode_pos,

                                        email,
                                        no_hp,

                                        nama_ibu,

                                        angkatan,
                                        id_periode_masuk_feeder,
                                        semester,
                                        tanggal_masuk,

                                        status_mahasiswa,
                                        id_status_mahasiswa_feeder,

                                        jalur_masuk,
                                        id_jalur_masuk_feeder,

                                        jenis_pendaftaran,
                                        id_jenis_pendaftaran_feeder,

                                        status,
                                        status_sync_feeder,
                                        last_sync_feeder,
                                        raw_feeder_data
                                    )
                                    VALUES
                                    (
                                        '$id_user',
                                        '$id_prodi_lokal',
                                        '$id_prodi_feeder',
                                        '$id_biodata_feeder',
                                        '$id_biodata_feeder',
                                        '$id_registrasi_feeder',

                                        '$nim',
                                        '$nama_mahasiswa',
                                        '$jenis_kelamin',
                                        '$tempat_lahir',
                                        " . sql_null($conn, $tanggal_lahir) . ",

                                        '$agama',
                                        '$id_agama_feeder',

                                        " . sql_null($conn, $nik) . ",
                                        " . sql_null($conn, $nisn) . ",
                                        " . sql_null($conn, $npwp) . ",

                                        '$kewarganegaraan',
                                        '$id_negara_feeder',

                                        '$alamat',
                                        '$id_wilayah_feeder',
                                        '$kode_pos',

                                        " . sql_null($conn, $email) . ",
                                        " . sql_null($conn, $no_hp) . ",

                                        '$nama_ibu',

                                        '$tahun_angkatan',
                                        '$id_periode_masuk',
                                        '1',
                                        " . sql_null($conn, $tanggal_masuk) . ",

                                        '$status_mahasiswa',
                                        '$id_status_mahasiswa_feeder',

                                        '$jalur_masuk',
                                        '$id_jalur_masuk_feeder',

                                        '$jenis_pendaftaran',
                                        '$id_jenis_pendaftaran_feeder',

                                        'aktif',
                                        'sudah',
                                        NOW(),
                                        " . sql_null($conn, json_encode([
                                            'list' => $m,
                                            'biodata' => $biodata,
                                            'riwayat' => $riwayat
                                        ], JSON_UNESCAPED_UNICODE)) . "
                                    )
                                ");

                                if ($q_insert) {
                                    $insert++;
                                } else {
                                    $gagal++;
                                    $pesan_gagal[] = "$nim - $nama_mahasiswa gagal insert: " . mysqli_error($conn);
                                }
                            }
                        }

                        mysqli_commit($conn);

                        simpan_log(
                            $conn,
                            $_SESSION['id_user'],
                            "Pull mahasiswa lengkap dari NeoFeeder. Prodi: {$prodi['nama_prodi']}, Angkatan: $angkatan, Insert: $insert, Update: $update, Skip: $skip, Gagal: $gagal",
                            "Neo Feeder"
                        );

                        $_SESSION['pull_mahasiswa_summary'] = [
                            'total' => $total,
                            'insert' => $insert,
                            'update' => $update,
                            'skip' => $skip,
                            'gagal' => $gagal,
                            'pesan_gagal' => $pesan_gagal,
                            'next_offset' => $offset + $limit,
                            'id_prodi' => $id_prodi_lokal,
                            'angkatan' => $angkatan
                        ];

                        set_alert($gagal > 0 ? "warning" : "success", "Pull mahasiswa selesai diproses.");
                        header("Location: pull_mahasiswa.php");
                        exit;

                    } catch (Exception $e) {
                        mysqli_rollback($conn);
                        set_alert("error", "Terjadi kesalahan transaksi: " . $e->getMessage());
                    }
                }
            }
        }
    }
}

$summary = $_SESSION['pull_mahasiswa_summary'] ?? null;
unset($_SESSION['pull_mahasiswa_summary']);

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <?php show_alert(); ?>

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Pull Mahasiswa Lengkap NeoFeeder</h2>
            <p class="text-sm text-slate-500">
                Menarik data mahasiswa dari GetListMahasiswa, GetBiodataMahasiswa, dan
                GetListRiwayatPendidikanMahasiswa.
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <a href="data_pull.php"
                class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                Kembali
            </a>
        </div>
    </div>

    <?php if ($summary): ?>
        <section class="grid grid-cols-1 sm:grid-cols-5 gap-5 mb-6">
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
                <p class="text-sm text-slate-500">Skip</p>
                <h2 class="text-3xl font-bold text-orange-700 mt-2"><?= number_format($summary['skip']); ?></h2>
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

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

        <h3 class="text-lg font-bold text-slate-800 mb-4">
            Filter Pull Mahasiswa
        </h3>

        <form method="POST" class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Program Studi</label>
                <select name="id_prodi" required class="w-full rounded-xl border border-slate-300 px-4 py-3">
                    <option value="">-- Pilih Prodi --</option>
                    <?php while ($prodi = mysqli_fetch_assoc($data_prodi)): ?>
                        <option value="<?= $prodi['id_prodi']; ?>">
                            <?= htmlspecialchars($prodi['nama_prodi']); ?> - <?= htmlspecialchars($prodi['jenjang']); ?>
                            <?= empty($prodi['id_prodi_feeder'] ?: $prodi['id_feeder']) ? ' (Belum ada ID Feeder)' : ''; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Tahun Angkatan</label>
                <input type="number" name="angkatan" placeholder="Contoh: 2025" min="2000" max="2100"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3">
                <p class="text-xs text-slate-500 mt-1">Kosongkan jika ingin menarik semua angkatan pada prodi tersebut.
                </p>
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
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="hanya_aktif" value="1" checked>
                    Hanya tarik mahasiswa aktif
                </label>
            </div>

            <div class="lg:col-span-2">
                <button type="submit" name="pull_mahasiswa" value="1"
                    onclick="return confirm('Tarik data mahasiswa lengkap dari NeoFeeder ke SIAKAD sekarang?')"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                    Pull Mahasiswa Lengkap
                </button>
            </div>

        </form>

    </section>

</main>

<?php require_once "../../includes/footer.php"; ?>
