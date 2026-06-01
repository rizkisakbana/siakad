<?php
require_once "../includes/auth.php";
require_once "../config/database.php";
require_once "../includes/helper.php";
require_once "profil_helper.php";

cek_login();

$page_title = "Profil Perguruan Tinggi";
$page_subtitle = "Informasi resmi kampus dan program studi ATITB";

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

<main class="lg:ml-[270px] min-h-screen bg-slate-50 p-4 sm:p-6 lg:p-8">

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-800 sm:text-2xl">Profil Perguruan Tinggi</h2>
            <p class="mt-1 text-sm text-slate-500">
                Informasi resmi kampus, legalitas, kontak, dan program studi.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <span class="inline-flex items-center rounded-full bg-green-100 px-4 py-2 text-xs font-bold text-green-700">
                <i class="fa-solid fa-circle-check mr-2"></i>
                Data Lokal
            </span>
            <span class="inline-flex items-center rounded-full bg-blue-100 px-4 py-2 text-xs font-bold text-blue-700">
                <i class="fa-solid fa-link mr-2"></i>
                NeoFeeder Ready
            </span>
        </div>
    </div>

    <section class="overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-xl">
        <div class="relative bg-gradient-to-br from-blue-950 via-blue-800 to-cyan-700 p-6 text-white sm:p-8 lg:p-10">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute -right-16 -top-16 h-56 w-56 rounded-full bg-white"></div>
                <div class="absolute -bottom-20 left-20 h-64 w-64 rounded-full bg-cyan-300"></div>
            </div>

            <div class="relative z-10 grid grid-cols-1 gap-8 xl:grid-cols-[1.4fr_0.8fr] xl:items-end">
                <div>
                    <div class="mb-5 flex flex-wrap gap-2">
                        <span class="inline-flex items-center rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-bold text-white">
                            Perguruan Tinggi Vokasi
                        </span>
                        <span class="inline-flex items-center rounded-full border border-emerald-300/30 bg-emerald-400/15 px-4 py-2 text-xs font-bold text-emerald-100">
                            Siap Kerja - Siap Wirausaha
                        </span>
                    </div>

                    <div class="mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-blue-800 shadow-lg">
                        <i class="fa-solid fa-building-columns text-3xl"></i>
                    </div>

                    <h1 class="max-w-4xl text-3xl font-black leading-tight sm:text-4xl lg:text-5xl">
                        <?= profil_h(profil_text($profil['nama_perguruan_tinggi'] ?? '', 'Akademi Teknik Informatika Tunas Bangsa')); ?>
                    </h1>

                    <p class="mt-5 max-w-3xl text-sm leading-7 text-blue-50 sm:text-base">
                        Akademi Teknik Informatika Tunas Bangsa Jakarta adalah perguruan tinggi vokasi yang berfokus pada pengembangan teknologi informasi dan akuntansi untuk menghasilkan lulusan yang siap kerja, mandiri, dan berdaya saing.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 xl:grid-cols-1">
                    <div class="rounded-2xl border border-white/20 bg-white/10 p-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-wider text-blue-100">Kode PT</p>
                        <p class="mt-2 text-2xl font-black"><?= profil_h(profil_text($profil['kode_perguruan_tinggi'] ?? '')); ?></p>
                    </div>

                    <div class="rounded-2xl border border-white/20 bg-white/10 p-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-wider text-blue-100">Program Studi</p>
                        <p class="mt-2 text-2xl font-black"><?= number_format($jumlah_prodi); ?></p>
                        <p class="text-xs text-blue-100"><?= number_format($prodi_aktif); ?> aktif</p>
                    </div>

                    <div class="rounded-2xl border border-white/20 bg-white/10 p-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-wider text-blue-100">Status PT</p>
                        <p class="mt-2 text-2xl font-black"><?= profil_h(profil_text($profil['status_perguruan_tinggi'] ?? 'A')); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 p-5 sm:p-6 lg:grid-cols-[0.9fr_1.1fr]">
            <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Visi Kampus</p>
                <p class="mt-3 text-lg font-black leading-7 text-blue-950">
                    Menjadi lembaga pendidikan unggulan dalam pengembangan teknologi informasi dan akuntansi berbasis inovasi, kreativitas, dan integritas.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Orientasi</p>
                    <p class="mt-2 font-bold text-slate-800">Pendidikan vokasi siap kerja</p>
                </div>

                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Bidang</p>
                    <p class="mt-2 font-bold text-slate-800">Teknologi informasi dan akuntansi</p>
                </div>

                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Integrasi</p>
                    <p class="mt-2 font-bold text-slate-800">SIAKAD dan NeoFeeder</p>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-lg">
            <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                <i class="fa-solid fa-id-card"></i>
            </div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">ID Feeder</p>
            <p class="mt-2 break-all text-sm font-bold text-slate-800">
                <?= profil_h(profil_text($profil['id_perguruan_tinggi_feeder'] ?? ($profil['id_feeder'] ?? ''))); ?>
            </p>
        </div>

        <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-lg">
            <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-blue-100 text-blue-700">
                <i class="fa-solid fa-phone"></i>
            </div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Telepon</p>
            <p class="mt-2 text-sm font-bold text-slate-800"><?= profil_h(profil_text($profil['telepon'] ?? '')); ?></p>
        </div>

        <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-lg">
            <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-green-100 text-green-700">
                <i class="fa-solid fa-envelope"></i>
            </div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Email</p>
            <p class="mt-2 break-all text-sm font-bold text-slate-800"><?= profil_h(profil_text($profil['email'] ?? '')); ?></p>
        </div>

        <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-lg">
            <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                <i class="fa-solid fa-globe"></i>
            </div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Website</p>
            <p class="mt-2 break-all text-sm font-bold text-slate-800">
                <?= profil_h(profil_text($profil['website'] ?? 'www.tunasbangsajakarta.ac.id')); ?>
            </p>
        </div>
    </section>

    <section class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-lg sm:p-6">
            <div class="mb-5">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Tentang ATITB</p>
                <h3 class="mt-2 text-xl font-black text-slate-900">Kampus Vokasi Bidang Teknologi Informasi dan Akuntansi</h3>
                <p class="mt-2 text-sm text-slate-500">Informasi profil kampus berbasis data lokal dan data resmi NeoFeeder.</p>
            </div>

            <div class="space-y-4 text-sm leading-7 text-slate-600">
                <p>
                    ATITB berdiri sebagai institusi pendidikan vokasi yang menyiapkan lulusan Diploma 3 untuk kebutuhan dunia kerja, industri, dan kewirausahaan.
                </p>
                <p>
                    Nilai pembelajaran kampus diarahkan pada profesionalitas, pendidikan berkualitas, kemandirian, dan kesiapan bekerja. Semangat ini menjadi dasar pengembangan layanan akademik internal di SIAKAD.
                </p>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-lg sm:p-6">
            <div class="mb-5">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Legalitas</p>
                <h3 class="mt-2 text-xl font-black text-slate-900">Data Resmi Perguruan Tinggi</h3>
            </div>

            <div class="space-y-3">
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">SK Pendirian</p>
                    <p class="mt-1 font-bold text-slate-800"><?= profil_h(profil_text($profil['sk_pendirian'] ?? '')); ?></p>
                    <p class="mt-1 text-sm text-slate-500"><?= profil_h(profil_tanggal($profil['tanggal_sk_pendirian'] ?? '')); ?></p>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Status Milik</p>
                    <p class="mt-1 font-bold text-slate-800"><?= profil_h(profil_text($profil['nama_status_milik'] ?? '')); ?></p>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Status Sinkronisasi</p>
                    <p class="mt-1 font-bold text-slate-800">
                        <?= profil_h(strtoupper(profil_text($profil['status_sync_feeder'] ?? ($profil['status_sinkron'] ?? 'belum')))); ?>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[0.9fr_1.1fr]">
        <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-lg sm:p-6">
            <div class="mb-5">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Alamat</p>
                <h3 class="mt-2 text-xl font-black text-slate-900">Lokasi Perguruan Tinggi</h3>
            </div>

            <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5">
                <p class="font-bold text-blue-950"><?= profil_h(profil_text($profil['jalan'] ?? '')); ?></p>
                <p class="mt-2 text-sm leading-6 text-blue-700">
                    <?= profil_h(profil_text($profil['nama_wilayah'] ?? '')); ?>,
                    Kode Pos <?= profil_h(profil_text($profil['kode_pos'] ?? '')); ?>
                </p>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Wilayah Feeder</p>
                    <p class="mt-2 break-all text-sm font-bold text-slate-800">
                        <?= profil_h(profil_text($profil['id_wilayah_feeder'] ?? ($profil['id_wilayah'] ?? ''))); ?>
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Koordinat</p>
                    <p class="mt-2 break-all text-sm font-bold text-slate-800">
                        <?= profil_h(profil_text($profil['lintang_bujur'] ?? '')); ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-lg sm:p-6">
            <div class="mb-5">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Program Studi</p>
                <h3 class="mt-2 text-xl font-black text-slate-900">Pilihan Pendidikan di ATITB</h3>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <?php foreach ($daftar_prodi as $prodi): ?>
                    <a href="<?= stripos($prodi['nama_prodi'], 'Akuntansi') !== false ? 'profil_ka.php' : 'profil_mi.php'; ?>"
                       class="group rounded-2xl border border-slate-100 bg-slate-50 p-5 transition hover:-translate-y-0.5 hover:border-blue-200 hover:bg-white hover:shadow-md">
                        <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-white text-blue-700 shadow-sm">
                            <i class="fa-solid <?= stripos($prodi['nama_prodi'], 'Akuntansi') !== false ? 'fa-calculator' : 'fa-laptop-code'; ?>"></i>
                        </div>

                        <h4 class="font-black text-slate-900"><?= profil_h($prodi['nama_prodi']); ?></h4>
                        <p class="mt-2 text-sm text-slate-500">
                            <?= profil_h($prodi['jenjang']); ?> - Kode <?= profil_h($prodi['kode_prodi']); ?>
                        </p>

                        <span class="mt-4 inline-flex items-center text-sm font-bold text-blue-700 group-hover:text-blue-900">
                            Lihat profil prodi
                            <i class="fa-solid fa-arrow-right ml-2 text-xs"></i>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="mt-6 rounded-2xl border border-slate-100 bg-white p-5 shadow-lg sm:p-6">
        <div class="mb-5">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Nilai Utama</p>
            <h3 class="mt-2 text-xl font-black text-slate-900">Budaya Pendidikan yang Ditekankan</h3>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <?php
            $nilai = [
                ['fa-briefcase', 'Profesional', 'Etika bekerja yang baik, bertanggung jawab, dan siap masuk dunia industri.'],
                ['fa-book-open-reader', 'Terdidik', 'Standar pengetahuan yang berkualitas dan relevan dengan kebutuhan zaman.'],
                ['fa-person-rays', 'Mandiri', 'Mampu menyelesaikan persoalan dan membangun kapasitas diri.'],
                ['fa-handshake-angle', 'Siap Bekerja', 'Disiplin, matang, dan memiliki orientasi karier yang jelas.'],
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