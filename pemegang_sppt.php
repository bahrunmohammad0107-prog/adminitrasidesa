<?php

session_start();

include "../cek_login.php";
include "../koneksi.php";


$desa_id = $_SESSION['desa_id'] ?? 1;

$id = intval($_GET['id'] ?? 0);


if($id<=0){
    die("ID SPPT tidak ditemukan");
}



/*
================================
DATA SPPT
================================
*/

$q=mysqli_query($koneksi,"

SELECT *

FROM pajak_sppt

WHERE id='$id'

AND desa_id='$desa_id'

LIMIT 1

");


$d=mysqli_fetch_assoc($q);


if(!$d){

    die("Data SPPT tidak ditemukan");

}





/*
================================
DATA PEMEGANG TANAH
================================
*/


$pemegang=mysqli_query($koneksi,"

SELECT

pemilik_tanah.nama_pemilik,

kepemilikan_tanah.luas_dimiliki

FROM kepemilikan_tanah


JOIN pemilik_tanah

ON kepemilikan_tanah.pemilik_id=pemilik_tanah.id



WHERE

kepemilikan_tanah.sppt_id='$id'

AND kepemilikan_tanah.luas_dimiliki>0



ORDER BY

pemilik_tanah.nama_pemilik ASC


");



?>

<!DOCTYPE html>
<html>

<head>

<title>Pemegang SPPT</title>

<style>


body{

font-family:'Segoe UI',sans-serif;

background:#eef2ff;

padding:30px;

}


.card{

max-width:800px;

margin:auto;

background:white;

padding:30px;

border-radius:25px;

box-shadow:0 15px 35px rgba(0,0,0,.12);

}



.header{

background:linear-gradient(135deg,#1e3a8a,#2563eb);

color:white;

padding:25px;

border-radius:20px;

margin-bottom:25px;

}



.header h2{

margin:0;

}



.info{

background:#f8fafc;

padding:20px;

border-radius:15px;

line-height:1.8;

margin-bottom:25px;

}



.judul{

font-weight:bold;

font-size:18px;

margin-bottom:15px;

}



.pemilik{

background:#dcfce7;

border-left:6px solid #16a34a;

padding:18px;

border-radius:15px;

margin-bottom:15px;

}



.nama{

font-size:20px;

font-weight:bold;

color:#166534;

}



.luas{

font-weight:bold;

margin-top:8px;

}



.kembali{

display:inline-block;

margin-top:20px;

background:#111827;

color:white;

padding:12px 20px;

border-radius:12px;

text-decoration:none;

font-weight:bold;

}


</style>


</head>


<body>


<div class="card">



<div class="header">

<h2>
👤 DAFTAR PEMEGANG TANAH
</h2>

</div>




<div class="info">


<b>NOP</b><br>

<?= htmlspecialchars($d['nop']) ?>


<br><br>


<b>Nama Wajib Pajak SPPT</b><br>

<?= strtoupper(htmlspecialchars($d['nama_wajib_pajak'])) ?>


<br><br>


<b>Alamat</b><br>

<?= htmlspecialchars($d['alamat_wajib_pajak']) ?>


<br><br>


<b>Pajak Terhutang</b><br>

Rp <?= number_format($d['pajak_terhitung'],0,',','.') ?>


<br><br>


<b>Luas Tanah</b><br>

<?= number_format($d['luas_tanah'],0,',','.') ?> m²



</div>




<div class="judul">

📌 Pemilik / Pemegang Tanah

</div>




<?php


if(mysqli_num_rows($pemegang)==0){


echo "

<div class='pemilik'>

Belum ada data pemegang tanah

</div>

";


}else{


while($p=mysqli_fetch_assoc($pemegang)){


?>


<div class="pemilik">


<div class="nama">

<?= strtoupper($p['nama_pemilik']) ?>

</div>


<div class="luas">

Luas :

<?= number_format($p['luas_dimiliki'],0,',','.') ?>

m²

</div>


</div>



<?php


}


}


?>





<a href="data_sppt.php" class="kembali">

⬅ Kembali Data SPPT

</a>



</div>


</body>

</html>