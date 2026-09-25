<?php

session_start();

include "../cek_login.php";
include "../koneksi.php";

$desa_id = $_SESSION['desa_id'] ?? 1;

$qTotal = mysqli_query($koneksi,"
SELECT COUNT(*) total
FROM pajak_sppt
WHERE desa_id='$desa_id'
");
$total = mysqli_fetch_assoc($qTotal)['total'];

$qLunas = mysqli_query($koneksi,"
SELECT COUNT(*) total
FROM pajak_sppt
WHERE desa_id='$desa_id'
AND status='SUDAH BAYAR'
");
$lunas = mysqli_fetch_assoc($qLunas)['total'];

$qBelum = mysqli_query($koneksi,"
SELECT COUNT(*) total
FROM pajak_sppt
WHERE desa_id='$desa_id'
AND status='BELUM BAYAR'
");
$belum = mysqli_fetch_assoc($qBelum)['total'];

$qPajak = mysqli_query($koneksi,"
SELECT SUM(pajak_terhitung) total
FROM pajak_sppt
WHERE desa_id='$desa_id'
");
$total_pajak = mysqli_fetch_assoc($qPajak)['total'] ?? 0;

?>

<!DOCTYPE html>
<html>
<head>

<meta charset="utf-8">
<title>Laporan Pajak PBB</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
    background:#f1f5f9;
}

.card-menu{
    border:none;
    border-radius:20px;
    transition:.3s;
    box-shadow:0 5px 15px rgba(0,0,0,.08);
}

.card-menu:hover{
    transform:translateY(-5px);
}

.icon{
    font-size:45px;
    margin-bottom:10px;
}

</style>

</head>

<body>

<div class="container py-4">

<div class="text-center mb-4">

<h2 class="fw-bold text-primary">
📊 LAPORAN PAJAK PBB
</h2>

<p class="text-muted">
Desa Ambalkliwonan
</p>

</div>


<div class="row g-3 mb-4">

<div class="col-md-3">
<div class="card text-center shadow-sm">
<div class="card-body">
<h3><?= number_format($total) ?></h3>
<small>Total SPPT</small>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card text-center shadow-sm">
<div class="card-body">
<h3><?= number_format($lunas) ?></h3>
<small>Sudah Bayar</small>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card text-center shadow-sm">
<div class="card-body">
<h3><?= number_format($belum) ?></h3>
<small>Belum Bayar</small>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card text-center shadow-sm">
<div class="card-body">
<h6>Rp <?= number_format($total_pajak,0,',','.') ?></h6>
<small>Total Pajak</small>
</div>
</div>
</div>

</div>


<div class="row g-4">

<div class="col-md-4">

<a href="cetak_laporan.php"
target="_blank"
class="text-decoration-none">

<div class="card card-menu">

<div class="card-body text-center">

<div class="icon">
🖨️
</div>

<h5>Cetak Semua SPPT</h5>

<small class="text-muted">
Cetak seluruh data SPPT
</small>

</div>

</div>

</a>

</div>



<div class="col-md-4">

<a href="cetak_lunas.php"
target="_blank"
class="text-decoration-none">

<div class="card card-menu">

<div class="card-body text-center">

<div class="icon">
🟢
</div>

<h5>Sudah Bayar</h5>

<small class="text-muted">
Cetak data lunas
</small>

</div>

</div>

</a>

</div>



<div class="col-md-4">

<a href="cetak_belum.php"
target="_blank"
class="text-decoration-none">

<div class="card card-menu">

<div class="card-body text-center">

<div class="icon">
🔴
</div>

<h5>Belum Bayar</h5>

<small class="text-muted">
Cetak data belum bayar
</small>

</div>

</div>

</a>

</div>



<div class="col-md-4">

<a href="rekap_dusun.php"
class="text-decoration-none">

<div class="card card-menu">

<div class="card-body text-center">

<div class="icon">
🏠
</div>

<h5>Rekap Dusun</h5>

<small class="text-muted">
Laporan per dusun
</small>

</div>

</div>

</a>

</div>



<div class="col-md-4">

<a href="rekap_pemegang.php"
class="text-decoration-none">

<div class="card card-menu">

<div class="card-body text-center">

<div class="icon">
👤
</div>

<h5>Rekap Pemegang</h5>

<small class="text-muted">
Laporan per pemegang SPPT
</small>

</div>

</div>

</a>

</div>



<div class="col-md-4">

<a href="index.php"
class="text-decoration-none">

<div class="card card-menu">

<div class="card-body text-center">

<div class="icon">
⬅️
</div>

<h5>Dashboard</h5>

<small class="text-muted">
Kembali ke dashboard
</small>

</div>

</div>

</a>

</div>

</div>

</div>

</body>
</html>