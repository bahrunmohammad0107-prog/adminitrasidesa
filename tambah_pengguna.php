<?php

session_start();

include "cek_login.php";
include "koneksi.php";


// hanya admin

if($_SESSION['level'] != 'admin'){

    header("Location: dashboard.php");
    exit;

}


$desa_id = $_SESSION['desa_id'];


// ===============================
// SIMPAN DATA
// ===============================

if(isset($_POST['simpan'])){


    $nama = mysqli_real_escape_string(
        $koneksi,
        $_POST['nama']
    );


    $username = mysqli_real_escape_string(
        $koneksi,
        $_POST['username']
    );


    $password = mysqli_real_escape_string(
        $koneksi,
        $_POST['password']
    );


    $level = $_POST['level'];


    $dusun_id = $_POST['dusun_id'];


    $status = $_POST['status'];



    mysqli_query($koneksi,"
        INSERT INTO users
        (
            desa_id,
            nama,
            username,
            password,
            level,
            dusun_id,
            status
        )

        VALUES

        (
            '$desa_id',
            '$nama',
            '$username',
            '$password',
            '$level',
            '$dusun_id',
            '$status'
        )
    ");



    header("Location:data_pengguna.php");
    exit;


}




// ambil dusun

$dusun = mysqli_query($koneksi,"

SELECT *

FROM dusun

WHERE desa_id='$desa_id'

ORDER BY nama_dusun ASC

");


?>


<!DOCTYPE html>

<html>

<head>

<title>Tambah Pengguna</title>

<meta charset="utf-8">

<style>


body{

font-family:Segoe UI;

background:#f1f5f9;

}


.container{

padding:30px;

}


.card{

background:white;

padding:25px;

max-width:500px;

margin:auto;

border-radius:20px;

box-shadow:0 10px 30px rgba(0,0,0,.1);

}


input,select{

width:100%;

padding:12px;

margin-bottom:15px;

border:1px solid #ddd;

border-radius:10px;

}



button,a{

padding:12px 20px;

border-radius:10px;

border:none;

text-decoration:none;

cursor:pointer;

}



button{

background:#16a34a;

color:white;

}



.kembali{

background:#475569;

color:white;

}



</style>


</head>


<body>


<div class="container">


<div class="card">


<h2>
👤 Tambah Pengguna
</h2>


<form method="POST">


<label>
Nama Lengkap
</label>

<input 
type="text"
name="nama"
required>



<label>
Username Login
</label>

<input 
type="text"
name="username"
required>




<label>
Password
</label>

<input 
type="text"
name="password"
required>




<label>
Level
</label>

<select name="level" required>


<option value="kadus">
Kadus
</option>


<option value="admin">
Admin
</option>


</select>





<label>
Pilih Dusun
</label>


<select name="dusun_id">


<option value="">
-- Pilih Dusun --
</option>


<?php while($d=mysqli_fetch_assoc($dusun)){ ?>


<option value="<?= $d['id']; ?>">

<?= $d['nama_dusun']; ?>

</option>


<?php } ?>


</select>





<label>
Status
</label>


<select name="status">


<option value="aktif">
Aktif
</option>


<option value="nonaktif">
Nonaktif
</option>


</select>





<button name="simpan">

💾 Simpan

</button>



<a href="data_pengguna.php" class="kembali">

Kembali

</a>



</form>


</div>


</div>


</body>

</html>