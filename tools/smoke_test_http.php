<?php

$baseUrl = 'http://localhost/siakad-atitb';
$cookieDir = __DIR__ . '/../database/backups';

$accounts = [
    'admin' => ['username' => 'admin', 'password' => 'admin123'],
    'dosen' => ['username' => 'dosen_tes', 'password' => 'Dosen12345'],
    'mahasiswa' => ['username' => '202610001', 'password' => '123456'],
];

$tests = [
    'public' => [
        ['/auth/login.php', 'Login page'],
        ['/auth/forgot_password.php', 'Forgot password page'],
        ['/profil_pt/profil_pt.php', 'Profil PT public/shared'],
        ['/profil_pt/profil_mi.php', 'Profil MI public/shared'],
        ['/profil_pt/profil_ka.php', 'Profil KA public/shared'],
    ],
    'admin' => [
        ['/admin/dashboard.php', 'Dashboard admin'],
        ['/admin/profil/profil.php', 'Profil user admin'],
        ['/admin/pengguna/data_pengguna.php', 'Data pengguna'],
        ['/admin/pengguna/edit_pengguna.php?id=1', 'Edit pengguna admin'],
        ['/admin/mahasiswa/data_mahasiswa.php', 'Data mahasiswa'],
        ['/admin/dosen/data_dosen.php', 'Data dosen'],
        ['/admin/kurikulum/data_kurikulum.php', 'Data kurikulum'],
        ['/admin/kurikulum/tambah_kurikulum.php', 'Tambah kurikulum'],
        ['/admin/matakuliah/data_matakuliah.php', 'Data mata kuliah'],
        ['/admin/kelas/data_kelas.php', 'Data kelas internal'],
        ['/admin/kelas/tambah_kelas.php', 'Tambah kelas internal'],
        ['/admin/jadwal/data_jadwal.php', 'Data jadwal kuliah'],
        ['/admin/jadwal/tambah_jadwal.php', 'Tambah jadwal kuliah'],
        ['/admin/jadwal/cek_bentrok.php', 'Cek bentrok jadwal'],
        ['/admin/perkuliahan/data_kelas_kuliah.php', 'Data kelas kuliah'],
        ['/admin/akademik/krs_mahasiswa.php', 'KRS mahasiswa'],
        ['/admin/akademik/khs_mahasiswa.php', 'KHS mahasiswa'],
        ['/admin/akademik/akm_mahasiswa.php', 'AKM mahasiswa'],
        ['/admin/akademik/transkrip_mahasiswa.php', 'Transkrip mahasiswa'],
        ['/admin/akademik/lulus_do_mahasiswa.php', 'Lulus DO mahasiswa'],
        ['/admin/akademik/presensi_mahasiswa.php', 'Presensi mahasiswa'],
        ['/admin/laporan/data_laporan.php', 'Dashboard laporan'],
        ['/admin/laporan/laporan_mahasiswa.php', 'Laporan mahasiswa'],
        ['/admin/laporan/laporan_dosen.php', 'Laporan dosen'],
        ['/admin/laporan/laporan_krs.php', 'Laporan KRS'],
        ['/admin/laporan/laporan_nilai.php', 'Laporan nilai'],
        ['/admin/laporan/laporan_presensi.php', 'Laporan presensi'],
        ['/admin/laporan/export_excel.php?jenis=mahasiswa', 'Export Excel laporan'],
        ['/admin/laporan/export_pdf.php?jenis=mahasiswa', 'Export PDF laporan'],
        ['/admin/neofeeder/data_pull.php', 'NeoFeeder data pull'],
        ['/admin/neofeeder/sync_profil_pt.php', 'NeoFeeder profil PT'],
        ['/admin/neofeeder/sync_prodi.php', 'NeoFeeder prodi'],
        ['/admin/neofeeder/sync_periode.php', 'NeoFeeder periode'],
        ['/admin/neofeeder/pull_dosen.php', 'Pull dosen'],
        ['/admin/neofeeder/pull_mahasiswa.php', 'Pull mahasiswa'],
        ['/admin/neofeeder/pull_kelas_kuliah.php', 'Pull kelas kuliah'],
        ['/admin/neofeeder/pull_nilai.php', 'Pull nilai'],
        ['/admin/neofeeder/pull_peserta_kelas.php', 'Pull peserta kelas'],
        ['/admin/neofeeder/pull_dosen_pengajar.php', 'Pull dosen pengajar'],
        ['/admin/neofeeder/pull_akm.php', 'Pull AKM'],
        ['/admin/neofeeder/pull_lulus_do.php', 'Pull lulus DO'],
        ['/admin/neofeeder/pull_transkrip.php', 'Pull transkrip'],
        ['/admin/sinkronisasi/sinkronisasi.php', 'Sinkronisasi utama'],
        ['/admin/sinkronisasi/pengaturan.php', 'Pengaturan sinkronisasi'],
        ['/admin/sinkronisasi/log_sinkronisasi.php', 'Log sinkronisasi'],
        ['/admin/sinkronisasi/sync_dosen.php', 'Sync dosen'],
        ['/admin/sinkronisasi/sync_mahasiswa.php', 'Sync mahasiswa'],
        ['/admin/sinkronisasi/sync_kurikulum.php', 'Sync kurikulum'],
        ['/admin/sinkronisasi/sync_matakuliah.php', 'Sync mata kuliah'],
        ['/admin/sinkronisasi/sync_kelas_kuliah.php', 'Sync kelas kuliah'],
        ['/admin/sinkronisasi/sync_dosen_pengajar.php', 'Sync dosen pengajar'],
        ['/admin/sinkronisasi/sync_peserta_kelas.php', 'Sync peserta kelas'],
        ['/admin/sinkronisasi/sync_nilai.php', 'Sync nilai'],
        ['/admin/sinkronisasi/sync_akm.php', 'Sync AKM'],
        ['/admin/sinkronisasi/sync_lulus_do.php', 'Sync lulus DO'],
        ['/admin/pmb/data_pendaftar.php', 'PMB data pendaftar'],
        ['/admin/pmb/laporan_pmb.php', 'PMB laporan'],
        ['/admin/keuangan/jenis_biaya.php', 'Keuangan jenis biaya'],
        ['/admin/keuangan/tagihan_mahasiswa.php', 'Keuangan tagihan'],
        ['/admin/keuangan/pembayaran.php', 'Keuangan pembayaran'],
        ['/admin/keuangan/verifikasi_pembayaran.php', 'Keuangan verifikasi pembayaran'],
        ['/admin/keuangan/laporan_keuangan.php', 'Keuangan laporan'],
        ['/admin/tugas_akhir/data_ta.php', 'Tugas akhir data'],
        ['/admin/tugas_akhir/pengajuan_ta.php', 'Tugas akhir pengajuan'],
        ['/admin/yudisium/data_yudisium.php', 'Yudisium data'],
        ['/admin/wisuda/data_wisuda.php', 'Wisuda data'],
        ['/admin/notifikasi/data_notifikasi.php', 'Data notifikasi'],
        ['/admin/notifikasi/data_email.php', 'Data email'],
        ['/admin/notifikasi/data_whatsapp.php', 'Data WhatsApp'],
        ['/admin/Log_Aktivitas/data_aktivitas.php', 'Log aktivitas'],
    ],
    'dosen' => [
        ['/dosen/dashboard.php', 'Dashboard dosen'],
        ['/admin/profil/profil.php', 'Profil dosen via shared profil'],
        ['/profil_pt/profil_pt.php', 'Profil PT dosen'],
    ],
    'mahasiswa' => [
        ['/mahasiswa/dashboard.php', 'Dashboard mahasiswa'],
        ['/admin/profil/profil.php', 'Profil mahasiswa via shared profil'],
        ['/profil_pt/profil_pt.php', 'Profil PT mahasiswa'],
    ],
];

