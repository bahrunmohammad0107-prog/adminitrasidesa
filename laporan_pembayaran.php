<?php
include 'koneksi.php';

$jenis_filter = $_GET['jenis_iuran'] ?? '';
$tahun = $_GET['tahun'] ?? date('Y');

$jenis = mysqli_query($koneksi,"
SELECT * FROM jenis_iuran
ORDER BY nama_iuran
");

$warga = mysqli_query($koneksi,"
SELECT *
FROM warga
ORDER BY nama ASC
");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Laporan Pembayaran</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
    background:#eef2f7;
}

.header{
    background:linear-gradient(135deg,#0d6efd,#6610f2);
    color:white;
    padding:20px;
    border-radius:15px;
}

.card-box{
    background:white;
    padding:20px;
    border-radius:15px;
    box-shadow:0 3px 10px rgba(0,0,0,.1);
}

.riwayat{
    line-height:1.8;
}

.jenis{
    font-weight:bold;
    color:#0d6efd;
}

.total{
    font-weight:bold;
    color:#198754;
}

</style>

</head>

<body>

<div class="container mt-4">

<div class="header">
<h3>📊 LAPORAN PEMBAYARAN IURAN</h3>
<small>Riwayat Pembayaran Warga</small>
</div>

<br>

<div class="card-box mb-3">

<form method="GET" class="row">

<div class="col-md-4">
<label>Jenis Iuran</label>

<select name="jenis_iuran" class="form-control">

<option value="">Semua Iuran</option>

<?php while($j=mysqli_fetch_assoc($jenis)){ ?>

<option
value="<?= $j['id'] ?>"
<?= ($jenis_filter==$j['id'])?'selected':'' ?>>

<?= $j['nama_iuran'] ?>

</option>

<?php } ?>

</select>
</div>

<div class="col-md-3">
<label>Tahun</label>

<input
type="text"
name="tahun"
class="form-control"
value="<?= $tahun ?>">
</div>

<div class="col-md-3">
<label>&nbsp;</label>

<button class="btn btn-primary w-100">
🔍 Tampilkan
</button>
</div>

</form>

</div>

<div class="card-box">

<table class="table table-bordered">

<tr>
<th width="50">No</th>
<th>NIK</th>
<th>Nama</th>
<th>RT/RW</th>
<th>Riwayat Pembayaran</th>
<th>Total</th>
</tr>

<?php

$no=1;

while($w=mysqli_fetch_assoc($warga)){

$id_warga = $w['id'];

$where = "
p.warga_id='$id_warga'
AND p.tahun='$tahun'
";

if($jenis_filter!=''){
    $where .= " AND p.jenis_iuran_id='$jenis_filter'";
}

$trx = mysqli_query($koneksi,"
SELECT
p.*,
j.nama_iuran
FROM pembayaran p
LEFT JOIN jenis_iuran j
ON p.jenis_iuran_id=j.id
WHERE $where
ORDER BY j.nama_iuran, p.tanggal_bayar
");

$riwayat = '';
$total = 0;

$current_jenis = '';

while($t=mysqli_fetch_assoc($trx)){

$total += $t['jumlah'];

if($current_jenis != $t['nama_iuran']){

$current_jenis = $t['nama_iuran'];

$riwayat .= "
<div class='jenis'>
".$t['nama_iuran']."
</div>";
}

$riwayat .= "
• ".date('d-m-Y',strtotime($t['tanggal_bayar']))."
 : Rp ".number_format($t['jumlah'],0,',','.')."<br>";
}

if($riwayat==''){
    $riwayat = "<span class='text-danger'>Belum ada pembayaran</span>";
}

?>

<tr>

<td><?= $no++ ?></td>

<td><?= $w['nik'] ?></td>

<td><?= $w['nama'] ?></td>

<td><?= $w['rt'] ?>/<?= $w['rw'] ?></td>

<td class="riwayat">
<?= $riwayat ?>
</td>

<td class="total">
Rp <?= number_format($total,0,',','.') ?>
</td>

</tr>

<?php } ?>

</table>

</div>

</div>

</body>
</html>