<?php
include 'koneksi.php';

$warga_id = $_GET['warga_id'] ?? 0;

if($warga_id == 0){
    die("ID warga tidak ditemukan");
}

$cek = mysqli_query($koneksi,"
SELECT *
FROM pembayaran
WHERE warga_id='$warga_id'
");

if(mysqli_num_rows($cek) == 0){
?>

<!DOCTYPE html>

<html>
<head>
<meta charset="utf-8">
<title>Tidak Ada Transaksi</title>

<style>

body{
    font-family:'Segoe UI',sans-serif;
    background:#eef2f7;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}

.card{
    background:white;
    width:500px;
    padding:35px;
    border-radius:20px;
    text-align:center;
    box-shadow:0 10px 30px rgba(0,0,0,.1);
}

.btn{
    display:inline-block;
    margin-top:20px;
    padding:12px 25px;
    background:#3498db;
    color:white;
    text-decoration:none;
    border-radius:10px;
    font-weight:bold;
}

</style>

</head>
<body>

<div class="card">

<h2>📋 Tidak Ada Transaksi</h2>

<p>
Warga ini belum memiliki data pembayaran yang dapat dihapus.
</p>

<a href="pembayaran_warga.php" class="btn">
⬅ Kembali
</a>

</div>

</body>
</html>
<?php
exit;
}

mysqli_query($koneksi,"
DELETE FROM pembayaran
WHERE warga_id='$warga_id'
");

?>

<!DOCTYPE html>

<html>
<head>
<meta charset="utf-8">
<title>Berhasil</title>

<style>

body{
    font-family:'Segoe UI',sans-serif;
    background:#eef2f7;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}

.card{
    background:white;
    width:500px;
    padding:35px;
    border-radius:20px;
    text-align:center;
    box-shadow:0 10px 30px rgba(0,0,0,.1);
}

.btn{
    display:inline-block;
    margin-top:20px;
    padding:12px 25px;
    background:#27ae60;
    color:white;
    text-decoration:none;
    border-radius:10px;
    font-weight:bold;
}

</style>

</head>
<body>

<div class="card">

<h2>✅ Berhasil</h2>

<p>
Seluruh transaksi pembayaran warga berhasil dihapus.
</p>

<a href="pembayaran_warga.php" class="btn">
⬅ Kembali ke Daftar
</a>

</div>

</body>
</html>
