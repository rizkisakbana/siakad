<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";
require_once "../../includes/log_aktivitas.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Tambah Ruangan";
$page_subtitle = "Menambahkan master data ruangan";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_ruangan = mysqli_real_escape_string($conn, strtoupper(trim($_POST['kode_ruangan'] ?? '')));
    $nama_ruangan = mysqli_real_escape_string($conn, trim($_POST['nama_ruangan'] ?? ''));
    $gedung = mysqli_real_escape_string($conn, trim($_POST['gedung'] ?? ''));
    $lantai = mysqli_real_escape_string($conn, trim($_POST['lantai'] ?? ''));
    $kapasitas = intval($_POST['kapasitas'] ?? 0);
    $jenis_ruangan = mysqli_real_escape_string($conn, $_POST['jenis_ruangan'] ?? 'kelas');
    $fasilitas = mysqli_real_escape_string($conn, trim($_POST['fasilitas'] ?? ''));
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'aktif');

    if (empty($kode_ruangan) || empty($nama_ruangan)) {
        set_alert("error", "Kode ruangan dan nama ruangan wajib diisi.");
    } elseif ($kapasitas < 0) {
        set_alert("error", "Kapasitas tidak boleh bernilai negatif.");
    } else {
        $cek_kode = mysqli_query($conn, "
            SELECT id_ruangan 
            FROM ruangan 
            WHERE kode_ruangan = '$kode_ruangan'
            LIMIT 1
        ");

        if ($cek_kode && mysqli_num_rows($cek_kode) > 0) {
            set_alert("error", "Kode ruangan sudah digunakan.");
        } else {
            $simpan = mysqli_query($conn, "
                INSERT INTO ruangan
                (
                    kode_ruangan,
                    nama_ruangan,
                    gedung,
                    lantai,
                    kapasitas,
                    jenis_ruangan,
                    fasilitas,
                    status
                )
                VALUES
                (
                    '$kode_ruangan',
                    '$nama_ruangan',
                    '$gedung',
                    '$lantai',
                    '$kapasitas',
                    '$jenis_ruangan',
                    '$fasilitas',
                    '$status'
                )
            ");

            if ($simpan) {
                simpan_log(
                    $conn,
                    $_SESSION['id_user'],
                    "Menambahkan ruangan: $kode_ruangan - $nama_ruangan",
                    "Ruangan"
                );

                set_alert("success", "Data ruangan berhasil ditambahkan.");
                header("Location: data_ruangan.php");
                exit;
            } else {
                set_alert("error", "Data ruangan gagal ditambahkan.");
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
            <h2 class="text-xl font-bold text-slate-800">Tambah Ruangan</h2>
            <p class="text-sm text-slate-500">
                Lengkapi data ruangan yang akan digunakan untuk jadwal kuliah.
            </p>
        </div>

        <a href="data_ruangan.php"
           class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            Kembali
        </a>
    </div> -->

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

            <form method="POST" class="grid grid-cols-1 lg:grid-cols-2 gap-5">

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Kode Ruangan
                    </label>
                    <input type="text"
                           name="kode_ruangan"
                           required
                           placeholder="Contoh: R101 / LAB01"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Nama Ruangan
                    </label>
                    <input type="text"
                           name="nama_ruangan"
                           required
                           placeholder="Contoh: Ruang 101 / Lab Komputer"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Gedung
                    </label>
                    <input type="text"
                           name="gedung"
                           placeholder="Contoh: Gedung A"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Lantai
                    </label>
                    <input type="text"
                           name="lantai"
                           placeholder="Contoh: 1 / 2 / 3"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Kapasitas
                    </label>
                    <input type="number"
                           name="kapasitas"
                           min="0"
                           value="0"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Jenis Ruangan
                    </label>
                    <select name="jenis_ruangan"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                        <option value="kelas">Kelas</option>
                        <option value="laboratorium">Laboratorium</option>
                        <option value="aula">Aula</option>
                        <option value="online">Online</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Fasilitas
                    </label>
                    <textarea name="fasilitas"
                              rows="4"
                              placeholder="Contoh: AC, Proyektor, Whiteboard, Komputer, Internet"
                              class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Status
                    </label>
                    <select name="status"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>

                <div class="lg:col-span-2 flex flex-col sm:flex-row gap-3 pt-4">
                    <button type="submit"
                            class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                        <i class="fa-solid fa-save mr-2"></i>
                        Simpan
                    </button>

                    <a href="data_ruangan.php"
                       class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                        <i class="fa-solid fa-arrow-left mr-2"></i>
                        Kembali
                    </a>
                </div>

            </form>

        </div>

        <aside class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

            <h3 class="text-lg font-bold text-slate-800 mb-4">
                Informasi Pengisian
            </h3>

            <div class="space-y-4 text-sm text-slate-600">
                <div class="p-4 rounded-xl bg-blue-50 border border-blue-100">
                    <p class="font-semibold text-blue-700 mb-1">Kode Ruangan</p>
                    <p>Kode ruangan harus unik, misalnya R101, R102, LAB01, atau ONLINE.</p>
                </div>

                <div class="p-4 rounded-xl bg-green-50 border border-green-100">
                    <p class="font-semibold text-green-700 mb-1">Kapasitas</p>
                    <p>Kapasitas digunakan untuk menyesuaikan jumlah mahasiswa pada jadwal kuliah.</p>
                </div>

                <div class="p-4 rounded-xl bg-orange-50 border border-orange-100">
                    <p class="font-semibold text-orange-700 mb-1">Status Ruangan</p>
                    <p>Ruangan nonaktif atau maintenance nantinya tidak boleh dipilih pada jadwal aktif.</p>
                </div>
            </div>

            <div class="mt-6">
                <a href="data_ruangan.php"
                   class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold">
                    <i class="fa-solid fa-list mr-2"></i>
                    Data Ruangan
                </a>
            </div>

        </aside>

    </section>

</main>

<?php require_once "../../includes/footer.php"; ?>