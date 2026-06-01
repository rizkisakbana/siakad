<?php

require __DIR__ . '/../config/database.php';

$baseUrl = 'http://localhost/siakad-atitb';
$cookieFile = __DIR__ . '/../database/backups/action_stage2.cookie';
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
        CURLOPT_USERAGENT => 'SIAKAD action test stage 2',
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
    preg_match('/^Location:\s*(.+)$/mi', $headers, $location);
    return [
        'status' => $status,
        'location' => isset($location[1]) ? trim($location[1]) : '',
        'body' => is_string($raw) ? substr($raw, $headerSize) : '',
        'error' => $error,
    ];
}

function add_result(string $name, bool $ok, string $detail = ''): void
{
    global $results;
    $results[] = [$ok ? 'OK' : 'FAIL', $name, $detail];
}

function one(string $sql): ?array
{
    global $conn;
    $result = mysqli_query($conn, $sql);
    if (!$result || mysqli_num_rows($result) < 1) {
        return null;
    }
    return mysqli_fetch_assoc($result);
}

function count_where(string $table, string $where): int
{
    global $conn;
    $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM `{$table}` WHERE {$where}");
    if (!$result) {
        return -1;
    }
    return (int) mysqli_fetch_assoc($result)['total'];
}

@unlink($cookieFile);

$prodi = one("SELECT id_prodi FROM prodi WHERE status='aktif' ORDER BY id_prodi ASC LIMIT 1");
if (!$prodi) {
    add_result('Ambil prodi aktif', false, 'Tidak ada prodi aktif');
    foreach ($results as $r) {
        echo implode("\t", $r) . "\n";
    }
    exit(1);
}
$idProdi = (int) $prodi['id_prodi'];

$login = http_request('POST', $baseUrl . '/auth/login_proses.php', [
    'username' => 'admin',
    'password' => 'admin123',
]);
add_result('Login admin action session', $login['status'] === 302 && str_contains($login['location'], 'admin/dashboard.php'), "HTTP {$login['status']} -> {$login['location']}");

$created = [
    'id_dosen' => null,
    'id_user_dosen' => null,
    'id_mahasiswa' => null,
    'id_user_mahasiswa' => null,
];

