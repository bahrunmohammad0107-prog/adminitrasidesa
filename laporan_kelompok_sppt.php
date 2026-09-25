<?php
session_start();
include "../cek_login.php";
include "../koneksi.php";

$desa_id  = intval($_SESSION['desa_id'] ?? 1);
$level    = strtolower(trim($_SESSION['level'] ?? 'kadus'));
$dusun_id = intval($_SESSION['dusun_id'] ?? 0);

$where = "WHERE pajak_sppt.desa_id = '$desa_id' AND pajak_sppt.pemegang_sppt IS NOT NULL AND pajak_sppt.pemegang_sppt != ''";
if ($level === 'kadus' && $dusun_id > 0) {
    $where .= " AND pajak_sppt.dusun_id = '$dusun_id'";
}

$q_rekap = mysqli_query($koneksi, "
    SELECT 
        pemegang_sppt,
        MAX(keterangan_pemegang) AS keterangan_pemegang,
        COUNT(id) AS jumlah_sppt,
        SUM(pajak_terhitung) AS total_tagihan,
        SUM(CASE WHEN status = 'SUDAH BAYAR' THEN pajak_terhitung ELSE 0 END) AS total_lunas,
        SUM(CASE WHEN status = 'BELUM BAYAR' OR status IS NULL OR status = '' THEN pajak_terhitung ELSE 0 END) AS total_sisa
    FROM pajak_sppt
    $where
    GROUP BY pemegang_sppt
    ORDER BY pemegang_sppt ASC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pengelompokan SPPT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <style>
        body { background: white; font-family: 'Segoe UI', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body class="p-4">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <h4 class="fw-bold mb-0">📑 Rekap Tagihan SPPT per Penanggung Jawab (Pemegang)</h4>
            <small class="text-muted">Lengkap dengan rincian SPPT dan ancer-ancer lokasi rumah penanggung jawab.</small>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-success rounded-pill px-4"><i class="fa fa-print"></i> Cetak Lembar Tagihan</button>
            <a href="pengelompokan_sppt.php" class="btn btn-outline-primary rounded-pill px-4">Kelola Kelompok</a>
            <a href="index.php" class="btn btn-dark rounded-pill px-4">Dashboard</a>
        </div>
    </div>

    <div class="text-center mb-4">
        <h4 class="fw-bold mb-1">REKAPITULASI PENAGIHAN SPPT KOLEKTIF</h4>
        <h5>DESA AMBALKLIWONAN</h5>
    </div>

    <table class="table table-bordered align-middle">
        <thead class="table-dark">
            <tr class="text-center">
                <th style="width: 40px;">No</th>
                <th>Penanggung Jawab (Pemegang) & Alamat</th>
                <th>Jumlah</th>
                <th>Rincian SPPT yang Ditagihkan</th>
                <th>Total Tagihan</th>
                <th>Status Bayar</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $grand_total = 0;
            while ($row = mysqli_fetch_assoc($q_rekap)): 
                $grand_total += $row['total_tagihan'];
                $pemegang_safe = mysqli_real_escape_string($koneksi, $row['pemegang_sppt']);
                
                $q_detail = mysqli_query($koneksi, "SELECT nop, nama_wajib_pajak, blok_tanah, pajak_terhitung, status FROM pajak_sppt WHERE pemegang_sppt = '$pemegang_safe' AND desa_id = '$desa_id'");
            ?>
            <tr>
                <td class="text-center fw-bold"><?= $no++ ?></td>
                <td>
                    <div class="fw-bold text-uppercase fs-6 text-primary">👤 <?= htmlspecialchars($row['pemegang_sppt']) ?></div>
                    <?php if (!empty($row['keterangan_pemegang'])): ?>
                        <small class="text-muted d-block mt-1">📍 <i><?= htmlspecialchars($row['keterangan_pemegang']) ?></i></small>
                    <?php endif; ?>
                </td>
                <td class="text-center fw-bold"><?= $row['jumlah_sppt'] ?> Lembar</td>
                <td>
                    <ul class="mb-0 ps-3">
                        <?php while ($d = mysqli_fetch_assoc($q_detail)): ?>
                            <li>
                                <b><?= strtoupper(htmlspecialchars($d['nama_wajib_pajak'])) ?></b> (<?= $d['nop'] ?>) - Rp <?= number_format($d['pajak_terhitung'], 0, ',', '.') ?>
                                <?= ($d['status'] == 'SUDAH BAYAR') ? '<b class="text-success">[LUNAS]</b>' : '<b class="text-danger">[BELUM]</b>' ?>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </td>
                <td class="fw-bold fs-6">Rp <?= number_format($row['total_tagihan'], 0, ',', '.') ?></td>
                <td>
                    <?php if ($row['total_sisa'] == 0): ?>
                        <span class="badge bg-success">SEMUA LUNAS</span>
                    <?php else: ?>
                        <span class="badge bg-danger">SISA: Rp <?= number_format($row['total_sisa'], 0, ',', '.') ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot class="table-light fw-bold">
            <tr>
                <td colspan="4" class="text-end">TOTAL TAGIHAN KESELURUHAN:</td>
                <td colspan="2" class="fs-5 text-primary">Rp <?= number_format($grand_total, 0, ',', '.') ?></td>
            </tr>
        </tfoot>
    </table>
</div>

</body>
</html>