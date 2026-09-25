<?php
session_start();

include "../cek_login.php";
include "../koneksi.php";

/* ==========================================
   1. SESSION & IDENTIFIKASI USER LOGIN
========================================== */

$desa_id  = intval($_SESSION['desa_id'] ?? 1);
$level    = strtolower(trim($_SESSION['level'] ?? ''));
$nama     = $_SESSION['nama'] ?? 'Administrator';
$id_user  = intval($_SESSION['id_user'] ?? $_SESSION['id'] ?? 0);
$dusun_id = intval($_SESSION['dusun_id'] ?? 0);
$nama_dusun = '';

// Ambil dusun_id & nama dusun langsung dari database jika login Kadus
if ($level === 'kadus') {
    if ($dusun_id <= 0 && $id_user > 0) {
        $qu = mysqli_query($koneksi, "SELECT dusun_id FROM users WHERE id = '$id_user' LIMIT 1");
        if ($qu && $ru = mysqli_fetch_assoc($qu)) {
            $dusun_id = intval($ru['dusun_id']);
            $_SESSION['dusun_id'] = $dusun_id;
        }
    }

    if ($dusun_id > 0) {
        $qd = mysqli_query($koneksi, "SELECT nama_dusun FROM dusun WHERE id = '$dusun_id' LIMIT 1");
        if ($qd && $rd = mysqli_fetch_assoc($qd)) {
            $nama_dusun = strtoupper(trim($rd['nama_dusun']));
        }
    }
}

/* ==========================================
   2. FILTER HAK AKSES WILAYAH (MURNI DUSUN_ID)
========================================== */

$where = " WHERE pajak_sppt.desa_id = '$desa_id' ";

if ($level === 'kadus') {
    $where .= " AND pajak_sppt.dusun_id = '$dusun_id' ";
}

/* ==========================================
   3. STATISTIK INDIKATOR
========================================== */

// Total SPPT
$q = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM pajak_sppt $where");
$d = mysqli_fetch_assoc($q);
$total_sppt = intval($d['total'] ?? 0);

// Total Pajak Tagihan
$q = mysqli_query($koneksi, "SELECT SUM(pajak_terhitung) AS total FROM pajak_sppt $where");
$d = mysqli_fetch_assoc($q);
$total_pajak = floatval($d['total'] ?? 0);

// SPPT Sudah Bayar
$q = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM pajak_sppt $where AND status = 'SUDAH BAYAR'");
$d = mysqli_fetch_assoc($q);
$sudah = intval($d['total'] ?? 0);

// SPPT Belum Bayar
$q = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM pajak_sppt $where AND (status = 'BELUM BAYAR' OR status IS NULL OR status = '')");
$d = mysqli_fetch_assoc($q);
$belum = intval($d['total'] ?? 0);

// Total Uang Masuk
$q = mysqli_query($koneksi, "SELECT SUM(pajak_terhitung) AS total FROM pajak_sppt $where AND status = 'SUDAH BAYAR'");
$d = mysqli_fetch_assoc($q);
$uang_masuk = floatval($d['total'] ?? 0);

// Jumlah Petugas Kadus
$q = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM users WHERE level = 'kadus' AND desa_id = '$desa_id'");
$d = mysqli_fetch_assoc($q);
$total_petugas = intval($d['total'] ?? 0);

// Progress Persentase
$persen = 0;
if ($total_sppt > 0) {
    $persen = round(($sudah / $total_sppt) * 100);
}

/* ==========================================
   4. GRAFIK PER DUSUN
========================================== */

$label = [];
$data  = [];

