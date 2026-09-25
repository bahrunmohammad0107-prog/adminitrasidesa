<?php
session_start();

include "koneksi.php";


// ===============================
// JIKA SUDAH LOGIN
// ===============================

if(isset($_SESSION['login']) && $_SESSION['login'] === true){

    header("Location: dashboard.php");
    exit;

}



$error = "";


// ===============================
// PESAN AUTO LOGOUT
// ===============================

if(isset($_GET['expired'])){

    $error = "Sesi Anda telah berakhir, silakan login kembali.";

}



// ===============================
// PROSES LOGIN
// ===============================

if(isset($_POST['login'])){


    $username = mysqli_real_escape_string(
        $koneksi,
        $_POST['username']
    );


    $password = mysqli_real_escape_string(
        $koneksi,
        $_POST['password']
    );



    $query = mysqli_query($koneksi,"
        SELECT *
        FROM users
        WHERE username='$username'
        AND password='$password'
        AND status='aktif'
        LIMIT 1
    ");



    if(!$query){

        die("Query Error : ".mysqli_error($koneksi));

    }




    if(mysqli_num_rows($query) > 0){


        $user = mysqli_fetch_assoc($query);



        // membuat session baru
        session_regenerate_id(true);



        $_SESSION['login'] = true;

        $_SESSION['LAST_ACTIVITY'] = time();

        $_SESSION['id_user'] = $user['id'];

        $_SESSION['nama'] = $user['nama'];

        $_SESSION['username'] = $user['username'];

        $_SESSION['level'] = $user['level'];

        $_SESSION['dusun_id'] = $user['dusun_id'];

        $_SESSION['desa_id'] = $user['desa_id'];



        header("Location: dashboard.php");

        exit;



    }else{


        $error = "Username atau Password Salah!";


    }



}



?>


<!DOCTYPE html>

<html lang="id">

<head>


<meta charset="UTF-8">


<meta name="viewport" content="width=device-width, initial-scale=1">


<title>
Login Sistem Administrasi Desa
</title>


<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">


<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">



<style>


*{

margin:0;
padding:0;
box-sizing:border-box;

}



body{

min-height:100vh;

display:flex;

justify-content:center;

align-items:center;

background:

linear-gradient(
135deg,
#0d47a1,
#1976d2,
#42a5f5
);

font-family:Segoe UI,Tahoma,sans-serif;

overflow:hidden;

}



body:before{

content:"";

position:absolute;

width:500px;

height:500px;

background:rgba(255,255,255,.08);

border-radius:50%;

left:-180px;

top:-150px;

}



body:after{

content:"";

position:absolute;

width:450px;

height:450px;

background:rgba(255,255,255,.08);

border-radius:50%;

right:-180px;

bottom:-180px;

}



.card-login{

width:420px;

background:rgba(255,255,255,.15);

backdrop-filter:blur(15px);

padding:35px;

border-radius:20px;

box-shadow:0 15px 40px rgba(0,0,0,.35);

z-index:2;

}

.logo{

width:100px;

height:100px;

display:block;

margin:auto;

border-radius:50%;

background:white;

padding:5px;

}



.judul{

margin-top:15px;

text-align:center;

color:white;

font-size:20px;

font-weight:bold;

}



.subjudul{

text-align:center;

color:white;

font-size:14px;

margin-bottom:20px;

}



.form-label{

color:white;

font-weight:bold;

}



.form-control{

height:48px;

}



.btn-login{

height:50px;

font-weight:bold;

}



.footer{

margin-top:20px;

text-align:center;

color:white;

font-size:12px;

}


</style>


</head>


<body>



<div class="card-login">



<img 
src="img/OIP.jpg"
class="logo">



<div class="judul">

SISTEM ADMINISTRASI DESA

</div>



<div class="subjudul">

<b>DESA AMBALKLIWONAN</b><br>

Kecamatan Ambal<br>

Kabupaten Kebumen

</div>




<?php if($error!=""){ ?>


<div class="alert alert-danger">

<i class="bi bi-exclamation-circle-fill"></i>

<?= $error ?>

</div>


<?php } ?>




<form method="POST">



<div class="mb-3">


<label class="form-label">

<i class="bi bi-person-fill"></i>

Username

</label>




<div class="input-group">


<span class="input-group-text">

<i class="bi bi-person"></i>

</span>




<input

type="text"

name="username"

class="form-control"

placeholder="Masukkan Username"

required>



</div>


</div>





<div class="mb-4">



<label class="form-label">

<i class="bi bi-lock-fill"></i>

Password

</label>




<div class="input-group">



<span class="input-group-text">

<i class="bi bi-lock"></i>

</span>





<input

type="password"

name="password"

id="password"

class="form-control"

placeholder="Masukkan Password"

required>



<button

type="button"

class="btn btn-light"

onclick="lihatPassword()">



<i 

class="bi bi-eye-fill"

id="iconPassword">

</i>



</button>



</div>


</div>

<button

type="submit"

name="login"

class="btn btn-primary btn-login w-100">


<i class="bi bi-box-arrow-in-right"></i>

LOGIN


</button>



</form>




<div class="footer">

<hr>

<b>Versi 1.0</b><br>

Sistem Administrasi Desa Terpadu<br>

© <?= date('Y'); ?> Pemerintah Desa Ambalkliwonan


</div>



</div>





<script>


function lihatPassword(){


    let password = document.getElementById("password");

    let icon = document.getElementById("iconPassword");



    if(password.type === "password"){


        password.type = "text";

        icon.className = "bi bi-eye-slash-fill";


    }else{


        password.type = "password";

        icon.className = "bi bi-eye-fill";


    }


}


</script>



</body>

</html>