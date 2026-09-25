
<?php
include "../cek_login.php";
include "../koneksi.php";

$desa_id = $_SESSION['desa_id'];

$data = mysqli_query($koneksi,"
SELECT *
FROM pajak_sppt
WHERE desa_id='$desa_id'
ORDER BY nama_wajib_pajak ASC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pembagian Petugas SPPT</title>

    <style>
        body{
            font-family:Arial;
            background:#f5f5f5;
            margin:20px;
        }

        h2{
            color:#1e3a8a;
        }

        table{
            width:100%;
            border-collapse:collapse;
            background:#fff;
        }

        table th, table td{
            border:1px solid #ddd;
            padding:10px;
        }

        table th{
            background:#2563eb;
            color:#fff;
        }

        .btn{
            background:#2563eb;
            color:white;
            padding:6px 12px;
            text-decoration:none;
            border-radius:5px;
        }
    </style>

</head>
<body>

<h2>📋 Pembagian Petugas SPPT</h2>

<table>

<tr>
    <th>No</th>
    <th>NOP</th>
    <th>Nama Wajib Pajak</th>
    <th>Pajak</th>
    <th>Petugas</th>
    <th>Aksi</th>
</tr>

<?php
$no=1;

while($d=mysqli_fetch_assoc($data)){
?>

<tr>

<td><?= $no++ ?></td>

<td><?= $d['nop'] ?></td>

<td><?= $d['nama_wajib_pajak'] ?></td>

<td align="right">
Rp <?= number_format($d['pajak_terhitung'],0,',','.') ?>
</td>

<td>
Belum Ditentukan
</td>

<td>
<a href="pilih_petugas.php?id=<?= $d['id'] ?>" class="btn">
Pilih
</a>
</td>

</tr>

<?php } ?>

</table>

<br>

<a href="index.php">← Kembali Dashboard Pajak</a>

</body>
</html>