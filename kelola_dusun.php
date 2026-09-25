<?php

session_start();

include "cek_login.php";
include "koneksi.php";

$desa_id = $_SESSION['desa_id'];

?>
/* =========================
   TAMBAH DUSUN
========================= */

if(isset($_POST['simpan'])){


    $nama_dusun = strtoupper($_POST['nama_dusun']);


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


    header("Location: kelola_dusun.php");
    exit;

}



/* =========================
   HAPUS DUSUN
========================= */

if(isset($_GET['hapus'])){


$id = intval($_GET['hapus']);


mysqli_query($koneksi,"
DELETE FROM dusun
WHERE id='$id'
AND desa_id='$desa_id'
");


header("Location: kelola_dusun.php");
exit;


}



/* =========================
   DATA DUSUN
========================= */

$data = mysqli_query($koneksi,"
SELECT *
FROM dusun
WHERE desa_id='$desa_id'
ORDER BY nama_dusun ASC
");


?>


<!DOCTYPE html>
<html>

<head>

<title>Kelola Dusun</title>


<style>


body{

margin:0;
font-family:Arial;
background:#0f172a;
color:white;
padding:30px;

}



.card{

background:#111827;
padding:25px;
border-radius:15px;
max-width:700px;
margin:auto;

}



h2{

text-align:center;

}



input{

width:70%;
padding:10px;
border-radius:8px;
border:none;

}



button{

padding:10px 15px;
background:#16a34a;
border:none;
border-radius:8px;
color:white;
cursor:pointer;

}



table{

width:100%;
margin-top:25px;
border-collapse:collapse;

}



th{

background:#1f2937;

}



td,th{

padding:12px;
border-bottom:1px solid #374151;

}



.hapus{

background:#dc2626;
padding:7px 10px;
border-radius:8px;
color:white;
text-decoration:none;

}



.back{

display:inline-block;
margin-bottom:15px;
color:white;
text-decoration:none;

}



</style>


</head>


<body>


<div class="card">


<a class="back" href="dashboard.php">
⬅ Dashboard
</a>


<h2>
🏘️ KELOLA DUSUN
</h2>



<form method="post">


<input 
type="text"
name="nama_dusun"
placeholder="Nama Dusun"
required>


<button name="simpan">

➕ Tambah

</button>


</form>



<table>


<tr>

<th>No</th>

<th>Nama Dusun</th>

<th>Aksi</th>

</tr>


<?php

$no=1;

while($d=mysqli_fetch_assoc($data)){

?>


<tr>

<td>
<?= $no++; ?>
</td>


<td>
<?= $d['nama_dusun']; ?>
</td>


<td>


<a class="hapus"
href="?hapus=<?= $d['id']; ?>"
onclick="return confirm('Hapus dusun ini?')">

🗑 Hapus

</a>


</td>


</tr>


<?php } ?>


</table>



</div>


</body>

</html>