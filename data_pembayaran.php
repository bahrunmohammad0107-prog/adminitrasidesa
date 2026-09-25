<?php
include 'koneksi.php';

$data = mysqli_query($koneksi,"
SELECT
    p.*,
    w.nama,
    j.nama_iuran
FROM pembayaran p
LEFT JOIN warga w ON p.warga_id = w.id
LEFT JOIN jenis_iuran j ON p.jenis_iuran_id = j.id
ORDER BY p.id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Data Pembayaran</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
.card-box{
    background:white;
    padding:20px;
    border-radius:15px;
    box-shadow:0 2px 10px rgba(0,0,0,.1);
}

.header{
    background:linear-gradient(135deg,#198754,#20c997);
    color:white;
    padding:20px;
    border-radius:15px;
}
</style>

</head>
<body>

<div class="container mt-4">

<div class="header">
    <h3>📋 DATA PEMBAYARAN IURAN</h3>
</div>

<br>

<a href="pembayaran_iuran.php" class="btn btn-success">
➕ Input Pembayaran
</a>

<br><br>

<div class="card-box">

<table class="table table-bordered table-striped">

<tr>
    <th>No</th>
    <th>Tanggal</th>
    <th>Nama</th>
    <th>Jenis Iuran</th>
    <th>Jumlah</th>
    <th>Status</th>
    <th width="220">Aksi</th>
</tr>

<?php
$no=1;
while($d=mysqli_fetch_array($data)){
?>

<tr>

<td><?= $no++; ?></td>

<td><?= $d['tanggal_bayar']; ?></td>

<td><?= $d['nama']; ?></td>

<td><?= $d['nama_iuran']; ?></td>

<td>
Rp <?= number_format($d['jumlah']); ?>
</td>

<td>
<span class="badge bg-success">
<?= $d['status']; ?>
</span>
</td>

<td>

<a href="detail_pembayaran.php?id=<?= $d['id']; ?>"
class="btn btn-info btn-sm">
👁 Detail
</a>

<a href="edit_pembayaran.php?id=<?= $d['id']; ?>"
class="btn btn-warning btn-sm">
✏ Edit
</a>

<a href="struk_pembayaran.php?id=<?= $d['id']; ?>"
class="btn btn-primary btn-sm">
🖨 Struk
</a>

<a href="hapus_pembayaran.php?id=<?= $d['id']; ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Hapus pembayaran ini?')">
🗑 Hapus
</a>

</td>

</tr>

<?php } ?>

</table>

</div>

</div>

</body>
</html>