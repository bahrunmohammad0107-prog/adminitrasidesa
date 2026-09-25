<?php
include 'koneksi.php';

/* ===================================
   AMBIL DATA DUSUN
=================================== */
$dusun = mysqli_query($koneksi,"
SELECT *
FROM dusun
ORDER BY nama_dusun ASC
");

/* ===================================
   SIMPAN DATA
=================================== */
if(isset($_POST['simpan'])){

    $nik       = mysqli_real_escape_string($koneksi,$_POST['nik']);
    $nama      = mysqli_real_escape_string($koneksi,$_POST['nama']);
    $alamat    = mysqli_real_escape_string($koneksi,$_POST['alamat']);
    $rt        = mysqli_real_escape_string($koneksi,$_POST['rt']);
    $rw        = mysqli_real_escape_string($koneksi,$_POST['rw']);
    $dusun_id  = (int)$_POST['dusun'];

    // Cek NIK
    $cek = mysqli_query($koneksi,"
    SELECT id
    FROM warga
    WHERE nik='$nik'
    ");

    if(mysqli_num_rows($cek)>0){

        header("Location:tambah_warga.php?type=warning&pesan=NIK sudah terdaftar.");
        exit;

    }

    mysqli_query($koneksi,"
    INSERT INTO warga
    (
        nik,
        nama,
        alamat,
        rt,
        rw,
        dusun_id
    )
    VALUES
    (
        '$nik',
        '$nama',
        '$alamat',
        '$rt',
        '$rw',
        '$dusun_id'
    )
    ");

    header("Location:data_warga.php?type=success&pesan=Data warga berhasil ditambahkan.");
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>

<meta charset="utf-8">

<title>Tambah Warga</title>

<style>

body{
    font-family:Segoe UI;
    background:#0f172a;
    margin:0;
    color:white;
}

.container{
    padding:25px;
}

.card{
    background:#111827;
    width:550px;
    margin:auto;
    border-radius:18px;
    padding:25px;
    box-shadow:0 20px 45px rgba(0,0,0,.45);
}

h2{
    text-align:center;
    margin-bottom:20px;
}

label{
    font-weight:bold;
    display:block;
    margin-top:12px;
}

input,
textarea,
select{

    width:100%;
    padding:11px;
    margin-top:6px;
    border:none;
    border-radius:10px;
    background:#1f2937;
    color:white;
    outline:none;
    box-sizing:border-box;

}

textarea{

    resize:vertical;
    min-height:90px;

}

.btn{

    width:100%;
    border:none;
    padding:13px;
    border-radius:10px;
    font-weight:bold;
    cursor:pointer;
    color:white;
    transition:.25s;
    margin-top:18px;

}

.btn:hover{

    transform:translateY(-2px);

}

.green{

    background:linear-gradient(135deg,#22c55e,#16a34a);

}

.gray{

    display:block;
    text-align:center;
    margin-top:10px;
    padding:12px;
    border-radius:10px;
    text-decoration:none;
    color:white;
    background:linear-gradient(135deg,#6b7280,#4b5563);

}

</style>

</head>
</head>

<body>

<?php include "notifikasi.php"; ?>

<div class="container">

<div class="card">

<h2>👥 TAMBAH DATA WARGA</h2>

<form method="POST">

<label>NIK</label>
<input
type="text"
name="nik"
placeholder="Masukkan NIK"
required>

<label>Nama Lengkap</label>
<input
type="text"
name="nama"
placeholder="Masukkan Nama Lengkap"
required>

<label>Alamat</label>
<textarea
name="alamat"
placeholder="Masukkan Alamat"
required></textarea>

<label>Dusun</label>

<select name="dusun" required>

<option value="">-- Pilih Dusun --</option>

<?php while($d=mysqli_fetch_assoc($dusun)){ ?>

<option value="<?= $d['id']; ?>">

<?= strtoupper($d['nama_dusun']); ?>

</option>

<?php } ?>

</select>

<label>RT</label>
<input
type="text"
name="rt"
placeholder="Contoh : 01">

<label>RW</label>
<input
type="text"
name="rw"
placeholder="Contoh : 03">

<button
class="btn green"
type="submit"
name="simpan">

💾 SIMPAN DATA WARGA

</button>

</form>

<a
href="data_warga.php"
class="gray">

⬅ Kembali ke Data Warga

</a>

</div>

</div>
</body>
</html>