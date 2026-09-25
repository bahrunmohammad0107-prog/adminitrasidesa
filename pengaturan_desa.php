<?php

include "koneksi.php";


$data = mysqli_fetch_assoc(mysqli_query($koneksi,"
SELECT * FROM profil_desa LIMIT 1
"));



if(isset($_POST['simpan'])){


$nama_desa = $_POST['nama_desa'];
$kecamatan = $_POST['kecamatan'];
$kabupaten = $_POST['kabupaten'];
$alamat    = $_POST['alamat'];



mysqli_query($koneksi,"
UPDATE profil_desa SET

nama_desa='$nama_desa',
kecamatan='$kecamatan',
kabupaten='$kabupaten',
alamat='$alamat'

WHERE id='1'

");



echo "<script>
alert('Data desa berhasil diperbarui');
location='pengaturan_desa.php';
</script>";

}


?>


<!DOCTYPE html>
<html>

<head>

<title>Pengaturan Desa</title>


<style>

body{

background:#0f172a;
color:white;
font-family:Arial;
padding:30px;

}


.card{

background:#111827;
width:500px;
padding:25px;
border-radius:15px;
margin:auto;

}


input,textarea{

width:100%;
padding:10px;
margin-bottom:15px;
border-radius:8px;
border:none;

}


button{

width:100%;
padding:12px;
background:#2563eb;
color:white;
border:none;
border-radius:10px;
cursor:pointer;

}

</style>


</head>


<body>


<div class="card">

<h2>⚙ Pengaturan Desa</h2>


<form method="post">


<label>Nama Desa</label>

<input name="nama_desa"
value="<?= $data['nama_desa']; ?>">



<label>Kecamatan</label>

<input name="kecamatan"
value="<?= $data['kecamatan']; ?>">



<label>Kabupaten</label>

<input name="kabupaten"
value="<?= $data['kabupaten']; ?>">



<label>Alamat</label>

<textarea name="alamat"><?= $data['alamat']; ?></textarea>



<button name="simpan">

💾 Simpan Perubahan

</button>


</form>


</div>


</body>

</html>