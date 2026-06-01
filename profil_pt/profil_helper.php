<?php
function profil_h($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function profil_text($value, $default = '-')
{
    $value = trim((string) ($value ?? ''));
    return $value !== '' ? $value : $default;
}

function profil_tanggal($value)
{
    $value = trim((string) ($value ?? ''));

    if ($value === '' || $value === '0000-00-00') {
        return '-';
    }

    return function_exists('tanggal_indonesia') ? tanggal_indonesia($value) : date('d-m-Y', strtotime($value));
}

function profil_badge($text, $class = 'bg-blue-50 text-blue-700 border-blue-100')
{
    return '<span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-bold ' . $class . '">' . profil_h($text) . '</span>';
}

function profil_info_card($icon, $label, $value, $color = 'blue')
{
    $colors = [
        'blue' => 'bg-blue-50 text-blue-700 border-blue-100',
        'green' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
        'amber' => 'bg-amber-50 text-amber-700 border-amber-100',
        'rose' => 'bg-rose-50 text-rose-700 border-rose-100',
        'slate' => 'bg-slate-50 text-slate-700 border-slate-100',
    ];

    $class = $colors[$color] ?? $colors['blue'];
    ?>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
        <div class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-xl border <?= $class; ?>">
            <i class="fa-solid <?= profil_h($icon); ?>"></i>
        </div>
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400"><?= profil_h($label); ?></p>
        <p class="mt-2 break-words text-base font-bold text-slate-800"><?= profil_h(profil_text($value)); ?></p>
    </div>
    <?php
}

function profil_section_title($eyebrow, $title, $description = '')
{
    ?>
    <div class="mb-6">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700"><?= profil_h($eyebrow); ?></p>
        <h3 class="mt-2 text-2xl font-black text-slate-900"><?= profil_h($title); ?></h3>
        <?php if ($description !== ''): ?>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500"><?= profil_h($description); ?></p>
        <?php endif; ?>
    </div>
    <?php
}

function profil_stat($label, $value, $hint = '')
{
    ?>
    <div class="rounded-2xl border border-white/25 bg-white/15 p-4 backdrop-blur">
        <p class="text-xs font-semibold uppercase tracking-wider text-white/70"><?= profil_h($label); ?></p>
        <p class="mt-2 text-2xl font-black text-white"><?= profil_h($value); ?></p>
        <?php if ($hint !== ''): ?>
            <p class="mt-1 text-xs text-white/70"><?= profil_h($hint); ?></p>
        <?php endif; ?>
    </div>
    <?php
}

function get_prodi_by_keyword($conn, $keyword)
{
    $keyword = mysqli_real_escape_string($conn, $keyword);
    $q = mysqli_query($conn, "
        SELECT *
        FROM prodi
        WHERE nama_prodi LIKE '%$keyword%'
        ORDER BY id_prodi ASC
        LIMIT 1
    ");

    return ($q && mysqli_num_rows($q) > 0) ? mysqli_fetch_assoc($q) : null;
}

function render_prodi_profile($prodi, $config)
{
    $nama = $prodi['nama_prodi'] ?? $config['nama'];
    $jenjang = profil_text($prodi['jenjang'] ?? $config['jenjang'], 'D3');
    $kode = profil_text($prodi['kode_prodi'] ?? '', '-');
    $id_feeder = profil_text($prodi['id_prodi_feeder'] ?? ($prodi['id_feeder'] ?? ''), '-');
    $status = profil_text($prodi['status'] ?? '', 'aktif');
    ?>
    <main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">
        <section class="overflow-hidden rounded-2xl bg-white shadow-lg border border-slate-100">
            <div class="bg-gradient-to-r from-blue-900 via-blue-800 to-cyan-800 p-6 sm:p-8 lg:p-10 text-white">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-4xl">
                        <div class="mb-5 flex flex-wrap gap-2">
                            <?= profil_badge('Program Studi ' . $jenjang, 'bg-white/10 text-white border-white/25'); ?>
                            <?= profil_badge('Kode Prodi ' . $kode, 'bg-emerald-400/15 text-emerald-100 border-emerald-300/25'); ?>
                        </div>
                        <div class="mb-5 inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-blue-800 shadow-lg">
                            <i class="fa-solid <?= profil_h($config['icon']); ?> text-3xl"></i>
                        </div>
                        <h1 class="text-3xl font-black leading-tight sm:text-4xl lg:text-5xl">
                            <?= profil_h($nama); ?>
                        </h1>
                        <p class="mt-5 max-w-3xl text-base leading-7 text-blue-50">
                            <?= profil_h($config['intro']); ?>
                        </p>
                    </div>

                    <div class="grid w-full gap-3 sm:grid-cols-3 lg:max-w-xl">
                        <?php profil_stat('Jenjang', $jenjang, 'Program vokasi'); ?>
                        <?php profil_stat('Akreditasi', $config['akreditasi'], $config['lembaga']); ?>
                        <?php profil_stat('Status', ucfirst($status), 'Data lokal'); ?>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 p-5 sm:p-6 lg:grid-cols-4">
                <?php foreach ($config['kompetensi'] as $item): ?>
                    <div class="flex gap-3 rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <div class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                            <i class="fa-solid fa-check text-sm"></i>
                        </div>
                        <p class="text-sm font-semibold leading-6 text-slate-700"><?= profil_h($item); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="mt-8 grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
            <?php profil_info_card('fa-hashtag', 'Kode Program Studi', $kode, 'blue'); ?>
            <?php profil_info_card('fa-layer-group', 'Jenjang Pendidikan', $jenjang, 'green'); ?>
            <?php profil_info_card('fa-database', 'ID Prodi Feeder', $id_feeder, 'slate'); ?>
            <?php profil_info_card('fa-circle-check', 'Status Data', ucfirst($status), 'amber'); ?>
        </section>

        <section class="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-[1fr_0.9fr]">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <?php profil_section_title('Profil Akademik', 'Arah Pembelajaran', 'Ringkasan ini disiapkan untuk informasi internal SIAKAD dan mengacu pada identitas program studi yang tersimpan di database.'); ?>
                <div class="space-y-4 text-sm leading-7 text-slate-600">
                    <p><?= profil_h($config['deskripsi']); ?></p>
                    <p><?= profil_h($config['prospek']); ?></p>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <?php profil_section_title('Akreditasi', 'Status Mutu Program Studi'); ?>
                <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5">
                    <p class="text-sm font-bold text-blue-800"><?= profil_h($config['akreditasi_text']); ?></p>
                    <p class="mt-3 text-sm leading-6 text-blue-700">
                        Informasi akreditasi ini ditampilkan sebagai profil publik internal. Pembaruan resmi tetap mengikuti data terbaru dari kampus dan PDDikti.
                    </p>
                </div>
            </div>
        </section>

        <section class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <?php profil_section_title('Nilai Pembelajaran', 'Karakter Lulusan yang Dibangun'); ?>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <?php foreach ($config['nilai'] as $item): ?>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-5 transition hover:-translate-y-0.5 hover:bg-white hover:shadow-md">
                        <div class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-white text-blue-700 shadow-sm">
                            <i class="fa-solid <?= profil_h($item[0]); ?>"></i>
                        </div>
                        <h4 class="font-bold text-slate-900"><?= profil_h($item[1]); ?></h4>
                        <p class="mt-2 text-sm leading-6 text-slate-500"><?= profil_h($item[2]); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
    <?php
}
?>
