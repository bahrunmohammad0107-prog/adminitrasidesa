<?php

include 'koneksi.php';



/* ==================================
   AMBIL DATA DUSUN + JUMLAH WARGA
================================== */


$query = mysqli_query($koneksi,"


SELECT

dusun.id,

dusun.nama_dusun,


COUNT(warga.id) AS jumlah_warga



FROM dusun



LEFT JOIN warga

ON warga.dusun_id = dusun.id



GROUP BY dusun.id



ORDER BY dusun.nama_dusun ASC



");



?>



<!DOCTYPE html>

<html>

<head>


<title>Laporan Warga Per Dukuh</title>


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



.header h2{


margin:0;


color:#0f172a;


}



.header p{


color:#64748b;


}



.btn{


padding:10px 16px;


border-radius:10px;


text-decoration:none;


color:white;


font-size:14px;


display:inline-flex;


gap:7px;


align-items:center;


}



.kembali{


background:#475569;


}






.grid{


margin-top:25px;


display:grid;


grid-template-columns:repeat(auto-fit,minmax(260px,1fr));


gap:20px;


}





.dukuh-card{


background:white;


border-radius:18px;


padding:20px;


border:1px solid #e2e8f0;


box-shadow:0 5px 15px rgba(0,0,0,.05);


transition:.2s;


}




.dukuh-card:hover{


transform:translateY(-5px);


box-shadow:0 10px 25px rgba(0,0,0,.12);


}





.icon{


width:55px;


height:55px;


border-radius:15px;


background:#dbeafe;


color:#2563eb;


display:flex;


align-items:center;


justify-content:center;


font-size:25px;


margin-bottom:15px;


}





.nama-dukuh{


font-size:20px;


font-weight:700;


color:#0f172a;


text-transform:uppercase;


}



.jumlah{


margin:15px 0;


background:#eff6ff;


padding:12px;


border-radius:12px;


color:#334155;


}



.jumlah b{


font-size:25px;


color:#2563eb;


display:block;


}





.lihat{


background:#2563eb;


}



.pdf{


background:#dc2626;


}



</style>



</head>



<body>




<div class="container">



<div class="card">



<div class="header">



<div>


<h2>

<i class="fa fa-users"></i>

Laporan Warga Per Dukuh

</h2>



<p>

Kelompok data warga berdasarkan dukuh/dusun

</p>



</div>




<div>


<a href="data_warga.php" class="btn kembali">


<i class="fa fa-arrow-left"></i>

Kembali


</a>


</div>



</div>




<div class="grid">



<?php



while($d=mysqli_fetch_assoc($query)){



?>



<div class="dukuh-card">



<div class="icon">


<i class="fa fa-map-marker-alt"></i>


</div>




<div class="nama-dukuh">


<?= $d['nama_dusun']; ?>


</div>





<div class="jumlah">


Jumlah Warga


<b>

<?= $d['jumlah_warga']; ?>

</b>


Orang


</div>





<a

href="warga_dukuh.php?id=<?= $d['id']; ?>"

class="btn lihat">


<i class="fa fa-eye"></i>


Lihat Warga


</a>




<a

href="cetak_warga_dukuh.php?id=<?= $d['id']; ?>"

target="_blank"

class="btn pdf">


<i class="fa fa-file-pdf"></i>


Cetak PDF


</a>



</div>



<?php } ?>



</div>




</div>


</div>




</body>


</html>