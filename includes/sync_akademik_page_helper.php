<?php

function sync_count($conn, $sql)
{
    $q = mysqli_query($conn, $sql);
    if (!$q) return 0;
    $row = mysqli_fetch_assoc($q);
    return (int)($row['total'] ?? 0);
}

function render_sync_akademik_page($title, $subtitle, $cards, $rows, $action_name = null)
{
    ?>
    <main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">
        <?php show_alert(); ?>

        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-800"><?= htmlspecialchars($title); ?></h2>
                <p class="text-sm text-slate-500 mt-1"><?= htmlspecialchars($subtitle); ?></p>
            </div>
            <a href="sinkronisasi.php" class="px-4 py-3 rounded-xl bg-slate-700 text-white font-semibold text-center">Kembali</a>
        </div>

        <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
            <?php foreach ($cards as $card): ?>
                <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
                    <p class="text-sm text-slate-500"><?= htmlspecialchars($card['label']); ?></p>
                    <h3 class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$card['value']); ?></h3>
                </div>
            <?php endforeach; ?>
        </section>

        <?php if ($action_name): ?>
            <form method="post" class="mb-6">
                <div class="mb-3">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Batas data per proses</label>
                    <input type="number" name="limit" value="25" min="1" max="200" class="w-32 rounded-xl border border-slate-300 px-4 py-3">
                </div>
                <button name="<?= htmlspecialchars($action_name); ?>" value="1" class="px-5 py-3 rounded-xl bg-indigo-700 hover:bg-indigo-800 text-white font-semibold">
                    <i class="fa-solid fa-arrows-rotate mr-2"></i> Jalankan Sinkronisasi
                </button>
            </form>
        <?php endif; ?>

        <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-4 sm:p-6">
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100 text-slate-700">
                        <tr>
                            <th class="px-4 py-3 text-left">Data</th>
                            <th class="px-4 py-3 text-left">Keterangan</th>
                            <th class="px-4 py-3 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td class="px-4 py-3 font-semibold"><?= htmlspecialchars($row[0]); ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($row[1]); ?></td>
                                <td class="px-4 py-3"><?= $row[2]; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <?php
}

function sync_badge($status)
{
    $class = $status === 'siap' ? 'bg-green-100 text-green-700' : ($status === 'perlu_data' ? 'bg-orange-100 text-orange-700' : 'bg-slate-100 text-slate-700');
    return '<span class="px-3 py-1 rounded-full text-xs font-bold ' . $class . '">' . htmlspecialchars($status) . '</span>';
}
