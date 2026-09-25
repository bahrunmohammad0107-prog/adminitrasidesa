<?php
session_start();
include "../cek_login.php";
include "../koneksi.php";

$desa_id  = intval($_SESSION['desa_id'] ?? 1);
$level    = strtolower(trim($_SESSION['level'] ?? 'admin'));
$id_user  = intval($_SESSION['id_user'] ?? $_SESSION['id'] ?? 0);
$dusun_id = intval($_SESSION['dusun_id'] ?? 0);

// Buat tabel setoran jika belum ada
mysqli_query($koneksi, "
    CREATE TABLE IF NOT EXISTS setoran_bendahara (
        id INT AUTO_INCREMENT PRIMARY KEY,
        desa_id INT,
        dusun_id INT,
        user_id INT,
        no_referensi VARCHAR(50),
        tanggal_setor DATE,
        total_nominal DECIMAL(15,2)
    )
");

// Proses Aksi Setor (Simpan Batch)
if (isset($_POST['proses_setor'])) {
    $tanggal_setor = date('Y-m-d');
    $no_referensi  = "STR/" . date('Ymd') . "/" . rand(100, 999);
    
    $q_pending = mysqli_query($koneksi, "
        SELECT id, pajak_terhitung 
        FROM pajak_sppt 
        WHERE status = 'SUDAH BAYAR' 
        AND (setor_status = 0 OR setor_status IS NULL)
    ");
    
    $total_nominal_batch = 0;
    $sppt_ids = [];
    
    while ($row = mysqli_fetch_assoc($q_pending)) {
        $total_nominal_batch += floatval($row['pajak_terhitung']);
        $sppt_ids[] = $row['id'];
    }
    
    if ($total_nominal_batch > 0 && count($sppt_ids) > 0) {
        $q_insert = mysqli_query($koneksi, "
            INSERT INTO setoran_bendahara (desa_id, dusun_id, user_id, no_referensi, tanggal_setor, total_nominal) 
            VALUES ('$desa_id', '$dusun_id', '$id_user', '$no_referensi', '$tanggal_setor', '$total_nominal_batch')
        ");
        
        if ($q_insert) {
            $setoran_id = mysqli_insert_id($koneksi);
            $ids_string = implode(',', $sppt_ids);
            
            mysqli_query($koneksi, "
                UPDATE pajak_sppt 
                SET setor_status = 1, setor_id = '$setoran_id' 
                WHERE id IN ($ids_string)
            ");
            
            echo "<script>alert('Berhasil melakukan penyetoran ke Bendahara!'); window.location='setor_bendahara.php';</script>";
        } else {
            echo "<script>alert('Gagal memproses setoran!');</script>";
        }
    } else {
        echo "<script>alert('Tidak ada dana lunas baru yang bisa disetorkan.');</script>";
    }
}

// HITUNG TOTAL TARGET & KAS LANGSUNG DARI TABEL SPPT
$q_target = mysqli_query($koneksi, "SELECT SUM(pajak_terhitung) AS total FROM pajak_sppt");
$d_target = mysqli_fetch_assoc($q_target);
$total_target_seluruh = floatval($d_target['total'] ?? 0);

$q_sudah = mysqli_query($koneksi, "SELECT SUM(pajak_terhitung) AS total FROM pajak_sppt WHERE status = 'SUDAH BAYAR'");
$d_sudah = mysqli_fetch_assoc($q_sudah);
$total_sudah_bayar = floatval($d_sudah['total'] ?? 0);

$total_sisa_tanggungan = $total_target_seluruh - $total_sudah_bayar;

$q_siap = mysqli_query($koneksi, "SELECT SUM(pajak_terhitung) AS total FROM pajak_sppt WHERE status = 'SUDAH BAYAR' AND (setor_status = 0 OR setor_status IS NULL)");
$d_siap = mysqli_fetch_assoc($q_siap);
$total_bayar_sekarang = floatval($d_siap['total'] ?? 0);

$riwayat_setor = mysqli_query($koneksi, "
    SELECT s.*, u.nama AS nama_petugas, d.nama_dusun 
    FROM setoran_bendahara s
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN dusun d ON s.dusun_id = d.id
    ORDER BY s.id DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setor Pajak ke Bendahara Desa</title>
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
            <h4 class="fw-bold mb-1"><i class="fa fa-hand-holding-dollar text-success me-2"></i>SETOR KAS PAJAK KE BENDAHARA DESA</h4>
            <small class="text-muted">Rekonsiliasi total target, uang lunas siap setor, dan sisa tanggungan wilayah.</small>
        </div>
        <a href="index.php" class="btn btn-outline-dark rounded-pill px-4 fw-bold">
            <i class="fa fa-arrow-left me-1"></i> Dashboard Pajak
        </a>
    </div>

    <!-- Ringkasan Setoran -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card p-3 border-start border-primary border-4 shadow-sm h-100">
                <small class="text-muted fw-bold">TOTAL TARGET (SELURUH SPPT)</small>
                <h5 class="fw-bold mt-1 text-primary">Rp <?= number_format($total_target_seluruh, 0, ',', '.') ?></h5>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 border-start border-success border-4 shadow-sm h-100">
                <small class="text-muted fw-bold">SIAP DISETORKAN (KAS LUNAS)</small>
                <h5 class="fw-bold mt-1 text-success">Rp <?= number_format($total_bayar_sekarang, 0, ',', '.') ?></h5>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 border-start border-danger border-4 shadow-sm h-100">
                <small class="text-muted fw-bold">TOTAL TANGGUNGAN (BELUM BAYAR)</small>
                <h5 class="fw-bold mt-1 text-danger">Rp <?= number_format($total_sisa_tanggungan, 0, ',', '.') ?></h5>
            </div>
        </div>
    </div>

    <!-- Tombol Aksi Eksekusi Setor -->
    <div class="main-card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h6 class="fw-bold mb-1 text-dark">Formulir Penyerahan Berita Acara Kas</h6>
                <p class="text-muted mb-0 small">Klik tombol di samping untuk mengunci kas terkumpul dan membuat berita acara setoran resmi.</p>
            </div>
            <form method="POST" onsubmit="return confirm('Yakin ingin melakukan setoran kas lunas terkumpul ke Bendahara Desa?');">
                <button type="submit" name="proses_setor" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm" <?= ($total_bayar_sekarang <= 0) ? 'disabled' : '' ?>>
                    <i class="fa fa-paper-plane me-1"></i> Proses Setor ke Bendahara
                </button>
            </form>
        </div>
    </div>

    <!-- Tabel Riwayat Setoran -->
    <div class="main-card p-4">
        <h6 class="fw-bold mb-3"><i class="fa fa-history text-primary me-2"></i>Riwayat Berita Acara Setoran</h6>
        <div class="table-responsive rounded-3 border">
            <table class="table table-custom table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 40px;">NO</th>
                        <th>TANGGAL & REFERENSI</th>
                        <th>PETUGAS / KADUS</th>
                        <th class="text-end">NOMINAL DISETORKAN</th>
                        <th class="text-center" style="width: 150px;">AKSI CETAK</th>
                    </tr>
                </thead>
                <tbody>
                    <?>
                    <?php 
                    $no = 1;
                    if (!$riwayat_setor || mysqli_num_rows($riwayat_setor) == 0):
                    ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">Belum ada riwayat setoran kas ke bendahara.</td>
                    </tr>
                    <?php 
                    else:
                        while ($r = mysqli_fetch_assoc($riwayat_setor)):
                    ?>
                    <tr>
                        <td class="text-center text-muted fw-bold"><?= $no++ ?></td>
                        <td>
                            <div class="fw-bold text-dark"><?= date('d/m/Y', strtotime($r['tanggal_setor'])) ?></div>
                            <small class="text-muted font-monospace"><?= htmlspecialchars($r['no_referensi']) ?></small>
                        </td>
                        <td>
                            <div class="fw-bold"><?= strtoupper(htmlspecialchars($r['nama_petugas'] ?: 'Admin')) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($r['nama_dusun'] ?: 'Semua Dusun') ?></small>
                        </td>
                        <td class="text-end fw-bold text-success fs-6">
                            Rp <?= number_format($r['total_nominal'], 0, ',', '.') ?>
                        </td>
                        <td class="text-center">
                            <a href="cetak_setoran.php?id=<?= $r['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">
                                <i class="fa fa-print me-1"></i> Cetak BA
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