<?php
include 'koneksi.php';

$id = $_POST['id'];
$nama = mysqli_real_escape_string($koneksi,$_POST['nama_iuran']);
$nominal = str_replace(".","",$_POST['nominal']);

mysqli_query($koneksi,"
UPDATE jenis_iuran
SET
nama_iuran='$nama',
nominal='$nominal'
WHERE id='$id'
");

header("Location: jenis_iuran.php");
exit;