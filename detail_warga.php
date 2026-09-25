<?php
include 'koneksi.php';

$id=$_GET['id'];
$data=mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT * FROM warga WHERE id=$id"));
?>

<h2>Detail Warga</h2>

<p>NIK: <?= $data['nik'] ?></p>
<p>Nama: <?= $data['nama'] ?></p>
<p>Alamat: <?= $data['alamat'] ?></p>
<p>RT: <?= $data['rt'] ?></p>
<p>RW: <?= $data['rw'] ?></p>

<a href="data_warga.php">Kembali</a>