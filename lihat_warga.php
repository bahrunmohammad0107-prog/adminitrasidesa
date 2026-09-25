<?php

include 'koneksi.php';


$id = intval($_GET['id']);



$query = mysqli_query($koneksi,"


SELECT

warga.*,

dusun.nama_dusun


FROM warga


LEFT JOIN dusun


ON warga.dusun_id = dusun.id



WHERE warga.id='$id'


");



$data = mysqli_fetch_assoc($query);



if(!$data){

echo "Data warga tidak ditemukan";

exit;

}



?>


<!DOCTYPE html>

<html>

<head>


<title>Detail Warga</title>


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


min-height:100vh;


display:flex;


justify-content:center;


align-items:center;


padding:25px;


}




.card{


width:500px;


background:white;


border-radius:25px;


overflow:hidden;


box-shadow:0 15px 40px rgba(0,0,0,.15);


}




.header{


background:linear-gradient(135deg,#1e3a8a,#2563eb);


color:white;


padding:25px;


text-align:center;


}



.header i{


font-size:45px;


margin-bottom:10px;


}



.header h2{


margin:0;


font-size:24px;


}



.header p{


margin:5px 0 0;


opacity:.8;


}






.body-card{


padding:25px;


}





.item{


display:flex;


align-items:center;


gap:15px;


padding:15px 0;


border-bottom:1px solid #e2e8f0;


}




.item:last-child{


border-bottom:none;


}




.icon{


width:42px;


height:42px;


border-radius:12px;


background:#dbeafe;


display:flex;


align-items:center;


justify-content:center;


color:#2563eb;


font-size:18px;


}





.label{


font-size:12px;


color:#64748b;


text-transform:uppercase;


font-weight:600;


}




.value{


font-size:16px;


font-weight:700;


color:#0f172a;


margin-top:3px;


}





.nik{


font-family:monospace;


letter-spacing:1px;


color:#334155;


}




.dusun{


display:inline-block;


padding:6px 15px;


border-radius:20px;


background:#dcfce7;


color:#166534;


font-size:13px;


font-weight:600;


}





.footer{


padding:20px 25px;


background:#f8fafc;


display:flex;


gap:10px;


}





.btn{


flex:1;


padding:12px;


border-radius:12px;


text-align:center;


text-decoration:none;


font-size:14px;


font-weight:600;


transition:.2s;


}





.btn:hover{


transform:translateY(-2px);


}



.kembali{


background:#475569;


color:white;


}




.edit{


background:#f59e0b;


color:white;


}




@media(max-width:600px){



.container{


padding:10px;


}



.card{


width:100%;


}



.footer{


flex-direction:column;


}



}



</style>



</head>



<body>




<div class="container">



<div class="card">



<div class="header">


<i class="fa-solid fa-user"></i>



<h2>

Detail Data Warga

</h2>



<p>

Administrasi Desa Ambalkliwonan

</p>


</div>





<div class="body-card">
    



<div class="item">


<div class="icon">

<i class="fa-solid fa-id-card"></i>

</div>


<div>


<div class="label">

NIK

</div>


<div class="value nik">

<?= $data['nik']; ?>

</div>


</div>


</div>





<div class="item">


<div class="icon">

<i class="fa-solid fa-user"></i>

</div>


<div>


<div class="label">

Nama Lengkap

</div>


<div class="value">

<?= strtoupper($data['nama']); ?>

</div>


</div>


</div>





<div class="item">


<div class="icon">

<i class="fa-solid fa-location-dot"></i>

</div>


<div>


<div class="label">

Alamat

</div>


<div class="value">

<?= $data['alamat']; ?>

</div>


</div>


</div>





<div class="item">


<div class="icon">

<i class="fa-solid fa-map"></i>

</div>


<div>


<div class="label">

Dusun

</div>



<div class="value">


<span class="dusun">


<?= $data['nama_dusun'] ?? '-'; ?>

</span>


</div>


</div>


</div>






<div class="item">


<div class="icon">

<i class="fa-solid fa-road"></i>

</div>


<div>


<div class="label">

RT / RW

</div>


<div class="value">

RT <?= $data['rt']; ?> 

/

RW <?= $data['rw']; ?>

</div>


</div>


</div>






</div>





<div class="footer">



<a href="data_warga.php" class="btn kembali">

<i class="fa fa-arrow-left"></i>

Kembali

</a>




<a href="edit_warga.php?id=<?= $data['id']; ?>" class="btn edit">

<i class="fa fa-edit"></i>

Edit Data

</a>



</div>




</div>


</div>





</body>


</html>