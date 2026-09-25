<?php

session_start();

include "../cek_login.php";
include "../koneksi.php";

$desa_id = $_SESSION['desa_id'] ?? 1;

$data = mysqli_query($koneksi,"
SELECT
    pajak_sppt.*,
    dusun.nama_dusun
FROM pajak_sppt
LEFT JOIN dusun
ON pajak_sppt.dusun_id=dusun.id
WHERE pajak_sppt.desa_id='$desa_id'
AND pajak_sppt.status='BELUM BAYAR'
ORDER BY nama_wajib_pajak ASC
");

?>

<!DOCTYPE html>
<html>
<head>

<meta charset="utf-8">
<title>Cetak SPPT Belum Bayar</title>

<style>

body{
    font-family:Arial, sans-serif;
    font-size:12px;
}

.judul{
    text-align:center;
    margin-bottom:20px;
}

.judul h2,
.judul h3,
.judul p{
    margin:3px;
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#fee2e2;
}

th,td{
    border:1px solid #000;
    padding:5px;
}

.text-center{
    text-align:center;
}

.text-right{
    text-align:right;
}

</style>

</head>

<body onload="window.print()">

<div class="judul">

<h2>PEMERINTAH DESA AMBALKLIWONAN</h2>

<h3>DAFTAR SPPT BELUM BAYAR</h3>

<p>Tanggal Cetak : <?= date('d-m-Y H:i:s') ?></p>

</div>

<table>

<tr>
    <th>No</th>
    <th>NOP</th>
    <th>Nama WP</th>
    <th>Pemegang SPPT</th>
    <th>Dusun</th>
    <th>L. Tanah</th>
    <th>L. Bangunan</th>
    <th>Pajak</th>
    <th>Status</th>
</tr>

<?php

$no = 1;
$total_pajak = 0;

while($d = mysqli_fetch_assoc($data)){

$total_pajak += $d['pajak_terhitung'];

?>

<tr>

<td class="text-center">
<?= $no++ ?>
</td>

<td>
<?= $d['nop'] ?>
</td>

<td>
<?= strtoupper($d['nama_wajib_pajak']) ?>
</td>

<td>
<?= strtoupper($d['pemegang_sppt'] ?? '-') ?>
</td>

<td>
<?= $d['nama_dusun'] ?>
</td>

<td class="text-center">
<?= number_format($d['luas_tanah'],0,',','.') ?> m²
</td>

<td class="text-center">
<?= number_format($d['luas_bangunan'],0,',','.') ?> m²
</td>

<td class="text-right">
Rp <?= number_format($d['pajak_terhitung'],0,',','.') ?>
</td>

<td class="text-center">
BELUM BAYAR
</td>

</tr>

<?php } ?>

<tr>

<td colspan="7" class="text-right">
<b>TOTAL PAJAK BELUM BAYAR</b>
</td>

<td colspan="2">
<b>
Rp <?= number_format($total_pajak,0,',','.') ?>
</b>
</td>

</tr>

</table>

<br><br>

<table style="border:none;width:100%;">

<tr>

<td style="border:none;width:70%;"></td>

<td style="border:none;text-align:center;">

Ambalkliwonan,
<?= date('d-m-Y') ?>

<br><br><br><br>

<b>
Administrator Pajak
</b>

</td>

</tr>

</table>

</body>
</html>