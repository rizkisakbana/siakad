<?php

require __DIR__ . '/../config/database.php';

$baseUrl = 'http://localhost/siakad-atitb';
$cookieFile = __DIR__ . '/../database/backups/action_stage3.cookie';
$suffix = date('His');
$results = [];

function req3(string $method, string $url, ?array $post = null): array
{
    global $cookieFile;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_USERAGENT => 'SIAKAD action test stage 3',
    ]);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
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

function result3(string $name, bool $ok, string $detail = ''): void
{
    global $results;
    $results[] = [$ok ? 'OK' : 'FAIL', $name, $detail];
}

function one3(string $sql): ?array
{
    global $conn;
    $res = mysqli_query($conn, $sql);
    if (!$res || mysqli_num_rows($res) < 1) {
        return null;
    }
    return mysqli_fetch_assoc($res);
}

function count3(string $table, string $where): int
{
    global $conn;
    $res = mysqli_query($conn, "SELECT COUNT(*) total FROM `{$table}` WHERE {$where}");
    if (!$res) {
        return -1;
    }
    return (int) mysqli_fetch_assoc($res)['total'];
}

@unlink($cookieFile);

$base = one3("
    SELECT p.id_prodi, ta.id_tahun, mk.id_mk, d.id_dosen
    FROM prodi p
    JOIN kurikulum k ON k.id_prodi = p.id_prodi AND k.status='aktif'
    JOIN mata_kuliah mk ON mk.id_kurikulum = k.id_kurikulum AND mk.status='aktif'
    JOIN dosen d ON d.id_prodi = p.id_prodi AND d.status='aktif'
    JOIN tahun_akademik ta ON ta.status IN ('aktif','nonaktif')
    WHERE p.status='aktif'
    ORDER BY p.id_prodi, ta.status ASC, ta.tahun DESC
    LIMIT 1
");

if (!$base) {
    result3('Ambil data dasar jadwal', false, 'Butuh prodi, tahun, mata kuliah, dan dosen aktif');
    foreach ($results as $row) echo implode("\t", $row) . "\n";
    exit(1);
}

$login = req3('POST', $baseUrl . '/auth/login_proses.php', ['username' => 'admin', 'password' => 'admin123']);
result3('Login admin action session', $login['status'] === 302, "HTTP {$login['status']} -> {$login['location']}");

$created = ['id_ruangan' => null, 'id_kelas' => null, 'id_jadwal' => null, 'id_kelas_kuliah' => null];

try {
    req3('GET', $baseUrl . '/admin/ruangan/tambah_ruangan.php');
    $kodeRuangan = 'AJ' . substr($suffix, -4);
    $r = req3('POST', $baseUrl . '/admin/ruangan/tambah_ruangan.php', [
        'kode_ruangan' => $kodeRuangan,
        'nama_ruangan' => 'Ruang Action Jadwal ' . $suffix,
        'gedung' => 'Gedung Test',
        'lantai' => '1',
        'kapasitas' => '40',
        'jenis_ruangan' => 'kelas',
        'fasilitas' => 'Action test',
        'status' => 'aktif',
    ]);
    $ruangan = one3("SELECT id_ruangan FROM ruangan WHERE kode_ruangan='" . mysqli_real_escape_string($conn, $kodeRuangan) . "' LIMIT 1");
    $created['id_ruangan'] = $ruangan['id_ruangan'] ?? null;
    result3('Tambah ruangan untuk jadwal', (bool) $ruangan, "HTTP {$r['status']} id=" . ($created['id_ruangan'] ?? '-'));

    req3('GET', $baseUrl . '/admin/kelas/tambah_kelas.php');
    $kodeKelas = 'AKJ' . substr($suffix, -4);
    $r = req3('POST', $baseUrl . '/admin/kelas/tambah_kelas.php', [
        'id_prodi' => $base['id_prodi'],
        'id_tahun' => $base['id_tahun'],
        'kode_kelas' => $kodeKelas,
        'nama_kelas' => 'Action Test Kelas ' . $suffix,
        'angkatan' => '2099',
        'semester' => '1',
        'kapasitas' => '40',
        'status' => 'aktif',
    ]);
    $kelas = one3("SELECT id_kelas FROM kelas WHERE kode_kelas='" . mysqli_real_escape_string($conn, $kodeKelas) . "' LIMIT 1");
    $created['id_kelas'] = $kelas['id_kelas'] ?? null;
    result3('Tambah kelas internal', (bool) $kelas, "HTTP {$r['status']} id=" . ($created['id_kelas'] ?? '-'));

    if ($created['id_kelas'] && $created['id_ruangan']) {
        req3('GET', $baseUrl . '/admin/jadwal/tambah_jadwal.php');
        $r = req3('POST', $baseUrl . '/admin/jadwal/tambah_jadwal.php', [
            'id_tahun' => $base['id_tahun'],
            'id_kelas' => $created['id_kelas'],
            'id_mk' => $base['id_mk'],
            'id_dosen' => $base['id_dosen'],
            'id_ruangan' => $created['id_ruangan'],
            'hari' => 'Minggu',
            'jam_mulai' => '21:00',
            'jam_selesai' => '21:50',
            'metode' => 'tatap muka',
            'status' => 'aktif',
        ]);
        $jadwal = one3("SELECT id_jadwal, id_kelas_kuliah FROM jadwal_kuliah WHERE id_kelas='" . (int) $created['id_kelas'] . "' AND id_ruangan='" . (int) $created['id_ruangan'] . "' LIMIT 1");
        $created['id_jadwal'] = $jadwal['id_jadwal'] ?? null;
        $created['id_kelas_kuliah'] = $jadwal['id_kelas_kuliah'] ?? null;
        result3('Tambah jadwal kuliah', (bool) $jadwal, "HTTP {$r['status']} id_jadwal=" . ($created['id_jadwal'] ?? '-') . ' id_kelas_kuliah=' . ($created['id_kelas_kuliah'] ?? '-'));

        $kk = $created['id_jadwal'] ? one3("SELECT id_kelas_kuliah FROM kelas_kuliah WHERE id_jadwal='" . (int) $created['id_jadwal'] . "' LIMIT 1") : null;
        $dpk = $kk ? one3("SELECT id_dosen_pengajar FROM dosen_pengajar_kelas WHERE id_kelas_kuliah='" . (int) $kk['id_kelas_kuliah'] . "' LIMIT 1") : null;
        result3('Rebuild kelas_kuliah otomatis', (bool) $kk, 'id=' . ($kk['id_kelas_kuliah'] ?? '-'));
        result3('Rebuild dosen_pengajar otomatis', (bool) $dpk, 'id=' . ($dpk['id_dosen_pengajar'] ?? '-'));
    }
} finally {
    if ($created['id_jadwal']) {
        req3('GET', $baseUrl . '/admin/jadwal/hapus_jadwal.php?id=' . (int) $created['id_jadwal']);
    }
    if ($created['id_kelas']) {
        req3('GET', $baseUrl . '/admin/kelas/hapus_kelas.php?id=' . (int) $created['id_kelas']);
    }
    if ($created['id_ruangan']) {
        req3('GET', $baseUrl . '/admin/ruangan/hapus_ruangan.php?id=' . (int) $created['id_ruangan']);
    }
    @unlink($cookieFile);
}

result3('Cleanup jadwal action', count3('jadwal_kuliah', "id_kelas='" . (int) ($created['id_kelas'] ?? 0) . "'") === 0, 'remaining=' . count3('jadwal_kuliah', "id_kelas='" . (int) ($created['id_kelas'] ?? 0) . "'"));
result3('Cleanup kelas action', count3('kelas', "kode_kelas LIKE 'AKJ%' AND nama_kelas LIKE 'Action Test Kelas%'") === 0, 'remaining=' . count3('kelas', "kode_kelas LIKE 'AKJ%' AND nama_kelas LIKE 'Action Test Kelas%'"));
result3('Cleanup ruangan action', count3('ruangan', "kode_ruangan LIKE 'AJ%' AND nama_ruangan LIKE 'Ruang Action Jadwal%'") === 0, 'remaining=' . count3('ruangan', "kode_ruangan LIKE 'AJ%' AND nama_ruangan LIKE 'Ruang Action Jadwal%'"));

foreach ($results as $row) {
    echo implode("\t", $row) . "\n";
}
