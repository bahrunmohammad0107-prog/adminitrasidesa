<?php

session_start();

include "cek_login.php";
include "koneksi.php";


// =========================
// HANYA ADMIN
// =========================

if($_SESSION['level'] != 'admin'){

    header("Location: dashboard.php");
    exit;

}


$desa_id = $_SESSION['desa_id'];



// =========================
// TAMBAH DUSUN
// =========================

if(isset($_POST['simpan'])){


    $nama_dusun = strtoupper(
        mysqli_real_escape_string(
            $koneksi,
            $_POST['nama_dusun']
        )
    );



    if($nama_dusun!=""){


        mysqli_query($koneksi,"
            INSERT INTO dusun
            (
                desa_id,
                nama_dusun
            )
            VALUES
            (
                '$desa_id',
                '$nama_dusun'
            )
        ");


    }


    header("Location:data_dusun.php");
    exit;


}




// =========================
// HAPUS DUSUN
// =========================

if(isset($_GET['hapus'])){


    $id=intval($_GET['hapus']);



    mysqli_query($koneksi,"
        DELETE FROM dusun
        WHERE id='$id'
        AND desa_id='$desa_id'
    ");



    header("Location:data_dusun.php");
    exit;


}




// =========================
// DATA DUSUN
// =========================


$data=mysqli_query($koneksi,"
    SELECT *
    FROM dusun
    WHERE desa_id='$desa_id'
    ORDER BY nama_dusun ASC
");



?>


<!DOCTYPE html>

<html>

<head>

<title>Data Dusun</title>


<meta charset="utf-8">


<meta name="viewport" content="width=device-width, initial-scale=1">


<link rel="stylesheet"

href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">



<style>


body{

margin:0;

font-family:'Segoe UI',Arial;

background:#f1f5f9;

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

border:none;

cursor:pointer;

margin:3px;

}



.kembali{

background:#475569;

}



.tambah{

background:#16a34a;

}



.hapus{

background:#dc2626;

}



input{

padding:12px;

width:300px;

border:1px solid #ddd;

border-radius:10px;

}



table{

width:100%;

margin-top:25px;

border-collapse:collapse;

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



</style>


</head>



<body>


<div class="container">


<div class="card">


<h2>
🏘️ DATA DUSUN DESA
</h2>


<p>
Kelola wilayah dusun Desa Ambalkliwonan
</p>



<a href="dashboard.php" class="btn kembali">

<i class="fa fa-arrow-left"></i>

Dashboard

</a>





<hr>



<h3>
➕ Tambah Dusun
</h3>


<form method="POST">


<input

type="text"

name="nama_dusun"

placeholder="Nama Dusun"

required>



<button

type="submit"

name="simpan"

class="btn tambah">

<i class="fa fa-save"></i>

Simpan

</button>


</form>







<table>


<tr>

<th width="50">
No
</th>


<th>
Nama Dusun
</th>


<th>
Aksi
</th>


</tr>



<?php


$no=1;


while($row=mysqli_fetch_assoc($data)){


?>


<tr>


<td>
<?=$no++;?>
</td>


<td>

<?= $row['nama_dusun']; ?>

</td>



<td>


<a

href="data_dusun.php?hapus=<?=$row['id'];?>"

class="btn hapus"

onclick="return confirm('Hapus dusun ini?')">

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