<?php
include 'koneksi.php';

$warga = mysqli_query($koneksi,"SELECT * FROM warga ORDER BY nama ASC");
$jenis = mysqli_query($koneksi,"SELECT * FROM jenis_iuran ORDER BY nama_iuran ASC");

if(isset($_POST['simpan'])){
    $warga_id = $_POST['warga_id'];
    $jenis_iuran_id = $_POST['jenis_iuran_id'];
    $tanggal_bayar = $_POST['tanggal_bayar'];
    $jumlah = $_POST['jumlah'];

    mysqli_query($koneksi,"
        INSERT INTO pembayaran 
        (warga_id, jenis_iuran_id, tanggal_bayar, jumlah)
        VALUES
        ('$warga_id','$jenis_iuran_id','$tanggal_bayar','$jumlah')
    ");

    echo "<script>
    alert('Pembayaran berhasil');
    window.location='pembayaran_iuran.php';
    </script>";
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Pembayaran Iuran PRO</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
.card{border-radius:15px;}
.header{
    background:linear-gradient(135deg,#28a745,#20c997);
    color:white;
    padding:15px;
    border-radius:15px;
}
</style>
</head>

<body>

<div class="container mt-4">

<div class="header">
<h3>💰 PEMBAYARAN IURAN PRO</h3>
</div>

<br>

<a href="jenis_iuran.php" class="btn btn-dark">⚙ Kelola Jenis Iuran</a>
<a href="data_warga.php" class="btn btn-secondary">⬅ Warga</a>

<br><br>

<div class="card p-4">

<form method="POST">

<div class="row">

<div class="col-md-4">
<label>Warga</label>
<select name="warga_id" class="form-control" required>
<option value="">-- pilih --</option>
<?php while($w=mysqli_fetch_array($warga)){ ?>
<option value="<?= $w['id']; ?>">
<?= $w['nama']; ?>
</option>
<?php } ?>
</select>
</div>

<div class="col-md-4">
<label>Jenis Iuran</label>
<select name="jenis_iuran_id" class="form-control" required>
<option value="">-- pilih --</option>
<?php while($j=mysqli_fetch_array($jenis)){ ?>
<option value="<?= $j['id']; ?>">
<?= $j['nama_iuran']; ?>
</option>
<?php } ?>
</select>
</div>

<div class="col-md-4">
<label>Tanggal Bayar</label>
<input type="date" name="tanggal_bayar" class="form-control" required>
</div>

</div>

<br>

<div class="mb-3">
<label>Jumlah (Rp)</label>
<input type="number" name="jumlah" class="form-control" required>
</div>

<button class="btn btn-success">💾 Simpan Pembayaran</button>

</form>

</div>

</div>

</body>
</html>