<?php

session_start();

include "../cek_login.php";
include "../koneksi.php";

$desa_id = intval($_SESSION['desa_id'] ?? 1);
$id      = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    die("ID transaksi tidak valid.");
}

function e($text) { return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8'); }
function luas($nilai) { return number_format(floatval($nilai), 2, ',', '.'); }

// Ambil Transaksi
$stmt = mysqli_prepare($koneksi, "SELECT * FROM riwayat_tanah WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$row) die("Data transaksi tidak ditemukan.");

$sppt_id = intval($row['sppt_id']);

// Proses Simpan Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis_mutasi   = trim($_POST['jenis_mutasi'] ?? '');
    $tanggal_mutasi = trim($_POST['tanggal_mutasi'] ?? '');
    $keterangan     = trim($_POST['keterangan'] ?? '');

    $stmt = mysqli_prepare($koneksi, "UPDATE riwayat_tanah SET jenis_mutasi = ?, tanggal_mutasi = ?, keterangan = ? WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "sssi", $jenis_mutasi, $tanggal_mutasi, $keterangan, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: riwayat_tanah.php?id=" . $sppt_id . "&success=" . urlencode("Data transaksi berhasil diperbarui."));
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Transaksi Riwayat</title>
    <style>
        body { font-family: "Segoe UI", Arial, sans-serif; background: #eef3f7; margin: 0; padding: 25px 15px; color: #0f172a; }
        .box { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
        h2 { margin-top: 0; color: #065f46; font-size: 20px; }
        .group { margin-bottom: 15px; display: flex; flex-direction: column; gap: 6px; }
        label { font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; }
        input, select, textarea { padding: 11px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-family: inherit; font-size: 14px; }
        .readonly-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px; border-radius: 10px; font-weight: 800; color: #1e293b; font-size: 13px; }
        .btn-save { background: #059669; color: #fff; border: none; padding: 12px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; width: 100%; font-size: 14px; margin-top: 10px; }
        .btn-back { display: block; text-align: center; margin-top: 12px; color: #64748b; text-decoration: none; font-weight: 700; font-size: 13px; }
    </style>
</head>
<body>
<div class="box">
    <h2>✏️ Edit Catatan Transaksi</h2>
    
    <div class="group">
        <label>Pihak Transaksi (Permanen)</label>
        <div class="readonly-box">
            👤 Dari: <?= e(strtoupper($row['dari_pemilik'])) ?> ➔ Kepada: <?= e(strtoupper($row['kepada_pemilik'])) ?> (<?= luas($row['luas_mutasi']) ?> m²)
        </div>
    </div>

    <form method="POST">
        <div class="group">
            <label>Jenis Peralihan</label>
            <select name="jenis_mutasi" required>
                <option value="JUAL BELI" <?= $row['jenis_mutasi'] == 'JUAL BELI' ? 'selected' : '' ?>>JUAL BELI</option>
                <option value="HIBAH" <?= $row['jenis_mutasi'] == 'HIBAH' ? 'selected' : '' ?>>HIBAH</option>
                <option value="WARIS" <?= $row['jenis_mutasi'] == 'WARIS' ? 'selected' : '' ?>>WARIS</option>
                <option value="PEMBAGIAN" <?= $row['jenis_mutasi'] == 'PEMBAGIAN' ? 'selected' : '' ?>>PEMBAGIAN HAK</option>
                <option value="PELEPASAN HAK" <?= $row['jenis_mutasi'] == 'PELEPASAN HAK' ? 'selected' : '' ?>>PELEPASAN HAK</option>
            </select>
        </div>

        <div class="group">
            <label>Tanggal Transaksi</label>
            <input type="date" name="tanggal_mutasi" value="<?= e($row['tanggal_mutasi']) ?>" required>
        </div>

        <div class="group">
            <label>Keterangan Tambahan</label>
            <textarea name="keterangan" rows="3"><?= e($row['keterangan']) ?></textarea>
        </div>

        <button type="submit" class="btn-save">💾 Simpan Perubahan</button>
        <a href="riwayat_tanah.php?id=<?= $sppt_id ?>" class="btn-back">↩️ Batal dan Kembali</a>
    </form>
</div>
</body>
</html>