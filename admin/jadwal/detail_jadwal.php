<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/alert.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$id_jadwal = intval($_GET['id'] ?? 0);
if ($id_jadwal <= 0) {
    set_alert('error', 'ID jadwal tidak valid.');
    header('Location: data_jadwal.php');
    exit;
}

$q = mysqli_query($conn, "
    SELECT j.*, ta.tahun, ta.semester AS semester_tahun, k.nama_kelas, k.kode_kelas,
           mk.kode_mk, mk.nama_mk, mk.total_sks, d.nama_dosen, r.nama_ruangan,
           kk.id_kelas_kuliah AS id_kk, kk.id_kelas_kuliah_feeder AS feeder_kk
    FROM jadwal_kuliah j
    JOIN tahun_akademik ta ON ta.id_tahun=j.id_tahun
    JOIN kelas k ON k.id_kelas=j.id_kelas
    JOIN mata_kuliah mk ON mk.id_mk=j.id_mk
    JOIN dosen d ON d.id_dosen=j.id_dosen
    JOIN ruangan r ON r.id_ruangan=j.id_ruangan
    LEFT JOIN kelas_kuliah kk ON kk.id_jadwal=j.id_jadwal
    WHERE j.id_jadwal='$id_jadwal'
    LIMIT 1
");

if (!$q || mysqli_num_rows($q) < 1) {
    set_alert('error', 'Data jadwal tidak ditemukan.');
    header('Location: data_jadwal.php');
    exit;
}

$data = mysqli_fetch_assoc($q);

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">
    <?php show_alert(); ?>
    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Detail Jadwal Kuliah</h2>
                <p class="text-sm text-slate-500 mt-1"><?= htmlspecialchars($data['kode_mk'] . ' - ' . $data['nama_mk']); ?></p>
            </div>
            <div class="flex gap-3">
                <a href="edit_jadwal.php?id=<?= $id_jadwal; ?>" class="px-4 py-3 rounded-xl bg-yellow-600 text-white font-semibold">Edit</a>
                <a href="data_jadwal.php" class="px-4 py-3 rounded-xl bg-slate-700 text-white font-semibold">Kembali</a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 text-sm">
            <?php
            $items = [
                'Tahun Akademik' => $data['tahun'] . ' - ' . $data['semester_tahun'],
                'Kelas' => $data['kode_kelas'] . ' - ' . $data['nama_kelas'],
                'Mata Kuliah' => $data['kode_mk'] . ' - ' . $data['nama_mk'],
                'Dosen' => $data['nama_dosen'],
                'Ruangan' => $data['nama_ruangan'],
                'Hari/Jam' => $data['hari'] . ', ' . substr($data['jam_mulai'], 0, 5) . '-' . substr($data['jam_selesai'], 0, 5),
                'Metode' => $data['metode'],
                'Status' => $data['status'],
                'ID Kelas Kuliah Lokal' => $data['id_kk'] ?: '-',
                'ID Kelas Kuliah Feeder' => $data['feeder_kk'] ?: '-',
                'Status Sync' => $data['status_sync_feeder'] ?: 'belum',
                'Terakhir Sync' => $data['last_sync_feeder'] ?: '-',
            ];
            ?>
            <?php foreach ($items as $label => $value): ?>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-slate-500"><?= htmlspecialchars($label); ?></p>
                    <p class="font-semibold mt-1 break-all"><?= htmlspecialchars((string)$value); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<?php require_once "../../includes/footer.php"; ?>
