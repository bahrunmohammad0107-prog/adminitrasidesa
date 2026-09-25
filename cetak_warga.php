<?php
session_start();

include "cek_login.php";
include "koneksi.php";

$desa_id = $_SESSION['desa_id'];
$level   = $_SESSION['level'];


if($level == "admin"){

    $filter = "1=1";

}else{

    $dusun_id = $_SESSION['dusun_id'];

    $filter = "warga.dusun_id='$dusun_id'";

}


$data = mysqli_query($koneksi,"
SELECT
    warga.*,
    dusun.nama_dusun
FROM warga
LEFT JOIN dusun
ON warga.dusun_id = dusun.id
WHERE $filter
ORDER BY dusun.nama_dusun ASC, warga.nama ASC
");


if(!$data){
    die(mysqli_error($koneksi));
}

?>

<!DOCTYPE html>
<html>
<head>

<title>Cetak Data Warga</title>

<style>

body{
    font-family: Arial;
    font-size: 12px;
}

h2{
    text-align:center;
}


table{
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
}


table, th, td{
    border:1px solid black;
}


th{
    background:#eee;
}


th,td{
    padding:7px;
}


.btn-back{
    background:#6c757d;
    color:white;
    padding:8px 12px;
    text-decoration:none;
    border-radius:5px;
}


.btn-print{
    background:#0d6efd;
    color:white;
    padding:8px 12px;
    text-decoration:none;
    border-radius:5px;
    margin-left:10px;
}


@media print{

    .no-print{
        display:none;
    }

}

</style>

</head>

<body>


<h2>
DATA WARGA BENDHAN
</h2>


<div class="no-print">

<a href="data_warga.php" class="btn-back">
⬅ Kembali
</a>


<a href="#" onclick="window.print()" class="btn-print">
🖨 Cetak
</a>

</div>



<table>

<tr>
<th>No</th>
<th>NIK</th>
<th>Nama</th>
<th>Alamat</th>
<th>RT</th>
<th>RW</th>
<th>Dusun</th>
</tr>


<?php

$no=1;

while($row=mysqli_fetch_assoc($data)){

?>

<tr>

<td><?= $no++; ?></td>

<td><?= $row['nik']; ?></td>

<td><?= strtoupper($row['nama']); ?></td>

<td><?= $row['alamat']; ?></td>

<td><?= $row['rt']; ?></td>

<td><?= $row['rw']; ?></td>

<td><?= strtoupper($row['nama_dusun']); ?></td>

</tr>


<?php } ?>


</table>


</body>
</html>