<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/../../includes/helper.php";
require_once __DIR__ . "/../../includes/log_aktivitas.php";
require_once __DIR__ . "/../../includes/internal_module_helper.php";

/** @var mysqli $conn */

cek_login();
cek_role(['super_admin', 'admin_keuangan']);

$page_title = "Verifikasi Pembayaran";
$page_subtitle = "Validasi pembayaran pending dan sinkronkan status tagihan.";

function refresh_status_tagihan_keuangan(mysqli $conn, int $id_tagihan): void
{
    $tagihan = internal_query_one($conn, "
        SELECT total_tagihan
        FROM tagihan_mahasiswa
        WHERE id_tagihan = '$id_tagihan'
        LIMIT 1
    ");

    if (!$tagihan) {
        throw new RuntimeException('Tagihan tidak ditemukan.');
    }

    $total_bayar = internal_sum($conn, "
        SELECT COALESCE(SUM(jumlah_bayar), 0) total
        FROM pembayaran_mahasiswa
        WHERE id_tagihan = '$id_tagihan'
          AND status_pembayaran = 'valid'
    ");

    $total_tagihan = (float)($tagihan['total_tagihan'] ?? 0);
    $status_tagihan = 'belum_bayar';

    if ($total_bayar >= $total_tagihan && $total_tagihan > 0) {
        $status_tagihan = 'lunas';
    } elseif ($total_bayar > 0) {
        $status_tagihan = 'sebagian';
    }

    $total_bayar_sql = mysqli_real_escape_string($conn, (string)$total_bayar);
    $status_sql = mysqli_real_escape_string($conn, $status_tagihan);

    if (!mysqli_query($conn, "
        UPDATE tagihan_mahasiswa
        SET total_bayar = '$total_bayar_sql',
            status_tagihan = '$status_sql'
        WHERE id_tagihan = '$id_tagihan'
    ")) {
        throw new RuntimeException('Gagal memperbarui status tagihan: ' . mysqli_error($conn));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pembayaran = intval($_POST['id_pembayaran'] ?? 0);
    $aksi = $_POST['aksi'] ?? '';
    $catatan = mysqli_real_escape_string($conn, trim($_POST['catatan'] ?? ''));
    $id_user = intval($_SESSION['id_user'] ?? 0);

    $pembayaran = internal_query_one($conn, "
        SELECT *
        FROM pembayaran_mahasiswa
        WHERE id_pembayaran = '$id_pembayaran'
        LIMIT 1
    ");

    if (!$pembayaran) {
        set_alert('error', 'Data pembayaran tidak ditemukan.');
    } elseif (($pembayaran['status_pembayaran'] ?? '') !== 'pending') {
        set_alert('warning', 'Pembayaran ini sudah diproses sebelumnya.');
    } elseif (!in_array($aksi, ['valid', 'ditolak'], true)) {
        set_alert('error', 'Aksi verifikasi tidak valid.');
    } else {
        mysqli_begin_transaction($conn);

        try {
            $id_tagihan = intval($pembayaran['id_tagihan']);
            $status_sql = mysqli_real_escape_string($conn, $aksi);

            if (!mysqli_query($conn, "
                UPDATE pembayaran_mahasiswa
                SET status_pembayaran = '$status_sql',
                    diverifikasi_oleh = '$id_user',
                    tanggal_verifikasi = NOW(),
                    catatan = " . (!empty($catatan) ? "'$catatan'" : "catatan") . "
                WHERE id_pembayaran = '$id_pembayaran'
            ")) {
                throw new RuntimeException('Gagal memperbarui pembayaran: ' . mysqli_error($conn));
            }

            refresh_status_tagihan_keuangan($conn, $id_tagihan);

            simpan_log(
                $conn,
                $id_user,
                ucfirst($aksi) . " pembayaran " . ($pembayaran['nomor_pembayaran'] ?? '-'),
                "Keuangan"
            );

            mysqli_commit($conn);
            set_alert('success', 'Pembayaran berhasil diverifikasi.');
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            set_alert('error', $e->getMessage());
        }
    }

    header('Location: verifikasi_pembayaran.php');
    exit;
}

$cards = [
    ['label' => 'Pending', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM pembayaran_mahasiswa WHERE status_pembayaran = 'pending'"))],
    ['label' => 'Valid Hari Ini', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM pembayaran_mahasiswa WHERE status_pembayaran = 'valid' AND DATE(tanggal_verifikasi) = CURDATE()"))],
    ['label' => 'Ditolak', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM pembayaran_mahasiswa WHERE status_pembayaran = 'ditolak'"))],
    ['label' => 'Nominal Pending', 'value' => rupiah_internal(internal_sum($conn, "SELECT COALESCE(SUM(jumlah_bayar),0) total FROM pembayaran_mahasiswa WHERE status_pembayaran = 'pending'"))],
];

$data_pending = internal_fetch_all($conn, "
    SELECT p.*, t.nomor_tagihan, t.total_tagihan, t.total_bayar, m.nim, m.nama_mahasiswa
    FROM pembayaran_mahasiswa p
    JOIN tagihan_mahasiswa t ON t.id_tagihan = p.id_tagihan
    JOIN mahasiswa m ON m.id_mahasiswa = p.id_mahasiswa
    WHERE p.status_pembayaran = 'pending'
    ORDER BY p.tanggal_bayar ASC, p.id_pembayaran ASC
    LIMIT 50
");

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
require_once __DIR__ . "/../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">
    <?php show_alert(); ?>

    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-800">Verifikasi Pembayaran</h2>
        <p class="text-sm text-slate-500 mt-1">Validasi pembayaran pending dan sinkronkan status tagihan.</p>
    </div>

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
        <?php foreach ($cards as $card): ?>
            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
                <p class="text-sm text-slate-500"><?= htmlspecialchars($card['label']); ?></p>
                <h3 class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$card['value']); ?></h3>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-4 sm:p-6">
        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-100 text-slate-700">
                    <tr>
                        <th class="px-4 py-3 text-left">Pembayaran</th>
                        <th class="px-4 py-3 text-left">Mahasiswa</th>
                        <th class="px-4 py-3 text-left">Tanggal</th>
                        <th class="px-4 py-3 text-left">Jumlah</th>
                        <th class="px-4 py-3 text-left">Metode</th>
                        <th class="px-4 py-3 text-left">Catatan</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!empty($data_pending)): ?>
                        <?php foreach ($data_pending as $row): ?>
                            <tr class="hover:bg-slate-50 align-top">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-800"><?= htmlspecialchars($row['nomor_pembayaran'] ?? '-'); ?></div>
                                    <div class="text-xs text-slate-500"><?= htmlspecialchars($row['nomor_tagihan'] ?? '-'); ?></div>
                                </td>
                                <td class="px-4 py-3"><?= htmlspecialchars(($row['nim'] ?? '-') . ' - ' . ($row['nama_mahasiswa'] ?? '-')); ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars(!empty($row['tanggal_bayar']) ? tanggal_jam_indonesia($row['tanggal_bayar']) : '-'); ?></td>
                                <td class="px-4 py-3 font-semibold"><?= rupiah_internal($row['jumlah_bayar'] ?? 0); ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($row['metode_bayar'] ?? '-'); ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($row['catatan'] ?? '-'); ?></td>
                                <td class="px-4 py-3">
                                    <form method="POST" class="flex flex-col sm:flex-row gap-2 justify-center">
                                        <input type="hidden" name="id_pembayaran" value="<?= (int)$row['id_pembayaran']; ?>">
                                        <input type="text" name="catatan" placeholder="Catatan" class="w-36 rounded-xl border border-slate-300 px-3 py-2 text-xs">
                                        <button name="aksi" value="valid" class="px-3 py-2 rounded-xl bg-green-700 hover:bg-green-800 text-white font-semibold text-xs">Valid</button>
                                        <button name="aksi" value="ditolak" class="px-3 py-2 rounded-xl bg-red-700 hover:bg-red-800 text-white font-semibold text-xs">Tolak</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">Tidak ada pembayaran pending.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
