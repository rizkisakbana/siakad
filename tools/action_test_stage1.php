<?php

require __DIR__ . '/../config/database.php';

$baseUrl = 'http://localhost/siakad-atitb';
$cookieFile = __DIR__ . '/../database/backups/action_stage1.cookie';
$suffix = date('His');
$results = [];

function http_request(string $method, string $url, ?array $postFields = null): array
{
    global $cookieFile;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_USERAGENT => 'SIAKAD action test stage 1',
    ]);

    if ($postFields !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
    }

    $raw = curl_exec($ch);
    $error = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $headers = is_string($raw) ? substr($raw, 0, $headerSize) : '';
    $body = is_string($raw) ? substr($raw, $headerSize) : '';

    preg_match('/^Location:\s*(.+)$/mi', $headers, $location);

    return [
        'status' => $status,
        'location' => isset($location[1]) ? trim($location[1]) : '',
        'body' => $body,
        'error' => $error,
    ];
}

function add_result(string $name, bool $ok, string $detail = ''): void
{
    global $results;
    $results[] = [$name, $ok ? 'OK' : 'FAIL', $detail];
}

function query_one(string $sql): ?array
{
    global $conn;
    $result = mysqli_query($conn, $sql);
    if (!$result || mysqli_num_rows($result) < 1) {
        return null;
    }
    return mysqli_fetch_assoc($result);
}

function table_count(string $table, string $where): int
{
    global $conn;
    $safeTable = str_replace('`', '', $table);
    $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM `{$safeTable}` WHERE {$where}");
    if (!$result) {
        return -1;
    }
    return (int) mysqli_fetch_assoc($result)['total'];
}

@unlink($cookieFile);

$login = http_request('POST', $baseUrl . '/auth/login_proses.php', [
    'username' => 'admin',
    'password' => 'admin123',
]);
add_result('Login admin action session', $login['status'] === 302 && str_contains($login['location'], 'admin/dashboard.php'), 'HTTP ' . $login['status'] . ' -> ' . $login['location']);

$created = [
    'id_mk' => null,
    'id_kurikulum' => null,
    'id_tahun' => null,
    'id_ruangan' => null,
    'id_prodi' => null,
];

