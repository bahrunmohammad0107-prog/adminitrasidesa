<?php
include 'koneksi.php';

$id = $_GET['id'] ?? 0;

/* data warga */
$w = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT * FROM warga WHERE id='$id'
"));

if(!$w){
    echo "Data tidak ditemukan";
    exit;
}

/* ambil transaksi terakhir */
$b = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT p.*, j.nama_iuran
FROM pembayaran p
LEFT JOIN jenis_iuran j ON p.iuran_id = j.id
WHERE p.warga_id='$id'
ORDER BY p.id DESC
LIMIT 1
"));

/* jika belum ada pembayaran */
if(!$b){
    $b = [
        'nama_iuran' => '-',
        'tanggal' => '-',
        'jumlah_bayar' => 0
    ];
}

/* total bayar */
$total_bayar = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT SUM(jumlah_bayar) AS total
 FROM pembayaran
 WHERE warga_id='$id'"
))['total'] ?? 0;

/* total tagihan */
$total_tagihan = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT SUM(nominal) AS total
 FROM jenis_iuran"
))['total'] ?? 0;

/* sisa */
$sisa = $total_tagihan - $total_bayar;
if($sisa < 0) $sisa = 0;

/* nomor struk */
$no_struk = "INV-" . date("YmdHis") . "-" . $id;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Struk Pembayaran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
    body{
        background:#eee;
    }
    .struk{
        width:380px;
        margin:20px auto;
        background:white;
        padding:20px;
        border:1px dashed #000;
        font-family: Arial;
    }
    .center{
        text-align:center;
    }
    </style>
</head>
<body>

<div class="struk">

    <div class="center">
        <h5>STRUK PEMBAYARAN IURAN</h5>
        <hr>
    </div>

    <p><b>No Struk:</b> <?= $no_struk; ?></p>

    <p><b>Nama:</b> <?= $w['nama']; ?></p>
    <p><b>NIK:</b> <?= $w['nik']; ?></p>
    <p><b>RT:</b> <?= $w['rt']; ?></p>

    <hr>

    <p><b>Jenis Iuran:</b> <?= $b['nama_iuran']; ?></p>
    <p><b>Tanggal:</b> <?= $b['tanggal']; ?></p>

    <hr>

    <p><b>Total Bayar:</b><br>
    Rp <?= number_format($total_bayar,0,',','.'); ?></p>

    <p><b>Total Tagihan:</b><br>
    Rp <?= number_format($total_tagihan,0,',','.'); ?></p>

    <p><b>Sisa:</b><br>
    Rp <?= number_format($sisa,0,',','.'); ?></p>

    <hr>

    <div class="center">
        <small>Terima kasih 🙏</small>
    </div>

    <br>

    <button onclick="window.print()" class="btn btn-primary w-100">
        Cetak Struk
    </button>

    <a href="data_warga.php" class="btn btn-secondary w-100 mt-2">
        Kembali
    </a>

</div>

</body>
</html>