function request_url(string $method, string $url, ?string $cookieFile = null, ?array $postFields = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_USERAGENT => 'SIAKAD smoke test',
    ]);

    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }

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

function verdict(array $response): string
{
    if ($response['error'] !== '') {
        return 'FAIL';
    }

    if ($response['status'] >= 500 || str_contains($response['body'], 'Fatal error') || str_contains($response['body'], 'Parse error')) {
        return 'FAIL';
    }

    if ($response['status'] >= 200 && $response['status'] < 400) {
        return 'OK';
    }

    return 'WARN';
}

foreach ($tests['public'] as [$path, $label]) {
    $response = request_url('GET', $baseUrl . $path);
    echo implode("\t", ['public', verdict($response), $response['status'], $path, $label, $response['location'], $response['error']]) . PHP_EOL;
}

foreach ($accounts as $role => $account) {
    $cookieFile = $cookieDir . "/smoke_{$role}.cookie";
    @unlink($cookieFile);

    $login = request_url('POST', $baseUrl . '/auth/login_proses.php', $cookieFile, $account);
    echo implode("\t", [$role, verdict($login), $login['status'], '/auth/login_proses.php', 'Login ' . $role, $login['location'], $login['error']]) . PHP_EOL;

    foreach ($tests[$role] as [$path, $label]) {
        $response = request_url('GET', $baseUrl . $path, $cookieFile);
        echo implode("\t", [$role, verdict($response), $response['status'], $path, $label, $response['location'], $response['error']]) . PHP_EOL;
    }

    @unlink($cookieFile);
}