try {
    $usernameDosen = 'smoke_dosen_' . $suffix;
    $nidn = '99' . substr($suffix, -8);
    $response = http_request('POST', $baseUrl . '/admin/dosen/tambah_dosen.php', [
        'id_prodi' => $idProdi,
        'nidn' => $nidn,
        'nidk' => '',
        'nuptk' => '',
        'nip' => 'SMOKE' . $suffix,
        'id_dosen_feeder' => '',
        'nama_dosen' => 'SMOKE TEST DOSEN ' . $suffix,
        'gelar_depan' => '',
        'gelar_belakang' => 'S.Kom.',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Jakarta',
        'tanggal_lahir' => '1990-01-01',
        'id_agama_feeder' => '',
        'status_dosen' => 'tetap',
        'id_status_aktif_feeder' => '',
        'id_ikatan_kerja_feeder' => '',
        'email' => '',
        'no_hp' => '',
        'alamat' => 'Smoke test',
        'username' => $usernameDosen,
        'password' => 'SmokeDosen123',
        'status' => 'aktif',
    ]);
    $dosen = one("SELECT id_dosen, id_user FROM dosen WHERE nidn='" . mysqli_real_escape_string($conn, $nidn) . "' LIMIT 1");
    $created['id_dosen'] = $dosen['id_dosen'] ?? null;
    $created['id_user_dosen'] = $dosen['id_user'] ?? null;
    add_result('Tambah dosen + user', (bool) $dosen, "HTTP {$response['status']} id_dosen=" . ($created['id_dosen'] ?? '-'));

    if ($created['id_user_dosen']) {
        @unlink($cookieFile);
        $loginDosen = http_request('POST', $baseUrl . '/auth/login_proses.php', [
            'username' => $usernameDosen,
            'password' => 'SmokeDosen123',
        ]);
        add_result('Login akun dosen baru', $loginDosen['status'] === 302 && str_contains($loginDosen['location'], 'dosen/dashboard.php'), "HTTP {$loginDosen['status']} -> {$loginDosen['location']}");

        @unlink($cookieFile);
        http_request('POST', $baseUrl . '/auth/login_proses.php', ['username' => 'admin', 'password' => 'admin123']);
        $deleteDosen = http_request('GET', $baseUrl . '/admin/dosen/hapus_dosen.php?id=' . (int) $created['id_dosen']);
        $remainingDosen = count_where('dosen', "id_dosen='" . (int) $created['id_dosen'] . "'");
        $remainingUserDosen = count_where('users', "id_user='" . (int) $created['id_user_dosen'] . "'");
        add_result('Hapus dosen + user', $deleteDosen['status'] === 302 && $remainingDosen === 0 && $remainingUserDosen === 0, "HTTP {$deleteDosen['status']} dosen={$remainingDosen} user={$remainingUserDosen}");
    }

    $usernameMahasiswa = 'smoke_mhs_' . $suffix;
    $nim = 'SM' . $suffix;
    $nik = '9999' . substr(str_repeat($suffix, 4), 0, 12);
    http_request('GET', $baseUrl . '/admin/mahasiswa/tambah_mahasiswa.php');
    $response = http_request('POST', $baseUrl . '/admin/mahasiswa/tambah_mahasiswa.php', [
        'id_prodi' => $idProdi,
        'id_kelas' => '',
        'nim' => $nim,
        'nama_mahasiswa' => 'SMOKE TEST MAHASISWA ' . $suffix,
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Jakarta',
        'tanggal_lahir' => '2000-01-01',
        'nik' => $nik,
        'nisn' => '',
        'npwp' => '',
        'alamat' => 'Smoke test',
        'kode_pos' => '',
        'email' => '',
        'no_hp' => '',
        'asal_sekolah' => 'SMK Smoke',
        'tahun_lulus' => '2099',
        'nama_ayah' => 'Ayah Smoke',
        'nama_ibu' => 'Ibu Smoke',
        'angkatan' => '2099',
        'semester' => '1',
        'tanggal_masuk' => '2099-01-01',
        'tanggal_keluar' => '',
        'status' => 'aktif',
        'username' => $usernameMahasiswa,
        'password' => 'SmokeMhs123',
        'id_agama_feeder' => '',
        'id_negara_feeder' => 'ID',
        'id_wilayah_feeder' => '',
        'id_jalur_masuk_feeder' => '',
        'id_jenis_pendaftaran_feeder' => '',
        'id_status_mahasiswa_feeder' => '',
        'id_pekerjaan_ayah_feeder' => '',
        'id_pekerjaan_ibu_feeder' => '',
        'id_penghasilan_ayah_feeder' => '',
        'id_penghasilan_ibu_feeder' => '',
        'id_alat_transportasi_feeder' => '',
    ]);
    $mahasiswa = one("SELECT id_mahasiswa, id_user FROM mahasiswa WHERE nim='" . mysqli_real_escape_string($conn, $nim) . "' LIMIT 1");
    $created['id_mahasiswa'] = $mahasiswa['id_mahasiswa'] ?? null;
    $created['id_user_mahasiswa'] = $mahasiswa['id_user'] ?? null;
    $bodyDetail = '';
    if (!$mahasiswa) {
        file_put_contents(__DIR__ . '/../database/backups/last_mahasiswa_action_fail.html', $response['body']);
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($response['body'])));
        $message = substr($text, 0, 500);
        if (preg_match('/<div class="mb-5[^"]*bg-red[^>]*>.*?<span>(.*?)<\\/span>/si', $response['body'], $matches)) {
            $message = html_entity_decode(strip_tags($matches[1]), ENT_QUOTES, 'UTF-8');
        } elseif (preg_match('/(Gagal[^.]*\\.|gagal[^.]*\\.|wajib[^.]*\\.|sudah digunakan[^.]*\\.|tidak valid[^.]*\\.)/i', $text, $matches)) {
            $message = $matches[1];
        }
        $bodyDetail = ' body=' . $message;
    }
    add_result('Tambah mahasiswa + user', (bool) $mahasiswa, "HTTP {$response['status']} id_mahasiswa=" . ($created['id_mahasiswa'] ?? '-') . $bodyDetail);

    if ($created['id_user_mahasiswa']) {
        @unlink($cookieFile);
        $loginMhs = http_request('POST', $baseUrl . '/auth/login_proses.php', [
            'username' => $usernameMahasiswa,
            'password' => 'SmokeMhs123',
        ]);
        add_result('Login akun mahasiswa baru', $loginMhs['status'] === 302 && str_contains($loginMhs['location'], 'mahasiswa/dashboard.php'), "HTTP {$loginMhs['status']} -> {$loginMhs['location']}");

        @unlink($cookieFile);
        http_request('POST', $baseUrl . '/auth/login_proses.php', ['username' => 'admin', 'password' => 'admin123']);
        $deactivateMhs = http_request('GET', $baseUrl . '/admin/mahasiswa/hapus_mahasiswa.php?id=' . (int) $created['id_mahasiswa']);
        $inactive = one("SELECT m.status AS status_mhs, u.status AS status_user FROM mahasiswa m LEFT JOIN users u ON u.id_user=m.id_user WHERE m.id_mahasiswa='" . (int) $created['id_mahasiswa'] . "' LIMIT 1");
        add_result('Nonaktifkan mahasiswa via hapus_mahasiswa', $deactivateMhs['status'] === 302 && ($inactive['status_mhs'] ?? '') === 'nonaktif' && ($inactive['status_user'] ?? '') === 'nonaktif', "HTTP {$deactivateMhs['status']} status_mhs=" . ($inactive['status_mhs'] ?? '-') . " user=" . ($inactive['status_user'] ?? '-'));
    }
} finally {
    if ($created['id_mahasiswa']) {
        mysqli_query($conn, "DELETE FROM mahasiswa WHERE id_mahasiswa='" . (int) $created['id_mahasiswa'] . "'");
    }
    if ($created['id_user_mahasiswa']) {
        mysqli_query($conn, "DELETE FROM users WHERE id_user='" . (int) $created['id_user_mahasiswa'] . "'");
    }
    if ($created['id_dosen']) {
        mysqli_query($conn, "DELETE FROM dosen WHERE id_dosen='" . (int) $created['id_dosen'] . "'");
    }
    if ($created['id_user_dosen']) {
        mysqli_query($conn, "DELETE FROM users WHERE id_user='" . (int) $created['id_user_dosen'] . "'");
    }
    @unlink($cookieFile);
}

add_result('Cleanup dosen smoke', count_where('dosen', "nama_dosen LIKE 'SMOKE TEST DOSEN%'") === 0, 'remaining=' . count_where('dosen', "nama_dosen LIKE 'SMOKE TEST DOSEN%'"));
add_result('Cleanup mahasiswa smoke', count_where('mahasiswa', "nama_mahasiswa LIKE 'SMOKE TEST MAHASISWA%'") === 0, 'remaining=' . count_where('mahasiswa', "nama_mahasiswa LIKE 'SMOKE TEST MAHASISWA%'"));
add_result('Cleanup user smoke', count_where('users', "username LIKE 'smoke_%'") === 0, 'remaining=' . count_where('users', "username LIKE 'smoke_%'"));

foreach ($results as $result) {
    echo implode("\t", $result) . "\n";
}
