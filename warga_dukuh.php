<?php

include 'koneksi.php';



$id = intval($_GET['id']);



/* ===============================
   AMBIL DATA DUKUH
================================ */


$dukuh = mysqli_fetch_assoc(mysqli_query($koneksi,"


SELECT *

FROM dusun

WHERE id='$id'


"));



if(!$dukuh){


echo "Data dukuh tidak ditemukan";

exit;


}




/* ===============================
   AMBIL DATA WARGA
================================ */


$query = mysqli_query($koneksi,"


SELECT

warga.*,

dusun.nama_dusun


FROM warga


LEFT JOIN dusun

ON warga.dusun_id=dusun.id



WHERE warga.dusun_id='$id'



ORDER BY warga.nama ASC



");




$total = mysqli_num_rows($query);



?>



<!DOCTYPE html>

<html>

<head>


<title>

Warga <?= $dukuh['nama_dusun']; ?>

</title>


<meta charset="utf-8">


<meta name="viewport" content="width=device-width,initial-scale=1">



<link rel="stylesheet"

href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">



<style>


*{

box-sizing:border-box;

}



body{


margin:0;


font-family:'Segoe UI',Arial;


background:#f1f5f9;


color:#1e293b;


}



.container{


padding:25px;


}



.card{


background:white;


padding:25px;


border-radius:20px;


box-shadow:0 10px 30px rgba(0,0,0,.10);


}




.header{


display:flex;


justify-content:space-between;


align-items:center;


flex-wrap:wrap;


gap:15px;


}




.judul h2{


margin:0;


color:#0f172a;


}



.judul p{


margin:5px 0;


color:#64748b;


}





.btn{


display:inline-flex;


align-items:center;


gap:7px;


padding:10px 16px;


border-radius:10px;


text-decoration:none;


font-size:14px;


color:white;


margin:3px;


}



.kembali{


background:#475569;


}



.pdf{


background:#dc2626;


}





.info{


margin-top:20px;


background:#eff6ff;


padding:15px;


border-radius:12px;


border:1px solid #dbeafe;


}



.info b{


font-size:25px;


color:#2563eb;


}





.table-responsive{


overflow-x:auto;


margin-top:20px;


}





table{


width:100%;


border-collapse:collapse;


min-width:900px;


}





thead th{


background:linear-gradient(135deg,#1e3a8a,#2563eb);


color:white;


padding:13px;


font-size:13px;


text-transform:uppercase;


}



tbody td{


padding:12px;


border-bottom:1px solid #e2e8f0;


}



tbody tr:hover{


background:#f8fafc;


}



.nik{


font-family:monospace;


font-weight:bold;


color:#475569;


}



.nama{


font-weight:700;


color:#0f172a;


}



.status{


background:#dcfce7;


color:#166534;


padding:5px 12px;


border-radius:20px;


font-size:12px;


font-weight:600;


}





@media(max-width:768px){


.container{


padding:10px;


}


}


</style>



</head>




<body>



<div class="container">


<div class="card">



<div class="header">


<div class="judul">


<h2>

<i class="fa fa-users"></i>

DATA WARGA DUKUH <?= strtoupper($dukuh['nama_dusun']); ?>

</h2>



<p>

Daftar penduduk berdasarkan dukuh

</p>


</div>




<div>


<a href="laporan_dukuh.php" class="btn kembali">

<i class="fa fa-arrow-left"></i>

Kembali

</a>



<a href="cetak_warga_dukuh.php?id=<?= $id; ?>" target="_blank" class="btn pdf">

<i class="fa fa-file-pdf"></i>

Cetak PDF

</a>


</div>


</div>




<div class="info">


Jumlah Warga


<b>

<?= $total; ?>

</b>

Orang


</div>



<div class="table-responsive">



<table>



<thead>


<tr>


<th width="60">

NO

</th>


<th width="160">

NIK

</th>


<th width="220">

NAMA LENGKAP

</th>


<th>

ALAMAT

</th>


<th width="70">

RT

</th>


<th width="70">

RW

</th>


<th width="150">

DUKUH

</th>


</tr>


</thead>




<tbody>



<?php



$no=1;



while($w=mysqli_fetch_assoc($query)){



?>



<tr>



<td align="center">


<?= $no++; ?>


</td>




<td class="nik">


<?= $w['nik']; ?>


</td>




<td>


<div class="nama">


<?= strtoupper($w['nama']); ?>


</div>


</td>





<td>


<?= $w['alamat']; ?>


</td>





<td align="center">


<?= $w['rt']; ?>


</td>




<td align="center">


<?= $w['rw']; ?>


</td>





<td>


<span class="status">


<?= $w['nama_dusun']; ?>


</span>


</td>



</tr>



<?php } ?>



</tbody>



</table>



</div>





</div>


</div>





</body>


</html>