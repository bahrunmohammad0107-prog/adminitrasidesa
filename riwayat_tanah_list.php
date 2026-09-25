<?php
session_start();
include "../cek_login.php";
include "../koneksi.php";

$desa_id  = intval($_SESSION['desa_id'] ?? 1);
$level    = strtolower(trim($_SESSION['level'] ?? 'admin'));
$id_user  = intval($_SESSION['id_user'] ?? $_SESSION['id'] ?? 0);
$dusun_id = intval($_SESSION['dusun_id'] ?? 0);

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

// Ambil semua daftar transaksi mutasi tanah
$query = mysqli_query($koneksi, "
    SELECT 
        r.*,
        p.nop,
        p.nama_wajib_pajak,
        p.blok_tanah,
        p.luas_tanah as luas_induk,
        d.nama_dusun
    FROM riwayat_tanah r
    JOIN pajak_sppt p ON r.sppt_id = p.id
    LEFT JOIN dusun d ON p.dusun_id = d.id
    $where_akses
    ORDER BY r.tanggal_mutasi DESC, r.id DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Register Transaksi & Mutasi Tanah</title>
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

<div class="container" style="max-width: 1250px;">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1"><i class="fa fa-book-bookmark text-primary me-2"></i>BUKU REGISTER TRANSAKSI PERTANAHAN</h4>
            <small class="text-muted">Menampung seluruh data bidang tanah yang mengalami mutasi peralihan hak (Jual Beli, Hibah, Waris).</small>
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
                        <th>TANGGAL & REGISTER</th>
                        <th>NOP & WP INDUK</th>
                        <th>DARI PIHAK (ASAL)</th>
                        <th>KEPADA (TUJUAN)</th>
                        <th class="text-center">JENIS MUTASI</th>
                        <th class="text-end">LUAS DIALIHKAN</th>
                        <th class="text-center" style="width: 200px;">AKSI CETAK / POHON</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    if (!$query || mysqli_num_rows($query) == 0):
                    ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa fa-folder-open fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                            <b>Belum ada data riwayat transaksi tanah.</b>
                            <div class="small mt-1">Data akan otomatis masuk ke sini ketika Anda menginput mutasi dari tombol <b>Transaksi</b> di menu Data SPPT.</div>
                        </td>
                    </tr>
                    <?php 
                    else:
                        while ($r = mysqli_fetch_assoc($query)): 
                            $badge = 'bg-primary';
                            if ($r['jenis_mutasi'] == 'HIBAH') $badge = 'bg-success';
                            if ($r['jenis_mutasi'] == 'WARIS') $badge = 'bg-warning text-dark';
                    ?>
                    <tr>
                        <td class="text-center text-muted fw-bold"><?= $no++ ?></td>
                        <td>
                            <div class="fw-bold text-dark"><?= date('d/m/Y', strtotime($r['tanggal_mutasi'])) ?></div>
                            <small class="text-muted font-monospace"><?= htmlspecialchars($r['no_registrasi'] ?: '-') ?></small>
                        </td>
                        <td>
                            <span class="font-monospace text-muted small d-block"><?= htmlspecialchars($r['nop']) ?></span>
                            <b><?= strtoupper(htmlspecialchars($r['nama_wajib_pajak'])) ?></b>
                            <small class="text-muted d-block"><?= htmlspecialchars($r['nama_dusun'] ?: '-') ?> (Blok <?= htmlspecialchars($r['blok_tanah'] ?: '-') ?>)</small>
                        </td>
                        <td>
                            <div class="fw-bold text-danger">📤 <?= strtoupper(htmlspecialchars($r['dari_pemilik'])) ?></div>
                            <small class="text-muted">NIK: <?= htmlspecialchars($r['nik_asal'] ?: '-') ?></small>
                        </td>
                        <td>
                            <div class="fw-bold text-success">📥 <?= strtoupper(htmlspecialchars($r['kepada_pemilik'])) ?></div>
                            <small class="text-muted">NIK: <?= htmlspecialchars($r['nik_tujuan'] ?: '-') ?></small>
                        </td>
                        <td class="text-center">
                            <span class="badge <?= $badge ?> rounded-pill px-3"><?= strtoupper($r['jenis_mutasi']) ?></span>
                        </td>
                        <td class="text-end">
                            <b class="text-dark fs-6"><?= number_format($r['luas_mutasi'], 2, ',', '.') ?></b> m²
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <a href="cetak_riwayat.php?id=<?= $r['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" title="Cetak Surat Bukti Transaksi">
                                    <i class="fa fa-print me-1"></i> Surat
                                </a>
                                <a href="riwayat_tanah.php?id=<?= $r['sppt_id'] ?>" class="btn btn-sm btn-dark rounded-pill px-3 fw-bold" title="Lihat Pohon Silsilah">
                                    <i class="fa fa-diagram-project me-1"></i> Pohon
                                </a>
                            </div>
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