<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/neofeeder_helper.php';
require __DIR__ . '/../admin/neofeeder/pull_perkuliahan_inti_helper.php';

$tests = [
    'AKM' => pull_akm_pelaporan($conn, 1, 0),
    'LULUS_DO' => pull_lulus_do_pelaporan($conn, 1, 0),
    'TRANSKRIP' => pull_transkrip_pelaporan($conn, 1, 0),
];

foreach ($tests as $name => $result) {
    echo $name . "\t";
    echo "total={$result['total']}\tinsert={$result['insert']}\tupdate={$result['update']}\tskip={$result['skip']}\tgagal={$result['gagal']}\n";
    foreach (($result['pesan_gagal'] ?? []) as $message) {
        echo "  - " . $message . "\n";
    }
}
