<?php
include 'koneksi.php';

$warga_id = $_GET['warga_id'] ?? 0;

$warga = mysqli_fetch_assoc(mysqli_query($koneksi,"
SELECT *
FROM warga
WHERE id='$warga_id'
"));

if(!$warga){
    die("Data warga tidak ditemukan");
}

$transaksi = mysqli_query($koneksi,"
SELECT
    p.*,
    j.nama_iuran
FROM pembayaran p
LEFT JOIN jenis_iuran j
ON p.jenis_iuran_id = j.id
WHERE p.warga_id='$warga_id'
ORDER BY p.tanggal_bayar ASC
");

$nomor_bukti = "INV-".date('Ymd')."-".str_pad($warga_id,4,'0',STR_PAD_LEFT);

$total = 0;
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Bukti Pembayaran</title>

<style>

@page{
    size:A4 portrait;
    margin:5mm;
}

body{
    background:#dcdcdc;
    font-family:"Courier New", monospace;
    margin:0;
    padding:20px;
}

.struk{
    width:170mm;
    min-height:260mm;
    background:white;
    margin:auto;
    padding:15px;
    box-sizing:border-box;
    box-shadow:0 0 15px rgba(0,0,0,.15);
}

.toolbar{
    margin-bottom:20px;
}

.btn{
    display:inline-block;
    text-decoration:none;
    padding:12px 18px;
    border-radius:6px;
    color:white;
    font-weight:bold;
}

.cetak{
    background:#2563eb;
}

.kembali{
    background:#64748b;
}

.center{
    text-align:center;
}

h1{
    margin:0;
    font-size:32px;
    letter-spacing:2px;
}

h3{
    margin:5px 0;
}

.garis{
    border-top:2px dashed #000;
    margin:12px 0;
}

table{
    width:100%;
    border-collapse:collapse;
}

.info td{
    padding:4px;
    font-size:14px;
}

.data{
    width:100%;
    table-layout:fixed;
}

.data th{
    border:1px solid #000;
    padding:8px;
    background:#efefef;
}

.data td{
    border:1px solid #000;
    padding:8px;
    word-wrap:break-word;
}

.total{
    text-align:right;
    font-size:24px;
    font-weight:bold;
    margin-top:15px;
}

.ttd{
    margin-top:70px;
    display:flex;
    justify-content:space-between;
}

.ttd-box{
    width:220px;
    text-align:center;
}

.ttd-garis{
    margin-top:70px;
    border-top:1px solid #000;
    padding-top:5px;
}

.footer{
    margin-top:30px;
    text-align:center;
    font-size:12px;
}

@media print{

    body{
        background:white;
        padding:0;
        margin:0;
    }

    .toolbar{
        display:none;
    }

    .struk{
        width:100%;
        max-width:none;
        box-shadow:none;
        padding:0;
    }

}

</style>

</head>
<body>

<div class="struk">

<div class="toolbar">

<a href="javascript:window.print()" class="btn cetak">
🖨 CETAK
</a>

<a href="laporan.php" class="btn kembali">
⬅ KEMBALI
</a>

</div>

<div class="center">

<h1>BUKTI PEMBAYARAN</h1>

<h3>IURAN WARGA RT / RW</h3>

<p>Desa Ambalkliwonan</p>

</div>

<div class="garis"></div>

<table class="info">

<tr>
<td width="180">No Bukti</td>
<td>: <?= $nomor_bukti ?></td>
</tr>

<tr>
<td>Tanggal Cetak</td>
<td>: <?= date('d-m-Y H:i:s') ?></td>
</tr>

<tr>
<td>NIK</td>
<td>: <?= $warga['nik'] ?></td>
</tr>

<tr>
<td>Nama</td>
<td>: <?= strtoupper($warga['nama']) ?></td>
</tr>

<tr>
<td>Alamat</td>
<td>: <?= $warga['alamat'] ?></td>
</tr>

<tr>
<td>RT / RW</td>
<td>: <?= $warga['rt'] ?>/<?= $warga['rw'] ?></td>
</tr>

</table>

<div class="garis"></div>

<h3>RINCIAN PEMBAYARAN</h3>

<table class="data">

<tr>
    <th width="40">No</th>
    <th width="90">Tanggal</th>
    <th>Jenis Iuran</th>
    <th width="110">Nominal</th>
</tr>

<?php
$no = 1;

while($d = mysqli_fetch_assoc($transaksi)){

$total += $d['jumlah'];
?>

<tr>

<td align="center">
<?= $no++ ?>
</td>

<td>
<?= date('d-m-Y',strtotime($d['tanggal_bayar'])) ?>
</td>

<td>
<?= strtoupper($d['nama_iuran']) ?>
</td>

<td align="right">
Rp <?= number_format($d['jumlah'],0,',','.') ?>
</td>

</tr>

<?php } ?>

</table>

<div class="garis"></div>

<div class="total">
TOTAL : Rp <?= number_format($total,0,',','.') ?>
</div>

<div class="ttd">

<div class="ttd-box">

Mengetahui

<div class="ttd-garis">
Ketua RT / RW
</div>

</div>

<div class="ttd-box">

Penerima

<div class="ttd-garis">
<?= strtoupper($warga['nama']) ?>
</div>

</div>

</div>

<div class="footer">

==============================================

<br>

Dokumen ini dicetak otomatis oleh Sistem Administrasi Iuran Warga

<br>

© <?= date('Y') ?>

</div>

</div>

</body>
</html>