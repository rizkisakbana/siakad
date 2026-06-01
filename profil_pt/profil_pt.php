<?php
require_once "../includes/auth.php";
require_once "../config/database.php";
require_once "../includes/helper.php";
require_once "profil_helper.php";

cek_login();

$page_title = "Profil Perguruan Tinggi";
$page_subtitle = "Profil resmi Akademi Teknik Informatika Tunas Bangsa Jakarta";

$q_profil = mysqli_query($conn, "
    SELECT *
    FROM profil_pt
    ORDER BY id_pt DESC
    LIMIT 1
");

$profil = ($q_profil && mysqli_num_rows($q_profil) > 0) ? mysqli_fetch_assoc($q_profil) : [];

$q_prodi = mysqli_query($conn, "
    SELECT nama_prodi, jenjang, kode_prodi, status
    FROM prodi
    ORDER BY nama_prodi ASC
");

$jumlah_prodi = 0;
$prodi_aktif = 0;
$daftar_prodi = [];

if ($q_prodi) {
    while ($row = mysqli_fetch_assoc($q_prodi)) {
        $jumlah_prodi++;
        if (($row['status'] ?? '') === 'aktif') {
            $prodi_aktif++;
        }
        $daftar_prodi[] = $row;
    }
}

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
require_once "../includes/navbar.php";
?>

<main class="lg:ml-[270px] min-h-screen bg-slate-50 p-4 sm:p-6 lg:p-8 font-sans">

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-800">Profil Perguruan Tinggi</h2>
            <p class="mt-1 text-sm text-slate-500">
                Informasi resmi kampus, program studi, kontak, dan integrasi NeoFeeder.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <span class="inline-flex items-center rounded-full bg-blue-100 px-4 py-2 text-xs font-semibold text-blue-700">
                <i class="fa-solid fa-building-columns mr-2"></i>
                Perguruan Tinggi Vokasi
            </span>
            <span class="inline-flex items-center rounded-full bg-green-100 px-4 py-2 text-xs font-semibold text-green-700">
                <i class="fa-solid fa-link mr-2"></i>
                NeoFeeder Ready
            </span>
        </div>
    </div>

    <section class="overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-xl">
        <div class="relative bg-gradient-to-br from-blue-950 via-blue-800 to-cyan-700 p-6 sm:p-8 lg:p-10 text-white">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-white"></div>
                <div class="absolute -bottom-24 left-16 h-72 w-72 rounded-full bg-cyan-300"></div>
            </div>

            <div class="relative z-10 grid grid-cols-1 gap-8 xl:grid-cols-[1.4fr_0.8fr] xl:items-end">
                <div>
                    <div class="mb-5 flex flex-wrap gap-2">
                        <span class="inline-flex items-center rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-semibold">
                            Akademi Teknik Informatika Tunas Bangsa Jakarta
                        </span>
                        <span class="inline-flex items-center rounded-full border border-emerald-300/30 bg-emerald-400/15 px-4 py-2 text-xs font-semibold text-emerald-100">
                            Siap Kerja - Siap Wirausaha
                        </span>
                    </div>

                    <div class="mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-blue-800 shadow-lg">
                        <i class="fa-solid fa-building-columns text-3xl"></i>
                    </div>

                    <h1 class="max-w-4xl text-3xl sm:text-4xl lg:text-5xl font-extrabold leading-tight">
                        <?= profil_h(profil_text($profil['nama_perguruan_tinggi'] ?? '', 'Akademi Teknik Informatika Tunas Bangsa Jakarta')); ?>
                    </h1>

                    <p class="mt-5 max-w-4xl text-sm sm:text-base leading-7 text-blue-50">
                        Akademi Teknik Informatika Tunas Bangsa Jakarta merupakan perguruan tinggi vokasi yang berfokus pada bidang teknologi informasi dan akuntansi untuk menciptakan lulusan yang siap bersaing di dunia kerja serta memiliki semangat berwirausaha.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 xl:grid-cols-1">
                    <div class="rounded-2xl border border-white/20 bg-white/10 p-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-wider text-blue-100">Kode PT</p>
                        <p class="mt-2 text-2xl font-extrabold"><?= profil_h(profil_text($profil['kode_perguruan_tinggi'] ?? '034147')); ?></p>
                    </div>

                    <div class="rounded-2xl border border-white/20 bg-white/10 p-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-wider text-blue-100">Program Studi</p>
                        <p class="mt-2 text-2xl font-extrabold"><?= number_format($jumlah_prodi); ?></p>
                        <p class="text-xs text-blue-100"><?= number_format($prodi_aktif); ?> aktif</p>
                    </div>

                    <div class="rounded-2xl border border-white/20 bg-white/10 p-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-wider text-blue-100">Jenjang</p>
                        <p class="mt-2 text-2xl font-extrabold">Diploma 3</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-6 grid grid-cols-1 lg:grid-cols-[1.1fr_0.9fr] gap-6">
        <div class="rounded-2xl border border-slate-100 bg-white p-5 sm:p-6 shadow-lg">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Visi</p>
            <h3 class="mt-2 text-xl font-bold text-slate-900">Visi Akademi Teknik Informatika Tunas Bangsa</h3>
            <p class="mt-4 text-sm sm:text-base leading-7 text-slate-600">
                “Menjadi Lembaga Pendidikan Unggulan Dalam Pengembangan Teknologi Informasi dan Akuntansi Berbasis Inovasi, Kreativitas, dan Integritas Untuk Menghasilkan Lulusan yang Berdaya Saing Global.”
            </p>
        </div>

        <div class="rounded-2xl border border-slate-100 bg-white p-5 sm:p-6 shadow-lg">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Tagline</p>
            <h3 class="mt-2 text-xl font-bold text-slate-900">Siap Kerja - Siap Wirausaha</h3>
            <p class="mt-4 text-sm leading-7 text-slate-600">
                Pembelajaran diarahkan untuk membekali mahasiswa dengan keahlian vokasi, wawasan kewirausahaan, dan kesiapan menghadapi kebutuhan dunia usaha serta dunia industri.
            </p>
        </div>
    </section>

    <section class="mt-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
        <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-lg">
            <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-blue-100 text-blue-700">
                <i class="fa-solid fa-phone"></i>
            </div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Telepon</p>
            <p class="mt-2 text-sm font-semibold text-slate-800"><?= profil_h(profil_text($profil['telepon'] ?? '', '(021) 8490 8754')); ?></p>
        </div>

        <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-lg">
            <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-green-100 text-green-700">
                <i class="fa-solid fa-envelope"></i>
            </div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Email</p>
            <p class="mt-2 break-all text-sm font-semibold text-slate-800"><?= profil_h(profil_text($profil['email'] ?? '', 'info@tunasbangsajakarta.ac.id')); ?></p>
        </div>

        <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-lg">
            <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                <i class="fa-solid fa-globe"></i>
            </div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Website</p>
            <p class="mt-2 break-all text-sm font-semibold text-slate-800"><?= profil_h(profil_text($profil['website'] ?? '', 'www.tunasbangsajakarta.ac.id')); ?></p>
        </div>

        <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-lg">
            <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-purple-100 text-purple-700">
                <i class="fa-solid fa-id-card"></i>
            </div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">ID Feeder</p>
            <p class="mt-2 break-all text-sm font-semibold text-slate-800"><?= profil_h(profil_text($profil['id_feeder'] ?? '')); ?></p>
        </div>
    </section>

    <section class="mt-6 grid grid-cols-1 xl:grid-cols-[1.1fr_0.9fr] gap-6">
        <div class="rounded-2xl border border-slate-100 bg-white p-5 sm:p-6 shadow-lg">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Sambutan Direktur</p>
            <h3 class="mt-2 text-xl font-bold text-slate-900">Perguruan Tinggi Vokasi di Era Industri 4.0</h3>

            <div class="mt-4 space-y-4 text-sm leading-7 text-slate-600">
                <p>
                    Akademi Teknik Informatika Tunas Bangsa Jakarta hadir di tengah masyarakat untuk membantu mencerdaskan kehidupan bangsa serta membina masyarakat dalam mengikuti perkembangan zaman, khususnya di era Revolusi Industri 4.0.
                </p>
                <p>
                    Kampus ini berdiri sejak tahun 2001 dengan jenjang pendidikan Diploma 3 dan membuka dua program studi, yaitu Manajemen Informatika dan Komputerisasi Akuntansi.
                </p>
                <p class="font-semibold text-slate-800">
                    Sa’ad Budiman Lubis, S.Pd.I., M.M — Direktur ATI Tunas Bangsa Jakarta
                </p>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-100 bg-white p-5 sm:p-6 shadow-lg">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Legalitas</p>
            <h3 class="mt-2 text-xl font-bold text-slate-900">Data Resmi Perguruan Tinggi</h3>

            <div class="mt-5 space-y-3">
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">SK Pendirian</p>
                    <p class="mt-1 font-semibold text-slate-800"><?= profil_h(profil_text($profil['sk_pendirian'] ?? '')); ?></p>
                    <p class="mt-1 text-sm text-slate-500"><?= profil_h(profil_tanggal($profil['tanggal_sk_pendirian'] ?? '')); ?></p>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Status Milik</p>
                    <p class="mt-1 font-semibold text-slate-800"><?= profil_h(profil_text($profil['nama_status_milik'] ?? 'Yayasan')); ?></p>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Status Sinkronisasi</p>
                    <p class="mt-1 font-semibold text-slate-800">
                        <?= profil_h(strtoupper(profil_text($profil['status_sync_feeder'] ?? ($profil['status_sinkron'] ?? 'belum')))); ?>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-6 rounded-2xl border border-slate-100 bg-white p-5 sm:p-6 shadow-lg">
        <div class="mb-5">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Program Studi</p>
            <h3 class="mt-2 text-xl font-bold text-slate-900">Pilihan Program Studi</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($daftar_prodi as $prodi): ?>
                <a href="<?= stripos($prodi['nama_prodi'], 'Akuntansi') !== false ? 'profil_ka.php' : 'profil_mi.php'; ?>"
                   class="group rounded-2xl border border-slate-100 bg-slate-50 p-5 transition hover:-translate-y-0.5 hover:border-blue-200 hover:bg-white hover:shadow-md">
                    <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-white text-blue-700 shadow-sm">
                        <i class="fa-solid <?= stripos($prodi['nama_prodi'], 'Akuntansi') !== false ? 'fa-calculator' : 'fa-laptop-code'; ?>"></i>
                    </div>

                    <h4 class="font-bold text-slate-900"><?= profil_h($prodi['nama_prodi']); ?></h4>
                    <p class="mt-2 text-sm text-slate-500">
                        <?= profil_h($prodi['jenjang']); ?> - Kode <?= profil_h($prodi['kode_prodi']); ?>
                    </p>

                    <?php if (stripos($prodi['nama_prodi'], 'Manajemen Informatika') !== false): ?>
                        <p class="mt-3 text-xs font-semibold text-blue-700">
                            Akreditasi “Baik” LAM INFOKOM No. 239/SK/LAM-INFOKOM/Ak.S/D3/VIII/2024
                        </p>
                    <?php elseif (stripos($prodi['nama_prodi'], 'Komputerisasi Akuntansi') !== false): ?>
                        <p class="mt-3 text-xs font-semibold text-blue-700">
                            Akreditasi “Baik” BAN-PT No. 1626/SK/BAN-PT/Akred/Dipl-III/III/2021
                        </p>
                    <?php endif; ?>

                    <span class="mt-4 inline-flex items-center text-sm font-semibold text-blue-700 group-hover:text-blue-900">
                        Lihat profil prodi
                        <i class="fa-solid fa-arrow-right ml-2 text-xs"></i>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="mt-6 grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="rounded-2xl border border-slate-100 bg-white p-5 sm:p-6 shadow-lg">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Lokasi Kampus</p>
            <h3 class="mt-2 text-xl font-bold text-slate-900">Kampus Jakarta Timur</h3>

            <div class="mt-5 rounded-2xl border border-blue-100 bg-blue-50 p-5">
                <p class="font-semibold text-blue-950">
                    Jl. Bambu Apus Raya No.1, Kel. Bambu Apus, Kec. Cipayung, Jakarta Timur - DKI Jakarta 13890
                </p>
                <p class="mt-2 text-sm text-blue-700">(021) 8490 8754</p>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-100 bg-white p-5 sm:p-6 shadow-lg">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Lokasi Kampus</p>
            <h3 class="mt-2 text-xl font-bold text-slate-900">Kampus Jakarta Barat</h3>

            <div class="mt-5 rounded-2xl border border-blue-100 bg-blue-50 p-5">
                <p class="font-semibold text-blue-950">
                    Jl. Raya Duri Kosambi No.3, Kel. Duri Kosambi, Kec. Cengkareng, Jakarta Barat - DKI Jakarta 11750
                </p>
                <p class="mt-2 text-sm text-blue-700">(021) 5336 2328</p>
            </div>
        </div>
    </section>

    <section class="mt-6 rounded-2xl border border-slate-100 bg-white p-5 sm:p-6 shadow-lg">
        <div class="mb-5">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Nilai Utama</p>
            <h3 class="mt-2 text-xl font-bold text-slate-900">Budaya Pendidikan ATITB</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <?php
            $nilai = [
                ['fa-briefcase', 'Profesional', 'Etika bekerja yang baik dan bertanggung jawab.'],
                ['fa-book-open-reader', 'Terdidik', 'Standar pengetahuan yang berkualitas.'],
                ['fa-person-rays', 'Mandiri', 'Mampu menyelesaikan berbagai persoalan.'],
                ['fa-handshake-angle', 'Siap Bekerja', 'Pemikiran matang, disiplin, dan tegas.'],
            ];

            foreach ($nilai as $item):
            ?>
                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-5 transition hover:-translate-y-0.5 hover:bg-white hover:shadow-md">
                    <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-white text-blue-700 shadow-sm">
                        <i class="fa-solid <?= profil_h($item[0]); ?>"></i>
                    </div>

                    <h4 class="font-bold text-slate-900"><?= profil_h($item[1]); ?></h4>
                    <p class="mt-2 text-sm leading-6 text-slate-500"><?= profil_h($item[2]); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

</main>

<?php require_once "../includes/footer.php"; ?>