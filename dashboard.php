<?php

error_reporting(E_ALL);
ini_set('display_errors',1);

session_start();

include "cek_login.php";
include "koneksi.php";


// ============================
// DATA LOGIN
// ============================

$desa_id = $_SESSION['desa_id'] ?? 1;

$level = $_SESSION['level'] ?? 'admin';

$nama_user = $_SESSION['nama'] ?? 'USER';




// ============================
// PROFIL DESA
// ============================


$qProfil = mysqli_query($koneksi,"
    SELECT *
    FROM profil_desa
    WHERE id='$desa_id'
    LIMIT 1
");


$profil = mysqli_fetch_assoc($qProfil);



if(!$profil){

    $profil=[
        'nama_desa'=>'AMBALKLIWONAN',
        'kecamatan'=>'AMBAL',
        'kabupaten'=>'KEBUMEN'
    ];

}





// ============================
// FUNGSI TOTAL
// ============================


function ambil_total($koneksi,$sql){

    $query=mysqli_query($koneksi,$sql);


    if(!$query){

        return 0;

    }


    $data=mysqli_fetch_assoc($query);


    return $data['total'] ?? 0;

}




// ============================
// FILTER ADMIN / KADUS
// ============================


$filter_warga="";

$filter_bayar="";



if($level!="admin"){


    $dusun_id=$_SESSION['dusun_id'];


    $filter_warga="
    AND warga.dusun_id='$dusun_id'
    ";


    $filter_bayar="
    AND warga.dusun_id='$dusun_id'
    ";

}




// ============================
// TOTAL DATA
// ============================



$total_warga = ambil_total($koneksi,"

SELECT COUNT(*) total

FROM warga

WHERE desa_id='$desa_id'

$filter_warga

");





$total_transaksi = ambil_total($koneksi,"

SELECT COUNT(*) total

FROM pembayaran

LEFT JOIN warga

ON pembayaran.warga_id=warga.id

WHERE warga.desa_id='$desa_id'

$filter_bayar

");





$total_pemasukan = ambil_total($koneksi,"

SELECT SUM(pembayaran.jumlah) total

FROM pembayaran

LEFT JOIN warga

ON pembayaran.warga_id=warga.id

WHERE warga.desa_id='$desa_id'

$filter_bayar

");






// ============================
// GRAFIK
// ============================


$bulan=[];

$nilai=[];



for($i=5;$i>=0;$i--){


    $tgl=date(
        'Y-m',
        strtotime("-".$i." month")
    );


    $bulan[]=date(
        'M Y',
        strtotime("-".$i." month")
    );



    $q=mysqli_query($koneksi,"

    SELECT SUM(jumlah) total

    FROM pembayaran

    WHERE DATE_FORMAT(
    tanggal_bayar,'%Y-%m'
    )='$tgl'

    ");



    $d=mysqli_fetch_assoc($q);


    $nilai[]=$d['total'] ?? 0;


}




// ============================
// TRANSAKSI TERBARU
// ============================


$transaksi=mysqli_query($koneksi,"

SELECT

p.*,

w.nama


FROM pembayaran p


LEFT JOIN warga w

ON p.warga_id=w.id


WHERE w.desa_id='$desa_id'


ORDER BY p.id DESC


LIMIT 5


");



?>


<!DOCTYPE html>

<html lang="id">

<head>


<meta charset="UTF-8">


<meta name="viewport" content="width=device-width,initial-scale=1">


<title>
Dashboard Administrasi Desa
</title>



<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>



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
/* =========================
SIDEBAR
========================= */


.sidebar{

position:fixed;

top:0;

left:0;

width:300px;

height:100vh;

background:#0f172a;

padding:25px;

color:white;

overflow-y:auto;

}



.sidebar h2{

text-align:center;

color:#38bdf8;

font-size:26px;

margin-bottom:25px;

}



.user-box{

background:#1e293b;

padding:15px;

border-radius:15px;

margin-bottom:20px;

font-size:14px;

}



.user-box b{

color:#38bdf8;

}




.sidebar a{


display:flex;


align-items:center;


gap:10px;


padding:13px 15px;


margin:6px 0;


border-radius:12px;


color:white;


text-decoration:none;


font-size:15px;


transition:.3s;


}



.sidebar a:hover{


background:#1e293b;


transform:translateX(5px);


}



.sidebar h4{


font-size:13px;


color:#94a3b8;


margin-top:25px;


}





/* =========================
MAIN
========================= */


.main{

margin-left:300px;

padding:30px;

}




/* =========================
HEADER
========================= */


.header{

background:linear-gradient(
135deg,
#2563eb,
#f97316
);


padding:40px;


border-radius:25px;


color:white;


box-shadow:0 15px 35px rgba(0,0,0,.15);


}



.header h1{

margin:0;

font-size:32px;

}



.header p{

font-size:16px;

margin:10px 0;

}




/* =========================
CARD
========================= */


.card-container{

display:flex;

gap:25px;

margin-top:30px;

flex-wrap:wrap;

}



.card{

flex:1;

min-width:250px;

padding:30px;

border-radius:25px;

color:white;

box-shadow:0 10px 25px rgba(0,0,0,.15);

}



.card h3{

margin:0;

font-size:18px;

}



.card h1{

font-size:38px;

margin:15px 0 0;

}



.blue{

background:#2563eb;

}



.green{

background:#16a34a;

}



.orange{

background:#ea580c;

}





/* =========================
CHART
========================= */


.chart-box{

background:white;

padding:30px;

margin-top:30px;

border-radius:25px;

box-shadow:0 10px 25px rgba(0,0,0,.08);

}




/* =========================
TABLE
========================= */


.table-box{

background:white;

padding:25px;

margin-top:30px;

border-radius:25px;

box-shadow:0 10px 25px rgba(0,0,0,.08);

}



table{

width:100%;

border-collapse:collapse;

}



th{

background:#0f172a;

color:white;

padding:15px;

}



td{

padding:15px;

border-bottom:1px solid #e2e8f0;

}



tr:hover{

background:#f8fafc;

}




/* =========================
MOBILE
========================= */


@media(max-width:900px){


.sidebar{

position:relative;

width:100%;

height:auto;

}



.main{

margin-left:0;

padding:15px;

}



.card-container{

flex-direction:column;

}



}


</style>


</head>


<body>
    <!-- =========================
SIDEBAR
========================= -->


<div class="sidebar">


<h2>
ADMIN PANEL
</h2>



<div class="user-box">

Login :

<br>

<b>
<?= strtoupper($nama_user); ?>
</b>


<br>


Level :

<b>
<?= strtoupper($level); ?>
</b>


</div>




<a href="dashboard.php">

<i class="fa fa-home"></i>

Dashboard

</a>



<hr>



<h4>
💰 ADMINISTRASI IURAN
</h4>




<a href="data_warga.php">

<i class="fa fa-users"></i>

Data Warga

</a>




<a href="pembayaran_warga.php">

<i class="fa fa-money-bill"></i>

Pembayaran Warga

</a>




<a href="laporan.php">

<i class="fa fa-file"></i>

Laporan Iuran

</a>




<a href="jenis_iuran.php">

<i class="fa fa-list"></i>

Jenis Iuran

</a>






<?php if($level=="admin"){ ?>



<hr>


<h4>
⚙ ADMINISTRASI SISTEM
</h4>



<a href="data_pengguna.php">

<i class="fa fa-user-gear"></i>

Data Pengguna

</a>



<a href="tambah_pengguna.php">

<i class="fa fa-user-plus"></i>

Tambah Pengguna

</a>



<a href="data_dusun.php">

<i class="fa fa-house"></i>

Data Dusun

</a>



<?php } ?>






<hr>


<h4>
🧾 ADMINISTRASI PAJAK
</h4>



<a href="pajak/index.php">

<i class="fa fa-file-invoice"></i>

Data Pajak

</a>




<a href="pajak/pembayaran.php">

<i class="fa fa-money-check"></i>

Pembayaran Pajak

</a>




<a href="pajak/laporan.php">

<i class="fa fa-chart-column"></i>

Laporan Pajak

</a>




<hr>




<a href="logout.php">

<i class="fa fa-right-from-bracket"></i>

Logout

</a>



</div>









<!-- =========================
MAIN CONTENT
========================= -->


<div class="main">



<div class="header">


<h1>

ADMINISTRASI DESA

<?= strtoupper($profil['nama_desa']); ?>

</h1>




<p>

Kecamatan

<?= $profil['kecamatan']; ?>


|

Kabupaten

<?= $profil['kabupaten']; ?>

</p>



<small>

Dashboard Monitoring Keuangan & Iuran

</small>



</div>






<!-- =========================
STATISTIK
========================= -->



<div class="card-container">



<div class="card blue">


<h3>

👥 Total Warga

</h3>


<h1>

<?= $total_warga; ?>

</h1>


</div>





<div class="card green">


<h3>

💰 Total Transaksi

</h3>



<h1>

<?= $total_transaksi; ?>

</h1>


</div>






<div class="card orange">


<h3>

💵 Total Pemasukan

</h3>



<h1>

Rp

<?= number_format(
$total_pemasukan,
0,
',',
'.'
); ?>

</h1>


</div>




</div>






<div class="chart-box">


<h3>

📊 Grafik Pemasukan 6 Bulan Terakhir

</h3>



<canvas id="chart"></canvas>


</div>
<div class="table-box">


<h3>
💳 Transaksi Terbaru
</h3>




<table>


<tr>

<th>
Nama Warga
</th>


<th>
Jumlah
</th>


<th>
Tanggal
</th>


</tr>





<?php while($d=mysqli_fetch_assoc($transaksi)){ ?>



<tr>


<td>

<?= strtoupper($d['nama'] ?? '-'); ?>

</td>




<td>

Rp 

<?= number_format(

$d['jumlah'] ?? 0,

0,

',',

'.'

); ?>


</td>





<td>

<?= $d['tanggal_bayar']; ?>

</td>



</tr>



<?php } ?>



</table>



</div>






</div>







<script>


new Chart(

document.getElementById('chart'),


{


type:'line',



data:{



labels:

<?= json_encode($bulan); ?>,




datasets:[{


label:'Pemasukan',



data:

<?= json_encode($nilai); ?>,



borderWidth:3,


tension:.4



}]



},



options:{


responsive:true,


plugins:{


legend:{


display:true


}


}



}



}



);



</script>






</body>

</html>