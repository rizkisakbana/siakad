<?php

function internal_count($conn, $sql)
{
    $q = mysqli_query($conn, $sql);
    if (!$q) {
        return 0;
    }

    $row = mysqli_fetch_assoc($q);
    return (int)($row['total'] ?? 0);
}

function internal_sum($conn, $sql)
{
    $q = mysqli_query($conn, $sql);
    if (!$q) {
        return 0;
    }

    $row = mysqli_fetch_assoc($q);
    return (float)($row['total'] ?? 0);
}

function rupiah_internal($value)
{
    return 'Rp ' . number_format((float)$value, 0, ',', '.');
}

function internal_badge($value)
{
    $value = (string)($value ?? '-');
    $map = [
        'aktif' => 'bg-green-100 text-green-700',
        'buka' => 'bg-green-100 text-green-700',
        'lunas' => 'bg-green-100 text-green-700',
        'valid' => 'bg-green-100 text-green-700',
        'selesai' => 'bg-green-100 text-green-700',
        'diterima' => 'bg-green-100 text-green-700',
        'pending' => 'bg-yellow-100 text-yellow-700',
        'draft' => 'bg-slate-100 text-slate-700',
        'belum_bayar' => 'bg-orange-100 text-orange-700',
        'sebagian' => 'bg-blue-100 text-blue-700',
        'ditolak' => 'bg-red-100 text-red-700',
        'dibatalkan' => 'bg-red-100 text-red-700',
        'batal' => 'bg-red-100 text-red-700',
        'nonaktif' => 'bg-red-100 text-red-700',
    ];

    $class = $map[$value] ?? 'bg-slate-100 text-slate-700';
    return '<span class="px-3 py-1 rounded-full text-xs font-bold ' . $class . '">' . htmlspecialchars(str_replace('_', ' ', $value)) . '</span>';
}

function render_internal_page($title, $subtitle, $cards, $columns, $rows, $empty_message = 'Data belum tersedia.')
{
    ?>
    <main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">
        <div class="mb-6">
            <h2 class="text-xl font-bold text-slate-800"><?= htmlspecialchars($title); ?></h2>
            <p class="text-sm text-slate-500 mt-1"><?= htmlspecialchars($subtitle); ?></p>
        </div>

        <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
            <?php foreach ($cards as $card): ?>
                <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
                    <p class="text-sm text-slate-500"><?= htmlspecialchars($card['label']); ?></p>
                    <h3 class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$card['value']); ?></h3>
                    <?php if (!empty($card['note'])): ?>
                        <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars($card['note']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </section>

        <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-4 sm:p-6">
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100 text-slate-700">
                        <tr>
                            <?php foreach ($columns as $column): ?>
                                <th class="px-4 py-3 text-left"><?= htmlspecialchars($column); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (!empty($rows)): ?>
                            <?php foreach ($rows as $row): ?>
                                <tr class="hover:bg-slate-50">
                                    <?php foreach ($row as $cell): ?>
                                        <td class="px-4 py-3"><?= $cell; ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= count($columns); ?>" class="px-4 py-10 text-center text-slate-500">
                                    <?= htmlspecialchars($empty_message); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <?php
}
