<?php

session_start();

include "../cek_login.php";
include "../koneksi.php";


$id = $_GET['id'] ?? '';

if($id==''){
    die("ID SPPT tidak ditemukan");
}



$id = mysqli_real_escape_string($koneksi,$id);



$query = mysqli_query($koneksi,"
SELECT 
pajak_sppt.*,
dusun.nama_dusun

FROM pajak_sppt

LEFT JOIN dusun

ON pajak_sppt.dusun_id=dusun.id

WHERE pajak_sppt.id='$id'
");



if(mysqli_num_rows($query)==0){

    die("Data SPPT tidak ditemukan");

}



$d=mysqli_fetch_assoc($query);





$dusun=mysqli_query($koneksi,"
SELECT *

FROM dusun

ORDER BY nama_dusun ASC

");



?>



<!DOCTYPE html>
<html>

<head>

<title>Mutasi SPPT PBB</title>


<style>


body{

font-family:'Segoe UI',sans-serif;

background:#eef2ff;

padding:30px;

}



.card{

max-width:650px;

margin:auto;

background:white;

padding:30px;

border-radius:25px;

box-shadow:0 15px 35px rgba(0,0,0,.12);

}



.header{

background:linear-gradient(135deg,#f59e0b,#d97706);

color:white;

padding:25px;

border-radius:20px;

margin-bottom:25px;

}



.header h2{

margin:0;

font-size:26px;

}



.info{

background:#f8fafc;

padding:20px;

border-radius:15px;

line-height:1.8;

margin-bottom:20px;

}



label{

font-weight:bold;

display:block;

margin-top:15px;

margin-bottom:7px;

}



input,
select{

width:100%;

padding:14px;

border-radius:12px;

border:1px solid #cbd5e1;

font-size:15px;

}



button{

margin-top:25px;

background:#f59e0b;

color:white;

border:none;

padding:15px 25px;

border-radius:15px;

font-weight:bold;

cursor:pointer;

font-size:15px;

}



button:hover{

background:#d97706;

}



.kembali{

display:inline-block;

margin-top:15px;

background:#111827;

color:white;

padding:13px 22px;

border-radius:15px;

text-decoration:none;

font-weight:bold;

}



</style>


</head>


<body>



<div class="card">



<div class="header">

<h2>
🔄 MUTASI SPPT PBB
</h2>

</div>




<div class="info">


<b>NOP</b><br>

<?= $d['nop'] ?>


<hr>


<b>Nama Wajib Pajak</b><br>

<?= strtoupper($d['nama_wajib_pajak']) ?>


<hr>


<b>Pemegang Saat Ini</b><br>


<?= $d['pemegang_sppt'] ?: '-' ?>


<hr>


<b>Dusun Saat Ini</b><br>


<?= $d['nama_dusun'] ?: '-' ?>


</div>





<form action="proses_mutasi.php" method="POST">



<input 
type="hidden"
name="id"
value="<?= $d['id'] ?>"
>




<label>
Nama Pemegang Baru
</label>


<input

type="text"

name="pemegang_sppt"

value="<?= htmlspecialchars($d['pemegang_sppt']) ?>"

placeholder="Masukkan nama pemegang"

required

>




<label>

Pindah Dusun

</label>


<select name="dusun_id" required>


<option value="">
-- Pilih Dusun --
</option>



<?php while($ds=mysqli_fetch_assoc($dusun)){ ?>


<option

value="<?= $ds['id'] ?>"

<?= ($d['dusun_id']==$ds['id'])?'selected':'' ?>

>


<?= $ds['nama_dusun'] ?>


</option>



<?php } ?>


</select>




<button>

💾 SIMPAN MUTASI

</button>




</form>




<a href="data_sppt.php" class="kembali">

⬅ Kembali Data SPPT

</a>



</div>



</body>

</html>