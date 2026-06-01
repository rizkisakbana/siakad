<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/alert.php";
require_once "../../includes/log_aktivitas.php";
require_once "../../includes/akademik_inti_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

function jadwal_options($conn, $sql) { return mysqli_query($conn, $sql); }

$tahun = jadwal_options($conn, "SELECT * FROM tahun_akademik ORDER BY status ASC, tahun DESC, semester ASC");
$kelas = jadwal_options($conn, "SELECT k.*, p.nama_prodi FROM kelas k LEFT JOIN prodi p ON p.id_prodi=k.id_prodi WHERE k.status='aktif' ORDER BY p.nama_prodi,k.nama_kelas");
$mk = jadwal_options($conn, "SELECT mk.*, k.nama_kurikulum, p.nama_prodi FROM mata_kuliah mk JOIN kurikulum k ON k.id_kurikulum=mk.id_kurikulum LEFT JOIN prodi p ON p.id_prodi=k.id_prodi WHERE mk.status='aktif' ORDER BY p.nama_prodi,mk.semester,mk.kode_mk");
$dosen = jadwal_options($conn, "SELECT * FROM dosen WHERE status='aktif' ORDER BY nama_dosen");
$ruangan = jadwal_options($conn, "SELECT * FROM ruangan WHERE status='aktif' ORDER BY nama_ruangan");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_tahun = intval($_POST['id_tahun'] ?? 0);
    $id_kelas = intval($_POST['id_kelas'] ?? 0);
    $id_mk = intval($_POST['id_mk'] ?? 0);
    $id_dosen = intval($_POST['id_dosen'] ?? 0);
    $id_ruangan = intval($_POST['id_ruangan'] ?? 0);
    $hari = mysqli_real_escape_string($conn, $_POST['hari'] ?? '');
    $jam_mulai = mysqli_real_escape_string($conn, $_POST['jam_mulai'] ?? '');
    $jam_selesai = mysqli_real_escape_string($conn, $_POST['jam_selesai'] ?? '');
    $metode = mysqli_real_escape_string($conn, $_POST['metode'] ?? 'tatap muka');
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'aktif');

    if ($id_tahun <= 0 || $id_kelas <= 0 || $id_mk <= 0 || $id_dosen <= 0 || $id_ruangan <= 0 || empty($hari) || empty($jam_mulai) || empty($jam_selesai)) {
        set_alert('error', 'Semua field wajib diisi.');
    } elseif ($jam_mulai >= $jam_selesai) {
        set_alert('error', 'Jam mulai harus lebih kecil dari jam selesai.');
    } else {
        $cek = mysqli_query($conn, "
            SELECT id_jadwal FROM jadwal_kuliah
            WHERE id_tahun='$id_tahun' AND hari='$hari' AND status='aktif'
            AND ('$jam_mulai' < jam_selesai AND '$jam_selesai' > jam_mulai)
            AND (id_kelas='$id_kelas' OR id_dosen='$id_dosen' OR id_ruangan='$id_ruangan')
            LIMIT 1
        ");

        if ($cek && mysqli_num_rows($cek) > 0) {
            set_alert('error', 'Jadwal bentrok dengan kelas, dosen, atau ruangan lain.');
        } else {
            mysqli_begin_transaction($conn);
            try {
                $simpan = mysqli_query($conn, "
                    INSERT INTO jadwal_kuliah
                    (id_tahun,id_kelas,id_mk,id_dosen,id_ruangan,hari,jam_mulai,jam_selesai,metode,status,status_sync_feeder)
                    VALUES
                    ('$id_tahun','$id_kelas','$id_mk','$id_dosen','$id_ruangan','$hari','$jam_mulai','$jam_selesai','$metode','$status','belum')
                ");
                if (!$simpan) throw new Exception('Gagal menyimpan jadwal: ' . mysqli_error($conn));

                rebuild_akademik_inti($conn, false);
                mysqli_commit($conn);

                simpan_log($conn, $_SESSION['id_user'], 'Menambah jadwal kuliah', 'Jadwal');
                set_alert('success', 'Jadwal berhasil ditambahkan dan relasi kelas kuliah dibangun ulang.');
                header('Location: data_jadwal.php');
                exit;
            } catch (Throwable $e) {
                mysqli_rollback($conn);
                set_alert('error', $e->getMessage());
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
    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
        <div class="mb-6">
            <h2 class="text-xl font-bold text-slate-800">Tambah Jadwal Kuliah</h2>
            <p class="text-sm text-slate-500 mt-1">Jadwal akan otomatis membentuk kelas kuliah NeoFeeder-ready.</p>
        </div>

        <form method="POST" class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-semibold mb-2">Tahun Akademik</label>
                <select name="id_tahun" required class="w-full rounded-xl border border-slate-300 px-4 py-3">
                    <option value="">Pilih tahun</option>
                    <?php while ($r = mysqli_fetch_assoc($tahun)): ?>
                        <option value="<?= $r['id_tahun']; ?>"><?= htmlspecialchars($r['tahun'].' - '.$r['semester']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2">Kelas</label>
                <select name="id_kelas" required class="w-full rounded-xl border border-slate-300 px-4 py-3">
                    <option value="">Pilih kelas</option>
                    <?php while ($r = mysqli_fetch_assoc($kelas)): ?>
                        <option value="<?= $r['id_kelas']; ?>"><?= htmlspecialchars(($r['nama_prodi'] ?? '-') . ' - ' . $r['nama_kelas']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="lg:col-span-2">
                <label class="block text-sm font-semibold mb-2">Mata Kuliah</label>
                <select name="id_mk" required class="w-full rounded-xl border border-slate-300 px-4 py-3">
                    <option value="">Pilih mata kuliah</option>
                    <?php while ($r = mysqli_fetch_assoc($mk)): ?>
                        <option value="<?= $r['id_mk']; ?>"><?= htmlspecialchars(($r['nama_prodi'] ?? '-') . ' - Smt ' . $r['semester'] . ' - ' . $r['kode_mk'] . ' ' . $r['nama_mk']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2">Dosen</label>
                <select name="id_dosen" required class="w-full rounded-xl border border-slate-300 px-4 py-3">
                    <option value="">Pilih dosen</option>
                    <?php while ($r = mysqli_fetch_assoc($dosen)): ?>
                        <option value="<?= $r['id_dosen']; ?>"><?= htmlspecialchars($r['nama_dosen']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2">Ruangan</label>
                <select name="id_ruangan" required class="w-full rounded-xl border border-slate-300 px-4 py-3">
                    <option value="">Pilih ruangan</option>
                    <?php while ($r = mysqli_fetch_assoc($ruangan)): ?>
                        <option value="<?= $r['id_ruangan']; ?>"><?= htmlspecialchars($r['nama_ruangan']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2">Hari</label>
                <select name="hari" required class="w-full rounded-xl border border-slate-300 px-4 py-3">
                    <?php foreach (['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'] as $h): ?>
                        <option value="<?= $h; ?>"><?= $h; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-semibold mb-2">Jam Mulai</label>
                    <input type="time" name="jam_mulai" required class="w-full rounded-xl border border-slate-300 px-4 py-3">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Jam Selesai</label>
                    <input type="time" name="jam_selesai" required class="w-full rounded-xl border border-slate-300 px-4 py-3">
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2">Metode</label>
                <select name="metode" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                    <option value="tatap muka">Tatap muka</option>
                    <option value="online">Online</option>
                    <option value="hybrid">Hybrid</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2">Status</label>
                <select name="status" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </select>
            </div>
            <div class="lg:col-span-2 flex gap-3">
                <button class="px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold"><i class="fa-solid fa-save mr-2"></i>Simpan</button>
                <a href="data_jadwal.php" class="px-5 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">Kembali</a>
            </div>
        </form>
    </section>
</main>

<?php require_once "../../includes/footer.php"; ?>