$grafik = mysqli_query($koneksi, "
    SELECT
        d.nama_dusun,
        COUNT(p.id) AS jumlah
    FROM dusun d
    LEFT JOIN pajak_sppt p ON d.id = p.dusun_id AND p.desa_id = '$desa_id'
    WHERE d.desa_id = '$desa_id'
    GROUP BY d.id, d.nama_dusun
    ORDER BY d.nama_dusun ASC
");

if ($grafik) {
    while ($g = mysqli_fetch_assoc($grafik)) {
        $label[] = $g['nama_dusun'];
        $data[]  = intval($g['jumlah']);
    }
}

/* ==========================================
   5. DAFTAR PEMBAYARAN TERBARU
========================================== */

$terbaru = mysqli_query($koneksi, "
    SELECT
        nop,
        nama_wajib_pajak,
        pajak_terhitung,
        tanggal_bayar
    FROM pajak_sppt
    $where
    AND status = 'SUDAH BAYAR'
    ORDER BY tanggal_bayar DESC, id DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pajak PBB Desa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            background: #f1f5f9;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #0f172a;
        }

        .header {
            background: linear-gradient(135deg, #1e3a8a, #0f172a);
            color: white;
            padding: 32px 0;
            border-radius: 0 0 24px 24px;
            box-shadow: 0 10px 25px rgba(0,0,0,.1);
        }

        .header h2 { margin: 0; font-weight: 800; font-size: 24px; letter-spacing: -0.5px; }
        .header p { margin: 4px 0 0; opacity: .85; font-size: 13.5px; }

        .stat-card {
            border: none;
            border-radius: 16px;
            color: white;
            overflow: hidden;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            position: relative;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        }

        .stat-card:hover { 
            transform: translateY(-4px); 
            box-shadow: 0 10px 25px rgba(0,0,0,0.12);
        }

        .stat-card i {
            position: absolute;
            right: 18px;
            top: 18px;
            font-size: 44px;
            opacity: .18;
        }

        .bg-blue   { background: linear-gradient(135deg, #2563eb, #1d4ed8); }
        .bg-green  { background: linear-gradient(135deg, #10b981, #059669); }
        .bg-red    { background: linear-gradient(135deg, #ef4444, #b91c1c); }
        .bg-orange { background: linear-gradient(135deg, #f97316, #c2410c); }
        .bg-purple { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }
        .bg-dark   { background: linear-gradient(135deg, #475569, #1e293b); }

        /* KARTU MENU QUICK-CARD ELEGAN & MODERN */
        .quick-card {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 3px 12px rgba(15, 23, 42, 0.04);
            background: #ffffff;
        }

        .quick-card:hover {
            transform: translateY(-4px);
            border-color: #cbd5e1;
            box-shadow: 0 10px 25px -4px rgba(15, 23, 42, 0.12);
        }

        .icon-circle {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin: 0 auto;
        }

        .bg-blue-subtle    { background-color: #eff6ff; color: #2563eb; }
        .bg-warning-subtle { background-color: #fffbeb; color: #d97706; }
        .bg-success-subtle { background-color: #f0fdf4; color: #16a34a; }
        .bg-secondary-subtle { background-color: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }
        .bg-primary-subtle { background-color: #eef2ff; color: #4f46e5; }
        .bg-emerald-subtle { background-color: #ecfdf5; color: #059669; }

        .progress { height: 20px; border-radius: 50px; background-color: #e2e8f0; }
        footer { margin-top: 50px; padding: 25px; text-align: center; color: #64748b; font-size: 12.5px; }
    </style>
</head>

<body>

<div class="header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2>🧾 ADMINISTRASI PAJAK PBB</h2>
                <p>Desa Ambalkliwonan • Kecamatan Ambal • Kabupaten Kebumen</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <div class="d-inline-flex flex-column align-items-md-end">
                    <h6 class="mb-1 fw-bold">
                        <i class="fa fa-user-circle me-1"></i>
                        <?= strtoupper(htmlspecialchars($nama)) ?>
                    </h6>
                    <span class="badge bg-light text-dark fw-bold px-3 py-1 rounded-pill">
                        <?= strtoupper(htmlspecialchars($level)) ?> <?= ($nama_dusun !== '') ? "(Dusun $nama_dusun)" : "" ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container mt-4">

    <!-- TOMBOL NAVIGASI ATAS -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <a href="../dashboard.php" class="btn btn-outline-dark rounded-pill px-4 fw-bold shadow-sm bg-white">
            <i class="fa-solid fa-arrow-left me-1"></i> Dashboard Utama
        </a>

        <?php if ($level === 'admin'): ?>
        <!-- Tombol Cepat Pengaturan Kop (Khusus Admin) -->
        <a href="pengaturan_kop.php" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm text-dark">
            <i class="fa-solid fa-stamp me-1"></i> Pengaturan Kop Surat Desa
        </a>
        <?php endif; ?>
    </div>

    <!-- STATISTIK INDIKATOR UTAMA -->
    <div class="row g-3">
        <div class="col-lg-4 col-md-6">
            <div class="card stat-card bg-blue">
                <div class="card-body p-3">
                    <i class="fa-solid fa-file-invoice"></i>
                    <small class="fw-bold opacity-75">TOTAL SPPT</small>
                    <h3 class="fw-bold mb-0 mt-1"><?= number_format($total_sppt, 0, ',', '.') ?></h3>
                    <small class="opacity-75">Data Terdaftar</small>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="card stat-card bg-orange">
                <div class="card-body p-3">
                    <i class="fa-solid fa-money-bill-wave"></i>
                    <small class="fw-bold opacity-75">TOTAL PAJAK</small>
                    <h3 class="fw-bold mb-0 mt-1">Rp <?= number_format($total_pajak, 0, ',', '.') ?></h3>
                    <small class="opacity-75">Target Tagihan</small>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="card stat-card bg-purple">
                <div class="card-body p-3">
                    <i class="fa-solid fa-wallet"></i>
                    <small class="fw-bold opacity-75">UANG MASUK</small>
                    <h3 class="fw-bold mb-0 mt-1">Rp <?= number_format($uang_masuk, 0, ',', '.') ?></h3>
                    <small class="opacity-75">Sudah Terbayar</small>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="card stat-card bg-green">
                <div class="card-body p-3">
                    <i class="fa-solid fa-circle-check"></i>
                    <small class="fw-bold opacity-75">SUDAH BAYAR</small>
                    <h3 class="fw-bold mb-0 mt-1"><?= number_format($sudah, 0, ',', '.') ?></h3>
                    <small class="opacity-75">SPPT Lunas</small>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="card stat-card bg-red">
                <div class="card-body p-3">
                    <i class="fa-solid fa-clock"></i>
                    <small class="fw-bold opacity-75">BELUM BAYAR</small>
                    <h3 class="fw-bold mb-0 mt-1"><?= number_format($belum, 0, ',', '.') ?></h3>
                    <small class="opacity-75">SPPT Belum Lunas</small>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="card stat-card bg-dark">
                <div class="card-body p-3">
                    <i class="fa-solid fa-users"></i>
                    <small class="fw-bold opacity-75">JUMLAH KADUS</small>
                    <h3 class="fw-bold mb-0 mt-1"><?= number_format($total_petugas, 0, ',', '.') ?></h3>
                    <small class="opacity-75">Petugas Aktif</small>
                </div>
            </div>
        </div>
    </div>

    <!-- PROGRESS PEMBAYARAN -->
    <div class="card mt-4 border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="fa-solid fa-chart-line text-primary me-2"></i>Progress Pelunasan Pajak Desa
                </h6>
                <span class="badge bg-success fw-bold"><?= $persen ?>% Selesai</span>
            </div>
            <div class="progress mt-2">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success fw-bold"
                     style="width:<?= $persen ?>%">
                    <?= $persen ?>%
                </div>
            </div>
            <small class="mt-2 text-muted d-block">
                Tercatat <b><?= $sudah ?></b> dari total <b><?= $total_sppt ?></b> SPPT telah lunas terbayar.
            </small>
        </div>
    </div>

    <!-- QUICK MENU GRID (8 KARTU SIMETRIS 4 x 2) -->
    <div class="row mt-4 g-3">
        
        <!-- 1. DATA SPPT -->
        <div class="col-lg-3 col-md-6 col-6">
            <a href="data_sppt.php" class="text-decoration-none">
                <div class="card quick-card h-100 p-2">
                    <div class="card-body text-center p-3">
                        <div class="icon-circle bg-blue-subtle mb-2">
                            <i class="fa-solid fa-database"></i>
                        </div>
                        <h6 class="fw-bold mb-1 text-dark">Data SPPT</h6>
                        <small class="text-muted d-none d-sm-block" style="font-size: 11.5px;">Kelola Data & Tagihan</small>
                    </div>
                </div>
            </a>
        </div>

        <!-- 2. KELOMPOKKAN -->
        <div class="col-lg-3 col-md-6 col-6">
            <a href="pengelompokan_sppt.php" class="text-decoration-none">
                <div class="card quick-card h-100 p-2">
                    <div class="card-body text-center p-3">
                        <div class="icon-circle bg-warning-subtle mb-2">
                            <i class="fa-solid fa-users-rectangle"></i>
                        </div>
                        <h6 class="fw-bold mb-1 text-dark">Kelompokkan</h6>
                        <small class="text-muted d-none d-sm-block" style="font-size: 11.5px;">1 Pemegang Banyak SPPT</small>
                    </div>
                </div>
            </a>
        </div>

        <!-- 3. LAPORAN KELOMPOK -->
        <div class="col-lg-3 col-md-6 col-6">
            <a href="laporan_kelompok_sppt.php" class="text-decoration-none">
                <div class="card quick-card h-100 p-2">
                    <div class="card-body text-center p-3">
                        <div class="icon-circle bg-success-subtle mb-2">
                            <i class="fa-solid fa-file-invoice-dollar"></i>
                        </div>
                        <h6 class="fw-bold mb-1 text-dark">Lap. Kelompok</h6>
                        <small class="text-muted d-none d-sm-block" style="font-size: 11.5px;">Cetak Tagihan Kolektif</small>
                    </div>
                </div>
            </a>
        </div>

        <!-- 4. RIWAYAT TANAH (MENGARAH KE BUKU REGISTER RIWAYAT) -->
        <div class="col-lg-3 col-md-6 col-6">
            <a href="riwayat_tanah_list.php" class="text-decoration-none">
                <div class="card quick-card h-100 p-2">
                    <div class="card-body text-center p-3">
                        <div class="icon-circle bg-secondary-subtle mb-2">
                            <i class="fa-solid fa-book-bookmark text-primary"></i>
                        </div>
                        <h6 class="fw-bold mb-1 text-dark">Riwayat Tanah</h6>
                        <small class="text-muted d-none d-sm-block" style="font-size: 11.5px;">Buku Register Transaksi</small>
                    </div>
                </div>
            </a>
        </div>

        <!-- 5. PEMBAYARAN -->
        <div class="col-lg-3 col-md-6 col-6">
            <a href="pembayaran.php" class="text-decoration-none">
                <div class="card quick-card h-100 p-2">
                    <div class="card-body text-center p-3">
                        <div class="icon-circle bg-success-subtle mb-2">
                            <i class="fa-solid fa-money-check-dollar"></i>
                        </div>
                        <h6 class="fw-bold mb-1 text-dark">Pembayaran</h6>
                        <small class="text-muted d-none d-sm-block" style="font-size: 11.5px;">Input Pelunasan Warga</small>
                    </div>
                </div>
            </a>
        </div>

        <!-- 6. SETOR BENDAHARA (FITUR BATCH KAS) -->
        <div class="col-lg-3 col-md-6 col-6">
            <a href="setor_bendahara.php" class="text-decoration-none">
                <div class="card quick-card h-100 p-2" style="border-top: 3px solid #10b981 !important;">
                    <div class="card-body text-center p-3">
                        <div class="icon-circle bg-emerald-subtle mb-2">
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                        </div>
                        <h6 class="fw-bold mb-1 text-dark">Setor Bendahara</h6>
                        <small class="text-muted d-none d-sm-block" style="font-size: 11.5px;">Kunci & Serah Kas</small>
                    </div>
                </div>
            </a>
        </div>

        <!-- 7. IMPORT SPPT -->
        <div class="col-lg-3 col-md-6 col-6">
            <a href="import_sppt.php" class="text-decoration-none">
                <div class="card quick-card h-100 p-2">
                    <div class="card-body text-center p-3">
                        <div class="icon-circle bg-primary-subtle mb-2">
                            <i class="fa-solid fa-upload"></i>
                        </div>
                        <h6 class="fw-bold mb-1 text-dark">Import SPPT</h6>
                        <small class="text-muted d-none d-sm-block" style="font-size: 11.5px;">Unggah Excel Desa</small>
                    </div>
                </div>
            </a>
        </div>

        <!-- 8. KOP & PROFIL DESA (KHUSUS ADMIN) -->
        <?php if ($level === 'admin'): ?>
        <div class="col-lg-3 col-md-6 col-6">
            <a href="pengaturan_kop.php" class="text-decoration-none">
                <div class="card quick-card h-100 p-2" style="border-top: 3px solid #f59e0b !important;">
                    <div class="card-body text-center p-3">
                        <div class="icon-circle bg-warning-subtle mb-2">
                            <i class="fa-solid fa-stamp"></i>
                        </div>
                        <h6 class="fw-bold mb-1 text-dark">Kop & Profil Desa</h6>
                        <small class="text-muted d-none d-sm-block" style="font-size: 11.5px;">Logo, Alamat & Kades</small>
                    </div>
                </div>
            </a>
        </div>
        <?php else: ?>
        <!-- Kotak Info Khusus Kadus agar Grid Tetap Simetris (4x2) -->
        <div class="col-lg-3 col-md-6 col-6">
            <div class="card quick-card h-100 p-2 bg-light text-muted">
                <div class="card-body text-center p-3">
                    <div class="icon-circle bg-secondary-subtle mb-2 text-muted">
                        <i class="fa-solid fa-shield"></i>
                    </div>
                    <h6 class="fw-bold mb-1 text-muted">Wilayah Kadus</h6>
                    <small class="text-muted d-none d-sm-block" style="font-size: 11.5px;">Dusun <?= $nama_dusun ?: '-' ?></small>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- GRAFIK & PEMBAYARAN -->
    <div class="row mt-4">
        <div class="col-lg-7 mb-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-4 text-dark">
                        <i class="fa-solid fa-chart-column text-primary me-2"></i>Grafik Jumlah SPPT per Dusun
                    </h6>
                    <canvas id="grafikDusun"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mb-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3 text-dark">
                        <i class="fa-solid fa-clock-rotate-left text-success me-2"></i>Pembayaran Terbaru
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                            <thead class="table-light">
                                <tr>
                                    <th>NOP</th>
                                    <th>NAMA</th>
                                    <th>NOMINAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($terbaru && mysqli_num_rows($terbaru) > 0): ?>
                                    <?php while ($r = mysqli_fetch_assoc($terbaru)): ?>
                                    <tr>
                                        <td class="font-monospace text-muted small"><?= htmlspecialchars($r['nop']) ?></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= strtoupper(htmlspecialchars($r['nama_wajib_pajak'])) ?></div>
                                            <small class="text-muted" style="font-size: 11px;">
                                                <?= date('d-m-Y', strtotime($r['tanggal_bayar'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold px-2 py-1">
                                                Rp <?= number_format($r['pajak_terhitung'], 0, ',', '.') ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">
                                            Belum ada pembayaran lunas.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <hr>
        <strong>Administrasi Pajak PBB Desa Ambalkliwonan</strong><br>
        Kecamatan Ambal • Kabupaten Kebumen<br>
        <small>© <?= date('Y'); ?> Sistem Administrasi Pajak Desa Terpadu</small>
    </footer>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const ctx = document.getElementById('grafikDusun');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($label); ?>,
        datasets: [{
            label: 'Jumlah SPPT',
            data: <?= json_encode($data); ?>,
            borderWidth: 0,
            borderRadius: 8,
            backgroundColor: [
                '#2563eb',
                '#10b981',
                '#f59e0b',
                '#ef4444',
                '#8b5cf6',
                '#06b6d4'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
            x: { grid: { display: false } }
        }
    }
});
</script>

</body>
</html>