<?php

session_start();

include "cek_login.php";
include "koneksi.php";


// hanya admin yang boleh masuk
if($_SESSION['level'] != 'admin'){

    header("Location: dashboard.php");
    exit;

}


$desa_id = $_SESSION['desa_id'];


// ===============================
// HAPUS USER
// ===============================

if(isset($_GET['hapus'])){

    $id = intval($_GET['hapus']);


    mysqli_query($koneksi,"
        DELETE FROM users
        WHERE id='$id'
        AND desa_id='$desa_id'
    ");


    header("Location:data_pengguna.php");
    exit;

}




// ===============================
// AMBIL DATA USER
// ===============================


$query = mysqli_query($koneksi,"

SELECT

users.*,

dusun.nama_dusun


FROM users


LEFT JOIN dusun

ON users.dusun_id=dusun.id


WHERE users.desa_id='$desa_id'


ORDER BY users.level ASC, users.nama ASC


");



?>


<!DOCTYPE html>

<html>

<head>

<title>Data Pengguna</title>


<meta charset="utf-8">


<meta name="viewport" content="width=device-width, initial-scale=1">


<link rel="stylesheet"

href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">


<style>


body{

margin:0;

background:#f1f5f9;

font-family:Segoe UI,Arial;

}


.container{

padding:25px;

}


.card{

background:white;

padding:25px;

border-radius:20px;

box-shadow:0 10px 30px rgba(0,0,0,.1);

}



h2{

margin-top:0;

}



.btn{

display:inline-block;

padding:10px 15px;

border-radius:10px;

text-decoration:none;

color:white;

margin:3px;

font-size:14px;

}



.tambah{

background:#16a34a;

}



.kembali{

background:#475569;

}



.edit{

background:#f59e0b;

}



.hapus{

background:#dc2626;

}



table{

width:100%;

border-collapse:collapse;

margin-top:20px;

}



th{

background:#1e3a8a;

color:white;

padding:12px;

}



td{

padding:12px;

border-bottom:1px solid #ddd;

}



.badge{

padding:5px 12px;

border-radius:20px;

background:#dcfce7;

color:#166534;

font-size:12px;

}



.admin{

background:#dbeafe;

color:#1e40af;

}



</style>


</head>


<body>



<div class="container">


<div class="card">


<h2>
👥 DATA PENGGUNA DESA
</h2>


<p>
Kelola akun Admin dan Kadus
</p>



<a href="dashboard.php" class="btn kembali">

<i class="fa fa-arrow-left"></i>

Dashboard

</a>



<a href="tambah_pengguna.php" class="btn tambah">

<i class="fa fa-user-plus"></i>

Tambah Pengguna

</a>





<table>


<tr>

<th>No</th>

<th>Nama</th>

<th>Username</th>

<th>Level</th>

<th>Dusun</th>

<th>Status</th>

<th>Aksi</th>


</tr>



<?php


$no=1;


while($row=mysqli_fetch_assoc($query)){


?>


<tr>


<td>
<?= $no++; ?>
</td>



<td>
<?= strtoupper($row['nama']); ?>
</td>



<td>
<?= $row['username']; ?>
</td>



<td>


<?php if($row['level']=="admin"){ ?>


<span class="badge admin">
ADMIN
</span>


<?php }else{ ?>


<span class="badge">
KADUS
</span>


<?php } ?>


</td>




<td>

<?= $row['nama_dusun'] ?? '-'; ?>

</td>



<td>

<?= strtoupper($row['status']); ?>

</td>




<td>


<a href="edit_pengguna.php?id=<?= $row['id']; ?>"

class="btn edit">

<i class="fa fa-edit"></i>

Edit

</a>



<a href="data_pengguna.php?hapus=<?= $row['id']; ?>"

onclick="return confirm('Hapus pengguna ini?')"

class="btn hapus">

<i class="fa fa-trash"></i>

Hapus

</a>



</td>



</tr>


<?php } ?>


</table>


</div>


</div>



</body>

</html>