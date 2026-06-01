<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/transaksi_pelaporan_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Rebuild Transaksi Pelaporan";
$page_subtitle = "Membangun ulang data KRS, nilai, KHS, AKM, status mahasiswa, lulus/DO, prestasi, dan MBKM";

$hasil = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $hasil = rebuild_transaksi_pelaporan($conn);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

require_once "../../includes/header.php";
require_once "../../includes/navbar.php";
require_once "../../includes/sidebar.php";
?>

<main class="content">
    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 mb-1"><?= htmlspecialchars($page_title) ?></h1>
                <p class="text-muted mb-0"><?= htmlspecialchars($page_subtitle) ?></p>
            </div>
            <a href="sinkronisasi.php" class="btn btn-outline-secondary">Kembali</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <strong>Rebuild gagal.</strong><br>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($hasil): ?>
            <div class="alert alert-success">
                Transaksi pelaporan berhasil dibangun ulang.
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Perubahan Data</h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm mb-0">
                                <tbody>
                                <?php foreach ($hasil['affected'] as $nama_tabel => $jumlah): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($nama_tabel) ?></td>
                                        <td class="text-end"><?= number_format((int)$jumlah) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Total Data Saat Ini</h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm mb-0">
                                <tbody>
                                <?php foreach ($hasil['total'] as $nama_tabel => $jumlah): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($nama_tabel) ?></td>
                                        <td class="text-end"><?= number_format((int)$jumlah) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Bangun Transaksi Pelaporan</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Proses ini memperbarui relasi pelaporan <code>KRS</code>, <code>nilai</code>,
                    menghitung ulang <code>KHS</code>, membentuk <code>AKM</code>, mencatat
                    riwayat status mahasiswa, dan menyiapkan data <code>mahasiswa_lulus_do</code>.
                    Data prestasi dan MBKM disiapkan strukturnya, lalu akan terisi saat modul internalnya dibuat.
                </p>
                <form method="post">
                    <button type="submit" class="btn btn-primary">Jalankan Rebuild</button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once "../../includes/footer.php"; ?>
