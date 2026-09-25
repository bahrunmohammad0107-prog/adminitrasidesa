<?php

session_start();

include "../cek_login.php";
include "../koneksi.php";
require "../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\IOFactory;

$desa_id  = intval($_SESSION['desa_id'] ?? 1);
$level    = strtolower(trim($_SESSION['level'] ?? 'kadus'));
$dusun_id = intval($_SESSION['dusun_id'] ?? 0);

$baru   = 0;
$update = 0;
$gagal  = 0;
$pesan  = "";

function angka_bersih($nilai)
{
    if ($nilai === null || $nilai === "") return 0;
    $nilai = (string)$nilai;
    $nilai = str_replace(['Rp', 'rp', ' ', "\xc2\xa0", '.', ','], '', $nilai);
    return is_numeric($nilai) ? floatval($nilai) : 0;
}

function bersihkan_nop($nop)
{
    $nop = trim((string)$nop);
    if ($nop === "") return "";
    $nop = preg_replace('/\.0+$/', '', $nop);
    $nop = preg_replace('/[^0-9]/', '', $nop);
    return $nop;
}

if (isset($_POST['import'])) {

    if (!isset($_FILES['file_excel']) || $_FILES['file_excel']['error'] != 0) {
        $pesan = "File Excel belum dipilih.";
    } else {
        $file = $_FILES['file_excel']['tmp_name'];

        try {
            $spreadsheet = IOFactory::load($file);
            $sheet       = $spreadsheet->getActiveSheet();
            $data        = $sheet->toArray(null, true, true, false);

            foreach ($data as $i => $row) {
                if ($i == 0) continue; // Lewati header

                $nop          = bersihkan_nop($row[1] ?? '');
                $nama         = trim($row[2] ?? '');
                $alamat_wp    = trim($row[3] ?? '');
                $alamat_objek = trim($row[4] ?? '');

                if (isset($row[8])) {
                    $blok_tanah    = trim($row[5] ?? '');
                    $luas_tanah    = $row[6] ?? 0;
                    $luas_bangunan = $row[7] ?? 0;
                    $pajak         = $row[8] ?? 0;
                } else {
                    $blok_tanah    = '';
                    $luas_tanah    = $row[5] ?? 0;
                    $luas_bangunan = $row[6] ?? 0;
                    $pajak         = $row[7] ?? 0;
                }

                if ($nop == "" && $nama == "") continue;
                if (strtoupper(trim($nama)) == "JUMLAH") continue;
                if ($nop == "" || $nama == "") {
                    $gagal++;
                    continue;
                }

                $luas_tanah    = angka_bersih($luas_tanah);
                $luas_bangunan = angka_bersih($luas_bangunan);
                $pajak         = angka_bersih($pajak);

                $nop_safe          = mysqli_real_escape_string($koneksi, $nop);
                $nama_safe         = mysqli_real_escape_string($koneksi, $nama);
                $alamat_wp_safe    = mysqli_real_escape_string($koneksi, $alamat_wp);
                $alamat_objek_safe = mysqli_real_escape_string($koneksi, $alamat_objek);
                $blok_tanah_safe   = mysqli_real_escape_string($koneksi, $blok_tanah);

                // KUNCI UTAMA: Jika Kadus unggah, otomatis 100% masuk ke dusunnya
                $target_dusun = ($level === 'kadus' && $dusun_id > 0) ? $dusun_id : intval($_POST['pilih_dusun'] ?? 0);

                // Cek NOP
                $cek = mysqli_query($koneksi, "SELECT id FROM pajak_sppt WHERE nop = '$nop_safe' AND desa_id = '$desa_id' LIMIT 1");

                if ($cek && mysqli_num_rows($cek) > 0) {
                    // Update data pokok SPPT tanpa merusak riwayat transaksi
                    $update_dusun_sql = ($target_dusun > 0) ? ", dusun_id = '$target_dusun'" : "";
                    $query_update = mysqli_query(
                        $koneksi,
                        "UPDATE pajak_sppt SET
                            nama_wajib_pajak   = '$nama_safe',
                            alamat_wajib_pajak = '$alamat_wp_safe',
                            alamat_objek_pajak = '$alamat_objek_safe',
                            blok_tanah         = '$blok_tanah_safe',
                            luas_tanah         = '$luas_tanah',
                            luas_bangunan      = '$luas_bangunan',
                            pajak_terhitung    = '$pajak'
                            $update_dusun_sql
                        WHERE nop = '$nop_safe' AND desa_id = '$desa_id'"
                    );

                    if ($query_update) $update++; else $gagal++;
                } else {
                    // Insert baru
                    $result_urut = mysqli_query($koneksi, "SELECT MAX(no_urut) AS max_urut FROM pajak_sppt WHERE desa_id = '$desa_id'");
                    $data_urut   = mysqli_fetch_assoc($result_urut);
                    $no_urut     = intval($data_urut['max_urut'] ?? 0) + 1;

                    $query_insert = mysqli_query(
                        $koneksi,
                        "INSERT INTO pajak_sppt
                        (
                            desa_id, dusun_id, no_urut, nop, nama_wajib_pajak,
                            alamat_wajib_pajak, alamat_objek_pajak, blok_tanah,
                            luas_tanah, luas_bangunan, pajak_terhitung, status, created_at
                        )
                        VALUES
                        (
                            '$desa_id', '$target_dusun', '$no_urut', '$nop_safe', '$nama_safe',
                            '$alamat_wp_safe', '$alamat_objek_safe', '$blok_tanah_safe',
                            '$luas_tanah', '$luas_bangunan', '$pajak', 'BELUM BAYAR', NOW()
                        )"
                    );

                    if ($query_insert) $baru++; else $gagal++;
                }
            }

            $pesan = "Unggah Berhasil! Data Baru: $baru | Diperbarui: $update | Gagal: $gagal";

        } catch (Exception $e) {
            $pesan = "Error: " . $e->getMessage();
        }
    }
}

