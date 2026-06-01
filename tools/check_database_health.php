<?php

require __DIR__ . '/../config/database.php';

$databaseResult = mysqli_query($conn, 'SELECT DATABASE() AS db');
$database = $databaseResult ? mysqli_fetch_assoc($databaseResult)['db'] : '(unknown)';

echo "DB={$database}\n";

$tables = [
    'users',
    'roles',
    'dosen',
    'mahasiswa',
    'prodi',
    'profil_pt',
    'tahun_akademik',
    'notifikasi',
    'password_reset_tokens',
];

foreach ($tables as $table) {
    $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM `{$table}`");
    if (!$result) {
        echo "{$table}: ERROR " . mysqli_error($conn) . "\n";
        continue;
    }

    echo "{$table}: " . mysqli_fetch_assoc($result)['total'] . "\n";
}

$admin = mysqli_query(
    $conn,
    "SELECT u.id_user, u.username, u.status, r.nama_role
     FROM users u
     JOIN roles r ON r.id_role = u.id_role
     WHERE u.username = 'admin'
     LIMIT 1"
);

if ($admin && mysqli_num_rows($admin) === 1) {
    $row = mysqli_fetch_assoc($admin);
    echo "admin_login: {$row['username']} / {$row['nama_role']} / {$row['status']}\n";
} else {
    echo "admin_login: NOT_FOUND\n";
}