try {
    $kodeProdi = 'ST' . substr($suffix, -4);
    $namaProdi = 'SMOKE TEST PRODI ' . $suffix;
    $response = http_request('POST', $baseUrl . '/admin/prodi/tambah_prodi.php', [
        'kode_prodi' => $kodeProdi,
        'nama_prodi' => $namaProdi,
        'jenjang' => 'D3',
        'gelar' => 'A.Md.',
        'status' => 'aktif',
    ]);
    $prodi = query_one("SELECT id_prodi FROM prodi WHERE kode_prodi = '" . mysqli_real_escape_string($conn, $kodeProdi) . "' LIMIT 1");
    $created['id_prodi'] = $prodi['id_prodi'] ?? null;
    add_result('Tambah prodi test', (bool) $prodi, 'HTTP ' . $response['status'] . ' id=' . ($created['id_prodi'] ?? '-'));

    $kodeRuangan = 'SMK' . substr($suffix, -4);
    $response = http_request('POST', $baseUrl . '/admin/ruangan/tambah_ruangan.php', [
        'kode_ruangan' => $kodeRuangan,
        'nama_ruangan' => 'Ruang Smoke Test ' . $suffix,
        'gedung' => 'Gedung Test',
        'lantai' => '1',
        'kapasitas' => '10',
        'jenis_ruangan' => 'kelas',
        'fasilitas' => 'Smoke test',
        'status' => 'aktif',
    ]);
    $ruangan = query_one("SELECT id_ruangan FROM ruangan WHERE kode_ruangan = '" . mysqli_real_escape_string($conn, $kodeRuangan) . "' LIMIT 1");
    $created['id_ruangan'] = $ruangan['id_ruangan'] ?? null;
    add_result('Tambah ruangan test', (bool) $ruangan, 'HTTP ' . $response['status'] . ' id=' . ($created['id_ruangan'] ?? '-'));

    $tahun = '2098/2099';
    $semester = 'Pendek';
    $response = http_request('POST', $baseUrl . '/admin/tahun_akademik/tambah_tahun.php', [
        'tahun' => $tahun,
        'semester' => $semester,
        'tanggal_mulai' => '2099-01-01',
        'tanggal_selesai' => '2099-02-01',
        'status' => 'nonaktif',
    ]);
    $tahunRow = query_one("SELECT id_tahun FROM tahun_akademik WHERE tahun = '{$tahun}' AND semester = '{$semester}' LIMIT 1");
    $created['id_tahun'] = $tahunRow['id_tahun'] ?? null;
    add_result('Tambah tahun akademik test', (bool) $tahunRow, 'HTTP ' . $response['status'] . ' id=' . ($created['id_tahun'] ?? '-'));

    if ($created['id_prodi']) {
        $namaKurikulum = 'SMOKE TEST KURIKULUM ' . $suffix;
        $response = http_request('POST', $baseUrl . '/admin/kurikulum/tambah_kurikulum.php', [
            'id_prodi' => $created['id_prodi'],
            'nama_kurikulum' => $namaKurikulum,
            'tahun_kurikulum' => '2099',
            'total_sks' => '2',
            'status' => 'aktif',
        ]);
        $kurikulum = query_one("SELECT id_kurikulum FROM kurikulum WHERE id_prodi = '" . (int) $created['id_prodi'] . "' AND nama_kurikulum = '" . mysqli_real_escape_string($conn, $namaKurikulum) . "' LIMIT 1");
        $created['id_kurikulum'] = $kurikulum['id_kurikulum'] ?? null;
        add_result('Tambah kurikulum test', (bool) $kurikulum, 'HTTP ' . $response['status'] . ' id=' . ($created['id_kurikulum'] ?? '-'));
    }

    if ($created['id_kurikulum']) {
        $kodeMk = 'SMK' . substr($suffix, -4);
        $response = http_request('POST', $baseUrl . '/admin/matakuliah/tambah_matakuliah.php', [
            'id_kurikulum' => $created['id_kurikulum'],
            'kode_mk' => $kodeMk,
            'nama_mk' => 'Mata Kuliah Smoke Test ' . $suffix,
            'semester' => '1',
            'sks_teori' => '2',
            'sks_praktik' => '0',
            'jenis_mk' => 'wajib',
            'status' => 'aktif',
        ]);
        $mk = query_one("SELECT id_mk FROM mata_kuliah WHERE id_kurikulum = '" . (int) $created['id_kurikulum'] . "' AND kode_mk = '" . mysqli_real_escape_string($conn, $kodeMk) . "' LIMIT 1");
        $created['id_mk'] = $mk['id_mk'] ?? null;
        add_result('Tambah mata kuliah test', (bool) $mk, 'HTTP ' . $response['status'] . ' id=' . ($created['id_mk'] ?? '-'));
    }

    $beforeUnread = table_count('notifikasi', "id_user = 1 AND status_baca = 'belum'");
    mysqli_query($conn, "
        INSERT INTO notifikasi (id_user, judul, pesan, tipe, link, status_baca)
        VALUES (1, 'SMOKE TEST NOTIF {$suffix}', 'Uji tandai dibaca', 'info', 'data_notifikasi.php', 'belum')
    ");
    $response = http_request('POST', $baseUrl . '/admin/notifikasi/tandai_semua_dibaca.php', [
        'scope' => 'saya',
    ]);
    $afterUnread = table_count('notifikasi', "id_user = 1 AND status_baca = 'belum'");
    add_result('Tandai notifikasi saya dibaca', $response['status'] === 302 && $afterUnread === 0, "HTTP {$response['status']} unread {$beforeUnread}->{$afterUnread}");

    $dashboard = http_request('GET', $baseUrl . '/admin/dashboard.php');
    $logoutGet = http_request('GET', $baseUrl . '/auth/logout.php');
    add_result('Logout GET ditolak aman', $logoutGet['status'] === 302 && str_contains($logoutGet['location'], 'auth/login.php'), 'HTTP ' . $logoutGet['status'] . ' -> ' . $logoutGet['location']);

    $postLogoutWithoutToken = http_request('POST', $baseUrl . '/auth/logout.php', []);
    add_result('Logout POST tanpa token ditolak aman', $postLogoutWithoutToken['status'] === 302 && str_contains($postLogoutWithoutToken['location'], 'auth/login.php?error=session'), 'HTTP ' . $postLogoutWithoutToken['status'] . ' -> ' . $postLogoutWithoutToken['location']);
} finally {
    if ($created['id_mk']) {
        http_request('GET', $baseUrl . '/admin/matakuliah/hapus_matakuliah.php?id=' . (int) $created['id_mk']);
    }
    if ($created['id_kurikulum']) {
        http_request('GET', $baseUrl . '/admin/kurikulum/hapus_kurikulum.php?id=' . (int) $created['id_kurikulum']);
    }
    if ($created['id_tahun']) {
        http_request('GET', $baseUrl . '/admin/tahun_akademik/hapus_tahun.php?id=' . (int) $created['id_tahun']);
    }
    if ($created['id_ruangan']) {
        http_request('GET', $baseUrl . '/admin/ruangan/hapus_ruangan.php?id=' . (int) $created['id_ruangan']);
    }
    if ($created['id_prodi']) {
        http_request('GET', $baseUrl . '/admin/prodi/hapus_prodi.php?id=' . (int) $created['id_prodi']);
    }

    @unlink($cookieFile);
}

$cleanup = [
    'prodi' => table_count('prodi', "kode_prodi LIKE 'ST%' AND nama_prodi LIKE 'SMOKE TEST%'"),
    'ruangan' => table_count('ruangan', "kode_ruangan LIKE 'SMK%' AND nama_ruangan LIKE 'Ruang Smoke Test%'"),
    'tahun_akademik' => table_count('tahun_akademik', "tahun = '2098/2099' AND semester = 'Pendek'"),
    'kurikulum' => table_count('kurikulum', "nama_kurikulum LIKE 'SMOKE TEST KURIKULUM%'"),
    'mata_kuliah' => table_count('mata_kuliah', "nama_mk LIKE 'Mata Kuliah Smoke Test%'"),
];

foreach ($cleanup as $table => $count) {
    add_result("Cleanup {$table}", $count === 0, "remaining={$count}");
}

foreach ($results as [$name, $status, $detail]) {
    echo "{$status}\t{$name}\t{$detail}\n";
}
