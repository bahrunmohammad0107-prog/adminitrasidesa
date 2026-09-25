<?php

include "../cek_login.php";
include "../koneksi.php";

$sppt_id = $_GET['id'];

$sppt = mysqli_fetch_assoc(mysqli_query($koneksi,"
SELECT *
FROM pajak_sppt
WHERE id='$sppt_id'
"));

$petugas = mysqli_query($koneksi,"
SELECT *
FROM users
WHERE level='kadus'
AND status='aktif'
ORDER BY nama ASC
");

?>

<!DOCTYPE html>
<html>
<head>

<title>Pilih Petugas</title>

<style>

body{
    font-family:Arial;
    background:#f4f6f9;
}

.box{
    width:600px;
    margin:40px auto;
    background:white;
    padding:25px;
    border-radius:12px;
    box-shadow:0 0 15px rgba(0,0,0,.15);
}

table{
    width:100%;
}

td{
    padding:8px;
}

select{
    width:100%;
    padding:10px;
}

button{
    padding:10px 20px;
    background:#2563eb;
    color:white;
    border:none;
    border-radius:6px;
    cursor:pointer;
}

</style>

</head>

<body>

<div class="box">

<h2>Pembagian Petugas SPPT</h2>

<form action="simpan_petugas.php" method="POST">

<input type="hidden" name="sppt_id"
value="<?= $sppt['id'] ?>">

<table>

<tr>
<td>NOP</td>
<td><?= $sppt['nop'] ?></td>
</tr>

<tr>
<td>Nama</td>
<td><?= $sppt['nama_wajib_pajak'] ?></td>
</tr>

<tr>
<td>Pajak</td>
<td>
Rp <?= number_format($sppt['pajak_terhitung'],0,',','.') ?>
</td>
</tr>

<tr>
<td>Petugas</td>

<td>

<select name="pengelola_id" required>

<option value="">-- Pilih Kadus --</option>

<?php
while($p=mysqli_fetch_assoc($petugas)){
?>

<option value="<?= $p['id'] ?>">
<?= $p['nama'] ?>
</option>

<?php } ?>

</select>

</td>

</tr>

</table>

<br>

<button type="submit">
Simpan
</button>

<a href="pembagian_petugas.php">
Kembali
</a>

</form>

</div>

</body>
</html>