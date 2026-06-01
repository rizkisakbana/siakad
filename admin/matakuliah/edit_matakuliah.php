<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";
require_once "../../includes/log_aktivitas.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Edit Mata Kuliah";
$page_subtitle = "Mengubah data mata kuliah berdasarkan kurikulum";

$id_mk = intval($_GET['id'] ?? 0);

if ($id_mk <= 0) {
    set_alert("error", "ID mata kuliah tidak valid.");
    header("Location: data_matakuliah.php");
    exit;
}

$query = mysqli_query($conn, "
    SELECT 
        mata_kuliah.*,
        kurikulum.nama_kurikulum,
        kurikulum.tahun_kurikulum,
        prodi.nama_prodi,
        prodi.jenjang
    FROM mata_kuliah
    LEFT JOIN kurikulum ON mata_kuliah.id_kurikulum = kurikulum.id_kurikulum
    LEFT JOIN prodi ON kurikulum.id_prodi = prodi.id_prodi
    WHERE mata_kuliah.id_mk = '$id_mk'
    LIMIT 1
");

if (mysqli_num_rows($query) < 1) {
    set_alert("error", "Data mata kuliah tidak ditemukan.");
    header("Location: data_matakuliah.php");
    exit;
}

$data = mysqli_fetch_assoc($query);

$total_jadwal = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM jadwal_kuliah 
    WHERE id_mk = '$id_mk'
"))['total'] ?? 0;

$data_kurikulum = mysqli_query($conn, "
    SELECT 
        kurikulum.*,
        prodi.kode_prodi,
        prodi.nama_prodi,
        prodi.jenjang
    FROM kurikulum
    LEFT JOIN prodi ON kurikulum.id_prodi = prodi.id_prodi
    WHERE kurikulum.status = 'aktif'
    ORDER BY prodi.nama_prodi ASC, kurikulum.tahun_kurikulum DESC
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_kurikulum = intval($_POST['id_kurikulum'] ?? 0);
    $kode_mk = mysqli_real_escape_string($conn, strtoupper(trim($_POST['kode_mk'] ?? '')));
    $nama_mk = mysqli_real_escape_string($conn, trim($_POST['nama_mk'] ?? ''));
    $semester = intval($_POST['semester'] ?? 0);
    $sks_teori = intval($_POST['sks_teori'] ?? 0);
    $sks_praktik = intval($_POST['sks_praktik'] ?? 0);
    $jenis_mk = mysqli_real_escape_string($conn, $_POST['jenis_mk'] ?? 'wajib');
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'aktif');

    if ($id_kurikulum <= 0 || empty($kode_mk) || empty($nama_mk) || $semester <= 0) {
        set_alert("error", "Kurikulum, kode mata kuliah, nama mata kuliah, dan semester wajib diisi.");
    } elseif ($semester < 1 || $semester > 8) {
        set_alert("error", "Semester harus berada pada rentang 1 sampai 8.");
    } elseif ($sks_teori < 0 || $sks_praktik < 0) {
        set_alert("error", "SKS teori dan SKS praktik tidak boleh bernilai negatif.");
    } elseif (($sks_teori + $sks_praktik) <= 0) {
        set_alert("error", "Total SKS harus lebih dari 0.");
    } else {
        $cek_kurikulum = mysqli_query($conn, "
            SELECT 
                kurikulum.*,
                prodi.nama_prodi
            FROM kurikulum
            LEFT JOIN prodi ON kurikulum.id_prodi = prodi.id_prodi
            WHERE kurikulum.id_kurikulum = '$id_kurikulum'
            LIMIT 1
        ");

        if (mysqli_num_rows($cek_kurikulum) < 1) {
            set_alert("error", "Kurikulum tidak ditemukan.");
        } else {
            $data_kur = mysqli_fetch_assoc($cek_kurikulum);

            $cek_duplikat = mysqli_query($conn, "
                SELECT id_mk 
                FROM mata_kuliah
                WHERE id_kurikulum = '$id_kurikulum'
                AND kode_mk = '$kode_mk'
                AND id_mk != '$id_mk'
                LIMIT 1
            ");

            if (mysqli_num_rows($cek_duplikat) > 0) {
                set_alert("error", "Kode mata kuliah sudah digunakan pada kurikulum tersebut.");
            } else {
                $update = mysqli_query($conn, "
                    UPDATE mata_kuliah SET
                        id_kurikulum = '$id_kurikulum',
                        kode_mk = '$kode_mk',
                        nama_mk = '$nama_mk',
                        semester = '$semester',
                        sks_teori = '$sks_teori',
                        sks_praktik = '$sks_praktik',
                        jenis_mk = '$jenis_mk',
                        status = '$status'
                    WHERE id_mk = '$id_mk'
                ");

                if ($update) {
                    simpan_log(
                        $conn,
                        $_SESSION['id_user'],
                        "Mengubah mata kuliah: $kode_mk - $nama_mk pada kurikulum " . $data_kur['nama_kurikulum'],
                        "Mata Kuliah"
                    );

                    set_alert("success", "Mata kuliah berhasil diperbarui.");
                    header("Location: data_matakuliah.php");
                    exit;
                } else {
                    set_alert("error", "Mata kuliah gagal diperbarui.");
                }
            }
        }
    }
}

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <?php show_alert(); ?>

    <!-- <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Edit Mata Kuliah</h2>
            <p class="text-sm text-slate-500">
                Perbarui data mata kuliah sesuai kurikulum program studi.
            </p>
        </div>

        <a href="data_matakuliah.php"
           class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            Kembali
        </a>
    </div> -->

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

            <form method="POST" class="grid grid-cols-1 lg:grid-cols-2 gap-5">

                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Kurikulum
                    </label>
                    <select name="id_kurikulum"
                            required
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                        <option value="">-- Pilih Kurikulum --</option>

                        <?php while ($kurikulum = mysqli_fetch_assoc($data_kurikulum)): ?>
                            <option value="<?= $kurikulum['id_kurikulum']; ?>"
                                <?= $data['id_kurikulum'] == $kurikulum['id_kurikulum'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($kurikulum['nama_kurikulum']); ?> |
                                <?= htmlspecialchars($kurikulum['nama_prodi']); ?> -
                                <?= htmlspecialchars($kurikulum['jenjang']); ?> |
                                Tahun <?= htmlspecialchars($kurikulum['tahun_kurikulum']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <p class="text-xs text-slate-500 mt-2">
                        Mata kuliah wajib terhubung dengan kurikulum. Kurikulum sudah terhubung dengan program studi.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Kode Mata Kuliah
                    </label>
                    <input type="text"
                           name="kode_mk"
                           required
                           value="<?= htmlspecialchars($data['kode_mk']); ?>"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Nama Mata Kuliah
                    </label>
                    <input type="text"
                           name="nama_mk"
                           required
                           value="<?= htmlspecialchars($data['nama_mk']); ?>"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Semester
                    </label>
                    <select name="semester"
                            required
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?= $i; ?>" <?= $data['semester'] == $i ? 'selected' : ''; ?>>
                                Semester <?= $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Jenis Mata Kuliah
                    </label>
                    <select name="jenis_mk"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                        <option value="wajib" <?= $data['jenis_mk'] == 'wajib' ? 'selected' : ''; ?>>
                            Wajib
                        </option>
                        <option value="pilihan" <?= $data['jenis_mk'] == 'pilihan' ? 'selected' : ''; ?>>
                            Pilihan
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        SKS Teori
                    </label>
                    <input type="number"
                           name="sks_teori"
                           id="sks_teori"
                           min="0"
                           value="<?= htmlspecialchars($data['sks_teori']); ?>"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        SKS Praktik
                    </label>
                    <input type="number"
                           name="sks_praktik"
                           id="sks_praktik"
                           min="0"
                           value="<?= htmlspecialchars($data['sks_praktik']); ?>"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Total SKS
                    </label>
                    <input type="text"
                           id="total_sks_preview"
                           value="<?= number_format($data['total_sks']); ?> SKS"
                           readonly
                           class="w-full rounded-xl border border-slate-300 bg-slate-100 px-4 py-3 text-slate-700 font-semibold outline-none">

                    <p class="text-xs text-slate-500 mt-2">
                        Total SKS otomatis dari SKS teori + SKS praktik.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Status
                    </label>
                    <select name="status"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                        <option value="aktif" <?= $data['status'] == 'aktif' ? 'selected' : ''; ?>>
                            Aktif
                        </option>
                        <option value="nonaktif" <?= $data['status'] == 'nonaktif' ? 'selected' : ''; ?>>
                            Nonaktif
                        </option>
                    </select>
                </div>

                <div class="lg:col-span-2 flex flex-col sm:flex-row gap-3 pt-4">
                    <button type="submit"
                            class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                        <i class="fa-solid fa-save mr-2"></i>
                        Update
                    </button>

                    <a href="data_matakuliah.php"
                       class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                        <i class="fa-solid fa-arrow-left mr-2"></i>
                        Kembali
                    </a>
                </div>

            </form>

        </div>

        <aside class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

            <h3 class="text-lg font-bold text-slate-800 mb-4">
                Informasi Mata Kuliah
            </h3>

            <div class="space-y-4 text-sm text-slate-600">
                <div class="p-4 rounded-xl bg-blue-50 border border-blue-100">
                    <p class="font-semibold text-blue-700 mb-1">Kurikulum Saat Ini</p>
                    <p><?= htmlspecialchars($data['nama_kurikulum'] ?? '-'); ?> - <?= htmlspecialchars($data['tahun_kurikulum'] ?? '-'); ?></p>
                </div>

                <div class="p-4 rounded-xl bg-green-50 border border-green-100">
                    <p class="font-semibold text-green-700 mb-1">Program Studi</p>
                    <p><?= htmlspecialchars($data['nama_prodi'] ?? '-'); ?> - <?= htmlspecialchars($data['jenjang'] ?? '-'); ?></p>
                </div>

                <div class="p-4 rounded-xl bg-orange-50 border border-orange-100">
                    <p class="font-semibold text-orange-700 mb-1">Penggunaan Jadwal</p>
                    <p>
                        Mata kuliah ini digunakan pada 
                        <strong><?= number_format($total_jadwal); ?></strong> jadwal kuliah.
                    </p>
                </div>
            </div>

            <div class="mt-6 space-y-3">
                <a href="detail_matakuliah.php?id=<?= $data['id_mk']; ?>"
                   class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-blue-100 hover:bg-blue-200 text-blue-700 font-semibold">
                    <i class="fa-solid fa-eye mr-2"></i>
                    Detail Mata Kuliah
                </a>

                <!-- <a href="data_matakuliah.php"
                   class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold">
                    <i class="fa-solid fa-list mr-2"></i>
                    Data Mata Kuliah
                </a> -->

                <?php if ($total_jadwal < 1): ?>
                    <a href="hapus_matakuliah.php?id=<?= $data['id_mk']; ?>"
                       onclick="return confirm('Yakin ingin menghapus mata kuliah ini?')"
                       class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold">
                        <i class="fa-solid fa-trash mr-2"></i>
                        Hapus Mata Kuliah
                    </a>
                <?php else: ?>
                    <button type="button"
                            onclick="alert('Mata kuliah tidak dapat dihapus karena sudah digunakan pada jadwal kuliah.')"
                            class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-300 text-slate-600 font-semibold cursor-not-allowed">
                        <i class="fa-solid fa-lock mr-2"></i>
                        Tidak Bisa Dihapus
                    </button>
                <?php endif; ?>
            </div>

        </aside>

    </section>

</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sksTeori = document.getElementById('sks_teori');
    const sksPraktik = document.getElementById('sks_praktik');
    const totalPreview = document.getElementById('total_sks_preview');

    function hitungTotalSks() {
        const teori = parseInt(sksTeori.value) || 0;
        const praktik = parseInt(sksPraktik.value) || 0;
        const total = teori + praktik;

        totalPreview.value = total + ' SKS';
    }

    if (sksTeori && sksPraktik && totalPreview) {
        sksTeori.addEventListener('input', hitungTotalSks);
        sksPraktik.addEventListener('input', hitungTotalSks);
        hitungTotalSks();
    }
});
</script>

<?php require_once "../../includes/footer.php"; ?>