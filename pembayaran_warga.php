
<?php

session_start();

include "cek_login.php";
include "koneksi.php";


$desa_id  = $_SESSION['desa_id'];
$level    = $_SESSION['level'];
$dusun_id = $_SESSION['dusun_id'];


$jenis = mysqli_query($koneksi,"SELECT * FROM jenis_iuran");

$cari = $_GET['cari'] ?? '';


$query = "
SELECT
    w.id,
    w.nik,
    w.nama,
    w.alamat,
    w.rt,
    w.rw,
    d.nama_dusun,
    CONCAT(w.rt,'/',w.rw) AS rt_rw
FROM warga w
LEFT JOIN dusun d ON w.dusun_id = d.id
WHERE w.desa_id='$desa_id'
";


/* FILTER KADUS */
if($level != "admin"){

    $query .= " AND w.dusun_id='$dusun_id'";

}


/* PENCARIAN */
if($cari != ''){

    $cari = mysqli_real_escape_string($koneksi,$cari);

    $query .= " AND (
        w.nama LIKE '%$cari%'
        OR w.nik LIKE '%$cari%'
    )";

}


$query .= " ORDER BY w.nama ASC";


$data = mysqli_query($koneksi,$query);


if(!$data){
    die(mysqli_error($koneksi));
}


$total_warga = mysqli_num_rows($data);

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Pembayaran Warga PRO</title>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
}

body{
font-family:'Segoe UI',sans-serif;
background:#eef2f7;
}

.topbar{
background:#2c3e50;
color:white;
padding:15px 25px;
font-size:24px;
font-weight:bold;
}

.container{
padding:25px;
}

.menu{
margin-bottom:20px;
}

.menu a{
text-decoration:none;
padding:10px 18px;
background:#34495e;
color:white;
border-radius:8px;
font-weight:bold;
}

.card{
background:white;
padding:25px;
border-radius:15px;
box-shadow:0 5px 15px rgba(0,0,0,.08);
}

.stat{
margin-bottom:20px;
}

.stat-box{
background:linear-gradient(135deg,#3498db,#2980b9);
color:white;
padding:20px;
border-radius:12px;
width:250px;
}

.stat-box h3{
font-size:30px;
}

.filter{
background:#f8fafc;
padding:15px;
border-radius:10px;
margin-bottom:20px;
}

.filter form{
display:flex;
gap:10px;
flex-wrap:wrap;
}

.filter input{
padding:10px;
border:1px solid #ddd;
border-radius:8px;
}

.btn{
border:none;
padding:10px 15px;
border-radius:8px;
cursor:pointer;
color:white;
font-weight:bold;
}

.btn-primary{
background:#3498db;
}

.btn-success{
background:#27ae60;
}

.btn-warning{
background:#f39c12;
}

.btn-danger{
background:#e74c3c;
}

.table-container{
overflow-x:auto;
}

table{
width:100%;
border-collapse:collapse;
}

th{
background:#2c3e50;
color:white;
padding:12px;
}

td{
padding:12px;
border-bottom:1px solid #eee;
text-align:center;
}

tr:hover{
background:#f8f9fa;
}

.aksi{
display:flex;
justify-content:center;
gap:5px;
flex-wrap:wrap;
}

.aksi a{
text-decoration:none;
}

.status-lunas{
background:#27ae60;
color:white;
padding:8px 15px;
border-radius:30px;
font-weight:bold;
display:inline-block;
white-space:nowrap;
}

.status-belum{
background:#e74c3c;
color:white;
padding:8px 15px;
border-radius:30px;
font-weight:bold;
display:inline-block;
white-space:nowrap;
}

.footer{
text-align:center;
margin-top:20px;
color:#777;
}

</style>
</head>

<body>

<div class="topbar">
💰 Aplikasi Iuran Warga PRO
</div>

<div class="container">

<div class="menu">
<a href="dashboard.php">🏠 Dashboard</a>
</div>

<div class="card">

<h2 style="margin-bottom:20px;">
💰 Pembayaran Warga
</h2>

<div class="stat">

<div class="stat-box">
<h3><?= $total_warga ?></h3>
<p>Total Warga</p>
</div>

</div>

<div class="filter">

<form method="GET">

<input
type="text"
name="cari"
placeholder="Cari Nama / NIK"
value="<?= $cari ?>"
>

<button type="submit" class="btn btn-primary">
🔍 Cari
</button>

</form>

</div>

<div class="table-container">

<table>

<tr>
<th>No</th>
<th>NIK</th>
<th>Nama</th>
<th>Dusun</th>
<th>Alamat</th>
<th>RT/RW</th>
<th>Status</th>
<th>Total Bayar</th>
<th>Aksi</th>
</tr>

<?php
$no=1;

while($row=mysqli_fetch_assoc($data)){

$id_warga = $row['id'];

$qBayar = mysqli_query($koneksi,"
SELECT COALESCE(SUM(jumlah),0) total_bayar
FROM pembayaran
WHERE warga_id='$id_warga'
");

$dBayar = mysqli_fetch_assoc($qBayar);

$total_bayar = $dBayar['total_bayar'];
?>

<tr>

<td><?= $no++ ?></td>

<td><?= $row['nik'] ?></td>

<td><?= $row['nama'] ?></td>

<td><?= $row['nama_dusun'] ?></td>

<td><?= $row['alamat'] ?></td>

<td><?= $row['rt_rw'] ?></td>

<td>

<?php if($total_bayar > 0){ ?>

<span class="status-lunas">
🟢 SUDAH BAYAR
</span>

<?php } else { ?>

<span class="status-belum">
🔴 BELUM BAYAR
</span>

<?php } ?>

</td>

<td style="font-weight:bold;color:#2980b9;">
Rp <?= number_format($total_bayar,0,',','.') ?>
</td>

<td>

<div class="aksi">

<a href="bayar.php?id=<?= $row['id'] ?>">
<button class="btn btn-success">
💰 Bayar
</button>
</a>

<a href="detail.php?id=<?= $row['id'] ?>">
<button class="btn btn-warning">
📄 Rincian
</button>
</a>

<a href="edit_pembayaran.php?warga_id=<?= $row['id'] ?>">
<button class="btn btn-primary">
✏ Edit Bayar
</button>
</a>

<a href="hapus_pembayaran.php?warga_id=<?= $row['id'] ?>"
onclick="return confirm('Yakin hapus seluruh pembayaran warga ini?')">

<button class="btn btn-danger">
🗑 Hapus Bayar
</button>

</a>

</div>

</td>

</tr>

<?php } ?>

</table>

</div>

</div>

<div class="footer">
© <?= date('Y') ?> Sistem Administrasi Iuran Warga
</div>

</div>

</body>
</html>
```
