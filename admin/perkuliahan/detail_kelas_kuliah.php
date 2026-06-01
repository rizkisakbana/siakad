<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Detail Kelas Kuliah";
$page_subtitle = "Dosen pengajar, peserta KRS, dan nilai kelas kuliah";

$id = intval($_GET['id'] ?? 0);
if ($id < 1) {
    set_alert('error', 'ID kelas kuliah tidak valid.');
    header('Location: data_kelas_kuliah.php');
    exit;
}

$q = mysqli_query($conn, "
    SELECT kk.*, mk.kode_mk, mk.nama_mk, mk.total_sks, p.nama_prodi, p.jenjang, ta.tahun, ta.semester AS semester_tahun
    FROM kelas_kuliah kk
    LEFT JOIN mata_kuliah mk ON kk.id_mk = mk.id_mk
    LEFT JOIN prodi p ON kk.id_prodi = p.id_prodi
    LEFT JOIN tahun_akademik ta ON kk.id_tahun = ta.id_tahun
    WHERE kk.id_kelas_kuliah = '$id'
    LIMIT 1
");

if (!$q || mysqli_num_rows($q) < 1) {
    set_alert('error', 'Data kelas kuliah tidak ditemukan.');
    header('Location: data_kelas_kuliah.php');
    exit;
}

$data = mysqli_fetch_assoc($q);

$pengajar = mysqli_query($conn, "
    SELECT dpk.*, d.nama_dosen, d.nidn, d.nidk
    FROM dosen_pengajar_kelas dpk
    LEFT JOIN dosen d ON dpk.id_dosen = d.id_dosen
    WHERE dpk.id_kelas_kuliah = '$id'
    ORDER BY dpk.urutan_pengajar ASC, d.nama_dosen ASC
");

$peserta = mysqli_query($conn, "
    SELECT pkk.*, m.nim AS nim_master, m.nama_mahasiswa
    FROM peserta_kelas_kuliah pkk
    LEFT JOIN mahasiswa m ON pkk.id_mahasiswa = m.id_mahasiswa
    WHERE pkk.id_kelas_kuliah = '$id'
    ORDER BY m.nama_mahasiswa ASC
    LIMIT 100
");

$nilai = mysqli_query($conn, "
    SELECT n.*, m.nim, m.nama_mahasiswa
    FROM nilai n
    LEFT JOIN mahasiswa m ON n.id_mahasiswa = m.id_mahasiswa
    WHERE n.id_kelas_kuliah = '$id'
    ORDER BY m.nama_mahasiswa ASC
    LIMIT 100
");

$total_pengajar = $pengajar ? mysqli_num_rows($pengajar) : 0;
$total_peserta = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM peserta_kelas_kuliah WHERE id_kelas_kuliah='$id'"))['total'] ?? 0;
$total_nilai = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM nilai WHERE id_kelas_kuliah='$id'"))['total'] ?? 0;

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">
    <?php show_alert(); ?>

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800"><?= htmlspecialchars($data['kode_mk'] . ' - ' . $data['nama_mk']); ?></h2>
            <p class="text-sm text-slate-500"><?= htmlspecialchars($data['nama_kelas_kuliah']); ?> &bull; <?= htmlspecialchars(($data['tahun'] ?? '-') . ' ' . ($data['semester_tahun'] ?? '-')); ?> &bull; <?= htmlspecialchars(($data['jenjang'] ?? '-') . ' ' . ($data['nama_prodi'] ?? '-')); ?></p>
        </div>
        <a href="data_kelas_kuliah.php" class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold"><i class="fa-solid fa-arrow-left mr-2"></i> Kembali</a>
    </div>

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5"><p class="text-sm text-slate-500">SKS</p><h2 class="text-3xl font-bold text-blue-700 mt-2"><?= number_format($data['total_sks'] ?? 0); ?></h2></div>
        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5"><p class="text-sm text-slate-500">Dosen Pengajar</p><h2 class="text-3xl font-bold text-green-700 mt-2"><?= number_format($total_pengajar); ?></h2></div>
        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5"><p class="text-sm text-slate-500">Peserta</p><h2 class="text-3xl font-bold text-purple-700 mt-2"><?= number_format($total_peserta); ?></h2></div>
        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5"><p class="text-sm text-slate-500">Nilai</p><h2 class="text-3xl font-bold text-orange-700 mt-2"><?= number_format($total_nilai); ?></h2></div>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Informasi Feeder</h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between gap-4 border-b pb-3"><span class="text-slate-500">ID Kelas Feeder</span><span class="font-semibold text-slate-800 text-right break-all"><?= htmlspecialchars($data['id_kelas_kuliah_feeder'] ?? '-'); ?></span></div>
                <div class="flex justify-between gap-4 border-b pb-3"><span class="text-slate-500">ID Semester</span><span class="font-semibold text-slate-800"><?= htmlspecialchars($data['id_semester_feeder'] ?? '-'); ?></span></div>
                <div class="flex justify-between gap-4 border-b pb-3"><span class="text-slate-500">Mode</span><span class="font-semibold text-slate-800"><?= htmlspecialchars($data['mode_kuliah'] ?? '-'); ?></span></div>
                <div class="flex justify-between gap-4"><span class="text-slate-500">Kapasitas</span><span class="font-semibold text-slate-800"><?= number_format($data['kapasitas'] ?? 0); ?></span></div>
            </div>
        </div>

        <div class="xl:col-span-2 bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Dosen Pengajar</h3>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100 text-slate-700"><tr><th class="px-4 py-3 text-left">Dosen</th><th class="px-4 py-3 text-left">SKS Substansi</th><th class="px-4 py-3 text-left">Pertemuan</th><th class="px-4 py-3 text-left">Evaluasi</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if ($pengajar && mysqli_num_rows($pengajar) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($pengajar)): ?>
                                <tr><td class="px-4 py-3"><div class="font-semibold text-slate-800"><?= htmlspecialchars($row['nama_dosen'] ?? '-'); ?></div><div class="text-xs text-slate-500"><?= htmlspecialchars($row['nidn'] ?: ($row['nidk'] ?? '-')); ?></div></td><td class="px-4 py-3"><?= number_format($row['sks_substansi_total'], 2); ?></td><td class="px-4 py-3"><?= number_format($row['realisasi_tatap_muka']); ?> / <?= number_format($row['rencana_tatap_muka']); ?></td><td class="px-4 py-3"><?= htmlspecialchars($row['jenis_evaluasi'] ?? '-'); ?></td></tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada dosen pengajar.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Peserta KRS</h3>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100 text-slate-700"><tr><th class="px-4 py-3 text-left">Mahasiswa</th><th class="px-4 py-3 text-left">Status</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if ($peserta && mysqli_num_rows($peserta) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($peserta)): ?>
                                <tr><td class="px-4 py-3"><div class="font-semibold text-slate-800"><?= htmlspecialchars($row['nama_mahasiswa'] ?? '-'); ?></div><div class="text-xs text-slate-500"><?= htmlspecialchars($row['nim_master'] ?: ($row['nim'] ?? '-')); ?></div></td><td class="px-4 py-3"><?= htmlspecialchars($row['status_peserta']); ?></td></tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="2" class="px-4 py-8 text-center text-slate-500">Belum ada peserta.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Nilai</h3>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100 text-slate-700"><tr><th class="px-4 py-3 text-left">Mahasiswa</th><th class="px-4 py-3 text-left">Angka</th><th class="px-4 py-3 text-left">Huruf</th><th class="px-4 py-3 text-left">Indeks</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if ($nilai && mysqli_num_rows($nilai) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($nilai)): ?>
                                <tr><td class="px-4 py-3"><div class="font-semibold text-slate-800"><?= htmlspecialchars($row['nama_mahasiswa'] ?? '-'); ?></div><div class="text-xs text-slate-500"><?= htmlspecialchars($row['nim'] ?? '-'); ?></div></td><td class="px-4 py-3"><?= number_format($row['nilai_akhir'], 2); ?></td><td class="px-4 py-3"><?= htmlspecialchars($row['nilai_huruf'] ?: '-'); ?></td><td class="px-4 py-3"><?= number_format($row['nilai_indeks'] ?? 0, 2); ?></td></tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada nilai.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>

<?php require_once "../../includes/footer.php"; ?>