// Ambil daftar dusun untuk pilihan Admin
$list_dusun = [];
if ($level === 'admin') {
    $q_d = mysqli_query($koneksi, "SELECT id, nama_dusun FROM dusun WHERE desa_id = '$desa_id' ORDER BY nama_dusun ASC");
    while ($rd = mysqli_fetch_assoc($q_d)) {
        $list_dusun[] = $rd;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Import SPPT PBB</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 30px; font-family: "Segoe UI", Arial, sans-serif; background: #f1f5f9; color: #0f172a; }
        .container { max-width: 800px; margin: auto; }
        .card { background: white; padding: 35px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,.08); }
        .header { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
        .badge { background: #dbeafe; color: #1e40af; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        input[type=file], select { width: 100%; padding: 14px; border-radius: 12px; border: 1px solid #cbd5e1; margin-bottom: 15px; font-size: 14px; }
        button { width: 100%; padding: 16px; border: none; border-radius: 12px; background: #2563eb; color: white; font-size: 16px; font-weight: bold; cursor: pointer; }
        button:hover { background: #1d4ed8; }
        .stat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 25px; text-align: center; }
        .stat-box { padding: 20px; border-radius: 14px; font-weight: bold; }
        .stat-box span { display: block; font-size: 30px; margin-top: 5px; }
        .bg-green { background: #dcfce7; color: #15803d; }
        .bg-blue { background: #dbeafe; color: #1e40af; }
        .bg-red { background: #fee2e2; color: #b91c1c; }
        .alert { padding: 15px; border-radius: 12px; background: #ecfdf5; color: #15803d; font-weight: bold; margin-top: 20px; }
        .btn-back { display: inline-block; margin-top: 20px; text-decoration: none; background: #334155; color: white; padding: 12px 20px; border-radius: 10px; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="header">
            <div style="font-size: 40px;">📥</div>
            <div>
                <h2 style="margin:0;">Unggah Data SPPT PBB</h2>
                <div class="badge">Akun: <?= strtoupper($level) ?></div>
            </div>
        </div>

        <form method="post" enctype="multipart/form-data">
            <?php if ($level === 'admin'): ?>
                <label style="font-weight:bold; display:block; margin-bottom:6px;">Target Dusun untuk File Ini:</label>
                <select name="pilih_dusun" required>
                    <option value="">-- Pilih Wilayah Dusun --</option>
                    <?php foreach ($list_dusun as $d): ?>
                        <option value="<?= $d['id'] ?>">Dusun <?= htmlspecialchars($d['nama_dusun']) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <input type="file" name="file_excel" accept=".xlsx,.xls" required>
            <button type="submit" name="import">📤 UNGGAH & TERAPKAN DATA</button>
        </form>

        <?php if ($pesan != ""): ?>
            <div class="alert"><?= htmlspecialchars($pesan) ?></div>
        <?php endif; ?>

        <div class="stat-grid">
            <div class="stat-box bg-green">DATA BARU<span><?= $baru ?></span></div>
            <div class="stat-box bg-blue">DIPERBARUI<span><?= $update ?></span></div>
            <div class="stat-box bg-red">GAGAL<span><?= $gagal ?></span></div>
        </div>

        <a href="data_sppt.php" class="btn-back">⬅ Buka Data SPPT</a>
    </div>
</div>

</body>
</html>