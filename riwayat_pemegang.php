<?php
session_start();
include "../cek_login.php";
include "../koneksi.php";

$desa_id  = intval($_SESSION['desa_id'] ?? 1);
$level    = strtolower(trim($_SESSION['level'] ?? 'admin'));
$id_user  = intval($_SESSION['id_user'] ?? $_SESSION['id'] ?? 0);
$dusun_id = intval($_SESSION['dusun_id'] ?? 0);

// Filter dusun jika Kadus
if ($level === 'kadus' && $dusun_id <= 0 && $id_user > 0) {
    $qu = mysqli_query($koneksi, "SELECT dusun_id FROM users WHERE id = '$id_user' LIMIT 1");
    if ($qu && $ru = mysqli_fetch_assoc($qu)) {
        $dusun_id = intval($ru['dusun_id']);
        $_SESSION['dusun_id'] = $dusun_id;
    }
}

$where_akses = "WHERE p.desa_id = '$desa_id'";
if ($level === 'kadus' && $dusun_id > 0) {
    $where_akses .= " AND p.dusun_id = '$dusun_id'";
}

// Ambil data SPPT yang memiliki riwayat transaksi tanah
$query = mysqli_query($koneksi, "
    SELECT 
        p.id as sppt_id,
        p.nop,
        p.nama_wajib_pajak,
        p.blok_tanah,
        p.luas_tanah,
        d.nama_dusun,
        COUNT(r.id) as total_transaksi,
        MAX(r.tanggal_mutasi) as transaksi_terakhir
    FROM pajak_sppt p
    LEFT JOIN dusun d ON p.dusun_id = d.id
    INNER JOIN riwayat_tanah r ON p.id = r.sppt_id
    $where_akses
    GROUP BY p.id
    ORDER BY transaksi_terakhir DESC, p.id DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat & Silsilah Tanah SPPT</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8fafc; font-family: 'Plus Jakarta Sans', sans-serif; color: #0f172a; }
        .main-card { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05); }
        .table-custom th { background: #0f172a !important; color: #ffffff !important; font-size: 11.5px; font-weight: 700; text-transform: uppercase; padding: 14px; }
        .table-custom td { padding: 14px; font-size: 13px; vertical-align: middle; }
    </style>
</head>
<body class="p-4">

<div class="container" style="max-width: 1200px;">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1"><i class="fa fa-diagram-project text-primary me-2"></i>RIWAYAT PERJALANAN & SILSILAH TANAH</h4>
            <small class="text-muted">Daftar bidang tanah SPPT yang tercatat memiliki riwayat mutasi, jual beli, waris, dan hibah.</small>
        </div>
        <a href="index.php" class="btn btn-outline-dark rounded-pill px-4 fw-bold">
            <i class="fa fa-arrow-left me-1"></i> Dashboard Pajak
        </a>
    </div>

    <div class="main-card p-4">
        <div class="table-responsive rounded-3 border">
            <table class="table table-custom table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 40px;">NO</th>
                        <th>NOP</th>
                        <th>WAJIB PAJAK ASLI</th>
                        <th>DUSUN / BLOK</th>
                        <th>LUAS INDUK</th>
                        <th class="text-center">TRANSAKSI</th>
                        <th>UPDATE TERAKHIR</th>
                        <th class="text-center" style="width: 200px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    if (mysqli_num_rows($query) == 0):
                    ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa fa-folder-open fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                            <b>Belum ada riwayat mutasi tanah.</b>
                            <div class="small mt-1">Buka menu <b>Data SPPT</b> lalu klik tombol <b>Transaksi</b> pada salah satu data SPPT untuk mencatat riwayat.</div>
                        </td>
                    </tr>
                    <?php 
                    else:
                        while ($r = mysqli_fetch_assoc($query)): 
                    ?>
                    <tr>
                        <td class="text-center text-muted fw-bold"><?= $no++ ?></td>
                        <td>
                            <span class="fw-bold text-dark font-monospace"><?= htmlspecialchars($r['nop']) ?></span>
                        </td>
                        <td>
                            <div class="fw-bold text-dark">👤 <?= strtoupper(htmlspecialchars($r['nama_wajib_pajak'])) ?></div>
                        </td>
                        <td>
                            <?= htmlspecialchars($r['nama_dusun'] ?: '-') ?> / <?= htmlspecialchars($r['blok_tanah'] ?: '-') ?>
                        </td>
                        <td><b class="text-success"><?= number_format($r['luas_tanah'], 0, ',', '.') ?> m²</b></td>
                        <td class="text-center">
                            <span class="badge bg-primary rounded-pill px-3"><?= $r['total_transaksi'] ?> Kali Mutasi</span>
                        </td>
                        <td>
                            <small class="text-muted"><?= date('d-m-Y', strtotime($r['transaksi_terakhir'])) ?></small>
                        </td>
                        <td class="text-center">
                            <a href="riwayat_tanah.php?id=<?= $r['sppt_id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold shadow-sm">
                                <i class="fa fa-diagram-project me-1"></i> Buka Pohon & Mutasi
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>