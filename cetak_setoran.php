<?php
session_start();
include "../cek_login.php";
include "../koneksi.php";

$id_setor = intval($_GET['id'] ?? 0);
$desa_id  = intval($_SESSION['desa_id'] ?? 1);

// Ambil data setoran
$q = mysqli_query($koneksi, "
    SELECT s.*, u.nama AS nama_petugas, d.nama_dusun 
    FROM setoran_bendahara s
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN dusun d ON s.dusun_id = d.id
    WHERE s.id = '$id_setor' AND s.desa_id = '$desa_id'
    LIMIT 1
");
$setor = mysqli_fetch_assoc($q);

if (!$setor) {
    die("Data setoran tidak ditemukan.");
}

// Ambil profil desa / kop surat
$kop = mysqli_query($koneksi, "SELECT * FROM profil_desa WHERE desa_id = '$desa_id' LIMIT 1");
$k = mysqli_fetch_assoc($kop);

$nama_desa   = $k['nama_desa'] ?? 'AMBALKLIWONAN';
$kecamatan   = $k['kecamatan'] ?? 'AMBAL';
$kabupaten   = $k['kabupaten'] ?? 'KEBUMEN';
$nama_kades  = $k['nama_kades'] ?? 'KEPALA DESA';
$nama_bendahara = $k['nama_bendahara'] ?? 'BENDAHARA DESA';

// Hitung ulang rincian target & tanggungan wilayah terkait pada saat itu
$dusun_filter = $setor['dusun_id'];
$where_wilayah = "WHERE desa_id = '$desa_id'";
if ($dusun_filter > 0) {
    $where_wilayah .= " AND dusun_id = '$dusun_id'";
}

$qt = mysqli_query($koneksi, "SELECT SUM(pajak_terhitung) AS total FROM pajak_sppt $where_wilayah");
$dt = mysqli_fetch_assoc($qt);
$total_target = floatval($dt['total'] ?? 0);

$total_setor = floatval($setor['total_nominal']);

// Total lunas keseluruhan sampai saat ini di wilayah tersebut
$qs = mysqli_query($koneksi, "SELECT SUM(pajak_terhitung) AS total FROM pajak_sppt $where_wilayah AND status = 'SUDAH BAYAR'");
$ds = mysqli_fetch_assoc($qs);
$total_lunas_kumulatif = floatval($ds['total'] ?? 0);

$sisa_tanggungan = $total_target - $total_lunas_kumulatif;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Berita Acara Serah Terima Setoran PBB</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 13px; color: #000; margin: 0; padding: 20px; }
        .header { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px; }
        .header h3, .header h4 { margin: 2px 0; }
        .content { line-height: 1.6; }
        table.tbl { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table.tbl th, table.tbl td { border: 1px solid #000; padding: 8px; font-size: 12.5px; }
        table.tbl th { background: #f0f0f0; text-align: center; }
        .ttd-container { width: 100%; margin-top: 40px; display: table; page-break-inside: avoid; }
        .ttd-box { display: table-cell; width: 50%; text-align: center; vertical-align: top; }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <h4>PEMERINTAH KABUPATEN <?= strtoupper($kabupaten) ?></h4>
        <h4>KECAMATAN <?= strtoupper($kecamatan) ?></h4>
        <h3>PEMERINTAH DESA <?= strtoupper($nama_desa) ?></h3>
        <p style="font-size: 11px; margin: 2px 0;">BERITA ACARA SERAH TERIMA PENERIMAAN PAJAK PBB</p>
    </div>

    <div class="content">
        <p>Pada hari ini tanggal <b><?= date('d-m-Y', strtotime($setor['tanggal_setor'])) ?></b>, telah dilakukan penyerahan uang penerimaan pembayaran Pajak Bumi dan Bangunan (PBB) dari Petugas Pemungut/Kadus kepada Bendahara Desa dengan rincian sebagai berikut:</p>
        
        <ul>
            <li><b>Nomor Referensi Setoran:</b> <?= htmlspecialchars($setor['no_referensi']) ?></li>
            <li><b>Wilayah Penarikan:</b> <?= htmlspecialchars($setor['nama_dusun'] ?: 'Semua Wilayah Desa') ?></li>
            <li><b>Petugas Penyetor:</b> <?= strtoupper(htmlspecialchars($setor['nama_petugas'] ?: 'Administrator')) ?></li>
        </ul>

        <table class="tbl">
            <tr>
                <th>KETERANGAN REKAPITULASI WILAYAH</th>
                <th style="width: 200px;">NOMINAL (RP)</th>
            </tr>
            <tr>
                <td><b>1. Total Target Pajak Wilayah</b></td>
                <td style="text-align: right;">Rp <?= number_format($total_target, 0, ',', '.') ?></td>
            </tr>
            <tr>
                <td><b>2. Total Dana Lunas Disetorkan (Tahap Ini)</b></td>
                <td style="text-align: right; font-weight: bold; color: #008000;">Rp <?= number_format($total_setor, 0, ',', '.') ?></td>
            </tr>
            <tr>
                <td><b>3. Total Sisa Tanggungan / Belum Lunas</b></td>
                <td style="text-align: right; font-weight: bold; color: #b22222;">Rp <?= number_format($sisa_tanggungan, 0, ',', '.') ?></td>
            </tr>
        </table>

        <p style="margin-top: 15px;">Demikian Berita Acara Serah Terima ini dibuat dengan sebenarnya agar dapat dipergunakan sebagaimana mestinya.</p>
    </div>

    <div class="ttd-container">
        <div class="ttd-box">
            <p>Menyerahkan,<br>Petugas Pemungut / Kadus</p>
            <br><br><br>
            <p><b><u><?= strtoupper(htmlspecialchars($setor['nama_petugas'] ?: '...................................')) ?></u></b></p>
        </div>
        <div class="ttd-box">
            <p>Menerima,<br>Bendahara Desa <?= ucfirst(strtolower($nama_desa)) ?></p>
            <br><br><br>
            <p><b><u><?= strtoupper(htmlspecialchars($nama_bendahara)) ?></u></b></p>
        </div>
    </div>

</body>
</html>