<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/helper.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/mahasiswa_helper.php";

/** @var mysqli $conn */

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$id_mahasiswa = intval($_GET['id'] ?? 0);

if ($id_mahasiswa <= 0) {
    set_alert("error", "ID mahasiswa tidak valid.");
    header("Location: data_mahasiswa.php");
    exit;
}

$data = mahasiswa_query_one($conn, "
    SELECT 
        mahasiswa.*,
        users.username,
        users.email AS email_user,
        users.no_hp AS no_hp_user,
        users.status AS status_user,
        prodi.kode_prodi,
        prodi.nama_prodi,
        prodi.jenjang,
        kelas.nama_kelas
    FROM mahasiswa
    LEFT JOIN users ON mahasiswa.id_user = users.id_user
    LEFT JOIN prodi ON mahasiswa.id_prodi = prodi.id_prodi
    LEFT JOIN kelas ON mahasiswa.id_kelas = kelas.id_kelas
    WHERE mahasiswa.id_mahasiswa = '$id_mahasiswa'
    LIMIT 1
");

if (!$data) {
    set_alert("error", "Data mahasiswa tidak ditemukan.");
    header("Location: data_mahasiswa.php");
    exit;
}

$page_title = "Detail Mahasiswa";
$page_subtitle = $data['nama_mahasiswa'] ?? "Detail data mahasiswa";

function tampil($value)
{
    return !empty($value) ? htmlspecialchars($value) : '-';
}

function ref_nama($conn, $jenis_ref, $id_feeder)
{
    return mahasiswa_ref_name($conn, trim((string) $jenis_ref), trim((string) $id_feeder));
}

function nama_wilayah_berantai($conn, $id_wilayah)
{
    $id_wilayah = trim((string) $id_wilayah);

    if ($id_wilayah === '') {
        return '';
    }

    $items = [];
    $current = $id_wilayah;
    $guard = 0;

    while ($current !== '' && $guard < 5) {
        $safe_id = mysqli_real_escape_string($conn, trim($current));
        $row = mahasiswa_query_one($conn, "
            SELECT id_induk_feeder, nama_ref
            FROM ref_pddikti
            WHERE jenis_ref = 'wilayah'
            AND TRIM(id_feeder) = '$safe_id'
            LIMIT 1
        ");

        if (!$row) {
            break;
        }

        $nama = trim((string) ($row['nama_ref'] ?? ''));

        if ($nama !== '' && strtolower($nama) !== 'indonesia') {
            $items[] = $nama;
        }

        $next = trim((string) ($row['id_induk_feeder'] ?? ''));
        if ($next === '' || $next === $current || $next === '000000') {
            break;
        }

        $current = $next;
        $guard++;
    }

    return implode(' - ', $items);
}

function badge_sync($status)
{
    if ($status == 'sudah') {
        return '<span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-bold">Sudah Sinkron</span>';
    }

    if ($status == 'gagal') {
        return '<span class="px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-bold">Gagal Sinkron</span>';
    }

    return '<span class="px-3 py-1 rounded-full bg-orange-100 text-orange-700 text-xs font-bold">Belum Sinkron</span>';
}

$nama_jalur_masuk = trim((string) ($data['jalur_masuk'] ?? ''));
if ($nama_jalur_masuk === '') {
    $nama_jalur_masuk = ref_nama($conn, 'jalur_masuk', $data['id_jalur_masuk_feeder'] ?? '');
}

$nama_wilayah = nama_wilayah_berantai($conn, $data['id_wilayah_feeder'] ?? '');

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
require_once __DIR__ . "/../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <?php show_alert(); ?>

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Detail Mahasiswa</h2>
            <p class="text-sm text-slate-500">
                Informasi lengkap mahasiswa dan status integrasi NeoFeeder/PDDikti.
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <a href="edit_mahasiswa.php?id=<?= $id_mahasiswa; ?>"
               class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                <i class="fa-solid fa-pen-to-square mr-2"></i>
                Edit
            </a>

            <a href="data_mahasiswa.php"
               class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                <i class="fa-solid fa-arrow-left mr-2"></i>
                Kembali
            </a>
        </div>
    </div>

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 text-center">
            <?php if (!empty($data['foto'])): ?>
                <img src="../../uploads/mahasiswa/<?= htmlspecialchars($data['foto']); ?>"
                     class="w-32 h-32 object-cover rounded-full mx-auto border-4 border-blue-100">
            <?php else: ?>
                <div class="w-32 h-32 rounded-full mx-auto bg-blue-100 flex items-center justify-center text-blue-700 text-5xl">
                    <i class="fa-solid fa-user-graduate"></i>
                </div>
            <?php endif; ?>

            <h3 class="mt-4 text-lg font-bold text-slate-800">
                <?= tampil($data['nama_mahasiswa']); ?>
            </h3>

            <p class="text-sm text-slate-500">
                NIM: <?= tampil($data['nim']); ?>
            </p>

            <div class="mt-4">
                <?= badge_sync($data['status_sync_feeder'] ?? 'belum'); ?>
            </div>
        </div>

        <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-5">

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
                <p class="text-sm text-slate-500">ID Feeder Mahasiswa</p>
                <h3 class="text-sm font-bold text-blue-700 mt-2 break-all">
                    <?= tampil($data['id_feeder'] ?: ($data['id_biodata_feeder'] ?? '')); ?>
                </h3>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
                <p class="text-sm text-slate-500">ID Registrasi Feeder</p>
                <h3 class="text-sm font-bold text-blue-700 mt-2 break-all">
                    <?= tampil($data['id_registrasi_feeder'] ?? ''); ?>
                </h3>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
                <p class="text-sm text-slate-500">Program Studi</p>
                <h3 class="text-lg font-bold text-slate-800 mt-2">
                    <?= tampil($data['nama_prodi']); ?>
                </h3>
                <p class="text-xs text-slate-500">
                    <?= tampil($data['kode_prodi']); ?> - <?= tampil($data['jenjang']); ?>
                </p>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
                <p class="text-sm text-slate-500">Terakhir Sinkron</p>
                <h3 class="text-sm font-bold text-slate-700 mt-2">
                    <?= !empty($data['last_sync_feeder']) ? tanggal_jam_indonesia($data['last_sync_feeder']) : '-'; ?>
                </h3>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
                <p class="text-sm text-slate-500">Kelas</p>
                <h3 class="text-lg font-bold text-slate-800 mt-2">
                    <?= tampil($data['nama_kelas']); ?>
                </h3>
            </div>

        </div>

    </section>

    <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Data Akademik</h3>

            <div class="space-y-3 text-sm">
                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">Angkatan</span>
                    <span class="font-semibold"><?= tampil($data['angkatan']); ?></span>
                </div>

                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">Semester</span>
                    <span class="font-semibold"><?= tampil($data['semester']); ?></span>
                </div>

                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">Tanggal Masuk</span>
                    <span class="font-semibold">
                        <?= !empty($data['tanggal_masuk']) ? tanggal_indonesia($data['tanggal_masuk']) : '-'; ?>
                    </span>
                </div>

                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">Tanggal Keluar</span>
                    <span class="font-semibold">
                        <?= !empty($data['tanggal_keluar']) ? tanggal_indonesia($data['tanggal_keluar']) : '-'; ?>
                    </span>
                </div>

                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">Jalur Masuk</span>
                    <span class="font-semibold text-right"><?= tampil($nama_jalur_masuk); ?></span>
                </div>

                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">Jenis Pendaftaran</span>
                    <span class="font-semibold"><?= tampil($data['jenis_pendaftaran'] ?? ''); ?></span>
                </div>

                <div class="flex justify-between">
                    <span class="text-slate-500">Status Mahasiswa</span>
                    <span class="font-semibold"><?= tampil($data['status_mahasiswa']); ?></span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Biodata Mahasiswa</h3>

            <div class="space-y-3 text-sm">
                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">Jenis Kelamin</span>
                    <span class="font-semibold">
                        <?= ($data['jenis_kelamin'] ?? '') == 'L' ? 'Laki-laki' : (($data['jenis_kelamin'] ?? '') == 'P' ? 'Perempuan' : '-'); ?>
                    </span>
                </div>

                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">Tempat, Tanggal Lahir</span>
                    <span class="font-semibold text-right">
                        <?= tampil($data['tempat_lahir']); ?>,
                        <?= !empty($data['tanggal_lahir']) ? tanggal_indonesia($data['tanggal_lahir']) : '-'; ?>
                    </span>
                </div>

                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">Agama</span>
                    <span class="font-semibold"><?= tampil($data['agama']); ?></span>
                </div>

                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">NIK</span>
                    <span class="font-semibold"><?= tampil($data['nik']); ?></span>
                </div>

                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">NISN</span>
                    <span class="font-semibold"><?= tampil($data['nisn']); ?></span>
                </div>

                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">NPWP</span>
                    <span class="font-semibold"><?= tampil($data['npwp']); ?></span>
                </div>

                <div class="flex justify-between">
                    <span class="text-slate-500">Kewarganegaraan</span>
                    <span class="font-semibold"><?= tampil($data['kewarganegaraan']); ?></span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Kontak & Alamat</h3>

            <div class="space-y-3 text-sm">
                <div class="border-b pb-2">
                    <p class="text-slate-500">Alamat</p>
                    <p class="font-semibold mt-1"><?= tampil($data['alamat']); ?></p>
                </div>

                <div class="flex justify-between border-b pb-2 gap-4">
                    <span class="text-slate-500">Wilayah</span>
                    <span class="font-semibold text-right"><?= tampil($nama_wilayah); ?></span>
                </div>

                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">Kode Pos</span>
                    <span class="font-semibold"><?= tampil($data['kode_pos']); ?></span>
                </div>

                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">Email</span>
                    <span class="font-semibold"><?= tampil(!empty($data['email']) ? $data['email'] : ($data['email_user'] ?? '')); ?></span>
                </div>

                <div class="flex justify-between">
                    <span class="text-slate-500">No. HP</span>
                    <span class="font-semibold"><?= tampil(!empty($data['no_hp']) ? $data['no_hp'] : ($data['no_hp_user'] ?? '')); ?></span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Orang Tua</h3>

            <div class="space-y-3 text-sm">
                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">Nama Ayah</span>
                    <span class="font-semibold"><?= tampil($data['nama_ayah']); ?></span>
                </div>

                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">Pekerjaan Ayah</span>
                    <span class="font-semibold"><?= tampil($data['pekerjaan_ayah']); ?></span>
                </div>

                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">Penghasilan Ayah</span>
                    <span class="font-semibold"><?= tampil($data['penghasilan_ayah'] ?? ''); ?></span>
                </div>

                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">Nama Ibu</span>
                    <span class="font-semibold"><?= tampil($data['nama_ibu']); ?></span>
                </div>

                <div class="flex justify-between border-b pb-2">
                    <span class="text-slate-500">Pekerjaan Ibu</span>
                    <span class="font-semibold"><?= tampil($data['pekerjaan_ibu']); ?></span>
                </div>

                <div class="flex justify-between">
                    <span class="text-slate-500">Penghasilan Ibu</span>
                    <span class="font-semibold"><?= tampil($data['penghasilan_ibu'] ?? ''); ?></span>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Akun Login</h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-slate-500">Username</p>
                    <p class="font-semibold mt-1"><?= tampil($data['username']); ?></p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-slate-500">Status Akun</p>
                    <p class="font-semibold mt-1"><?= tampil($data['status_user']); ?></p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-slate-500">Status Data</p>
                    <p class="font-semibold mt-1"><?= tampil($data['status']); ?></p>
                </div>
            </div>
        </div>

    </section>

</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
