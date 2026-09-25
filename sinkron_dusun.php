<?php
session_start();
include "../cek_login.php";
include "../koneksi.php";

$desa_id = intval($_SESSION['desa_id'] ?? 1);

// 1. Ambil semua dusun di desa ini
$q_dusun = mysqli_query($koneksi, "SELECT id, nama_dusun FROM dusun WHERE desa_id = '$desa_id'");
$daftar_dusun = [];
while ($d = mysqli_fetch_assoc($q_dusun)) {
    $daftar_dusun[] = $d;
}

$laporan = [];
$total_terupdate = 0;

foreach ($daftar_dusun as $ds) {
    $dusun_id   = intval($ds['id']);
    $nama_dusun = strtoupper(trim($ds['nama_dusun']));
    
    // Siapkan kata kunci pencarian yang fleksibel (misal: BENDAN <-> BENDHAN)
    $keywords = [$nama_dusun];
    if (stripos($nama_dusun, 'BENDAN') !== false) {
        $keywords[] = str_ireplace('BENDAN', 'BENDHAN', $nama_dusun);
    } elseif (stripos($nama_dusun, 'BENDHAN') !== false) {
        $keywords[] = str_ireplace('BENDHAN', 'BENDAN', $nama_dusun);
    }
    
    $where_kondisi = [];
    foreach ($keywords as $kw) {
        $kw_safe = mysqli_real_escape_string($koneksi, $kw);
        $where_kondisi[] = "alamat_objek_pajak LIKE '%$kw_safe%'";
        $where_kondisi[] = "alamat_wajib_pajak LIKE '%$kw_safe%'";
        $where_kondisi[] = "blok_tanah LIKE '%$kw_safe%'";
    }
    
    // Perbaikan: Hapus dusun_id = '' karena tipe data dusun_id adalah angka
    $sql_cek = "WHERE desa_id = $desa_id AND (dusun_id IS NULL OR dusun_id = 0) AND (" . implode(" OR ", $where_kondisi) . ")";
    
    // Hitung berapa data yang cocok
    $cek = mysqli_query($koneksi, "SELECT COUNT(*) as jml FROM pajak_sppt $sql_cek");
    $jml = 0;
    if ($cek && $r_cek = mysqli_fetch_assoc($cek)) {
        $jml = intval($r_cek['jml']);
    }
    
    if ($jml > 0) {
        // Update dusun_id di pajak_sppt
        mysqli_query($koneksi, "UPDATE pajak_sppt SET dusun_id = $dusun_id $sql_cek");
        $total_terupdate += $jml;
    }
    
    // Hitung total data final di dusun ini
    $cek_total = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pajak_sppt WHERE desa_id = $desa_id AND dusun_id = $dusun_id");
    $total_final = 0;
    if ($cek_total && $r_tot = mysqli_fetch_assoc($cek_total)) {
        $total_final = intval($r_tot['total']);
    }
    
    $laporan[] = [
        'nama'        => $nama_dusun,
        'terupdate'   => $jml,
        'total_final' => $total_final
    ];
}

// Cek jika masih ada SPPT yang belum memiliki dusun sama sekali
$cek_sisa = mysqli_query($koneksi, "SELECT COUNT(*) as sisa FROM pajak_sppt WHERE desa_id = $desa_id AND (dusun_id IS NULL OR dusun_id = 0)");
$sisa_tanpa_dusun = 0;
if ($cek_sisa && $r_sisa = mysqli_fetch_assoc($cek_sisa)) {
    $sisa_tanpa_dusun = intval($r_sisa['sisa']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sinkronisasi Wilayah Dusun</title>
    <style>
        body { font-family: "Segoe UI", Arial, sans-serif; background: #eef2ff; margin: 0; padding: 30px; }
        .box { max-width: 700px; margin: auto; background: white; border-radius: 20px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        h2 { margin-top: 0; color: #1e3a8a; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px 15px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { background: #f8fafc; color: #475569; font-size: 12px; text-transform: uppercase; }
        .badge-sukses { background: #dcfce7; color: #15803d; padding: 4px 10px; border-radius: 20px; font-weight: bold; font-size: 12px; }
        .btn-kembali { display: inline-block; background: #2563eb; color: white; padding: 12px 24px; border-radius: 12px; text-decoration: none; font-weight: bold; margin-top: 15px; }
    </style>
</head>
<body>

<div class="box">
    <h2>🔄 Sinkronisasi Wilayah SPPT Dusun Selesai</h2>
    <p>Sistem telah menautkan data SPPT dengan master Dusun masing-masing secara otomatis.</p>
    
    <table>
        <thead>
            <tr>
                <th>Nama Dusun</th>
                <th>Data Baru Tersinkron</th>
                <th>Total Data SPPT di Dusun</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($laporan as $lp): ?>
            <tr>
                <td><b>DUSUN <?= htmlspecialchars($lp['nama']) ?></b></td>
                <td><span class="badge-sukses">+ <?= $lp['terupdate'] ?> Data</span></td>
                <td><b><?= $lp['total_final'] ?> SPPT</b></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($sisa_tanpa_dusun > 0): ?>
        <p style="color: #dc2626; font-size: 13px;">
            ⚠️ Terdapat <b><?= $sisa_tanpa_dusun ?> SPPT</b> yang teks alamatnya tidak mencantumkan nama dusun manapun.
        </p>
    <?php else: ?>
        <p style="color: #15803d; font-size: 13px; font-weight: bold;">
            ✅ Semua data SPPT berhasil 100% tersambung ke dusun masing-masing.
        </p>
    <?php endif; ?>

    <a href="data_sppt.php" class="btn-kembali">➡️ Buka Data SPPT (Cek Hasil)</a>
</div>

</body>
</html>