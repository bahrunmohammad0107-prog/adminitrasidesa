<?php
include 'koneksi.php';

$warga_id = $_GET['warga_id'] ?? 0;

$data = mysqli_fetch_assoc(mysqli_query($koneksi,"
SELECT *
FROM pembayaran
WHERE warga_id='$warga_id'
ORDER BY id DESC
LIMIT 1
"));

if(!$data){
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Belum Ada Pembayaran</title>

<style>

body{
    margin:0;
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
    padding:40px;
    border-radius:20px;
    text-align:center;
    box-shadow:0 10px 30px rgba(0,0,0,.1);
}

.icon{
    font-size:80px;
    margin-bottom:15px;
}

h2{
    color:#2c3e50;
    margin-bottom:10px;
}

p{
    color:#7f8c8d;
    margin-bottom:25px;
    line-height:1.6;
}

.btn{
    display:inline-block;
    padding:12px 25px;
    background:#3498db;
    color:white;
    text-decoration:none;
    border-radius:10px;
    font-weight:bold;
}

.btn:hover{
    background:#2980b9;
}

</style>

</head>
<body>

<div class="card">

<div class="icon">
📋
</div>

<h2>Belum Ada Transaksi</h2>

<p>
Warga ini belum memiliki data pembayaran iuran.
Silakan lakukan pembayaran terlebih dahulu sebelum melakukan edit transaksi.
</p>

<a href="bayar.php?id=<?= $warga_id ?>" class="btn">
💰 Input Pembayaran
</a>

<br><br>

<a href="pembayaran_warga.php" style="color:#7f8c8d;text-decoration:none;">
⬅ Kembali ke Daftar Warga
</a>

</div>

</body>
</html>
<?php
exit;
}
$id = $data['id'];

if(isset($_POST['update'])){

    $jenis_iuran_id = $_POST['jenis_iuran_id'];
    $jumlah = $_POST['jumlah'];
    $tanggal_bayar = $_POST['tanggal_bayar'];

    $bulan = date('m', strtotime($tanggal_bayar));
    $tahun = date('Y', strtotime($tanggal_bayar));

    mysqli_query($koneksi,"
    UPDATE pembayaran SET
        jenis_iuran_id='$jenis_iuran_id',
        jumlah='$jumlah',
        tanggal_bayar='$tanggal_bayar',
        bulan='$bulan',
        tahun='$tahun'
    WHERE id='$id'
    ");

    echo "
    <script>
    alert('Pembayaran berhasil diupdate');
    window.location='pembayaran_warga.php';
    </script>
    ";
    exit;
}

$jenis = mysqli_query($koneksi,"
SELECT *
FROM jenis_iuran
ORDER BY nama_iuran ASC
");
?>

<!DOCTYPE html>

<html>
<head>
<meta charset="utf-8">
<title>Edit Pembayaran</title>

<style>

body{
    background:#eef2f7;
    font-family:'Segoe UI',sans-serif;
}

.card{
    width:700px;
    margin:30px auto;
    background:white;
    padding:25px;
    border-radius:15px;
    box-shadow:0 5px 15px rgba(0,0,0,.08);
}

h2{
    color:#2c3e50;
    margin-bottom:20px;
}

.form-group{
    margin-bottom:15px;
}

label{
    display:block;
    margin-bottom:5px;
    font-weight:bold;
}

input,select{
    width:100%;
    padding:12px;
    border:1px solid #ddd;
    border-radius:8px;
}

.btn{
    border:none;
    padding:12px 18px;
    border-radius:8px;
    cursor:pointer;
    color:white;
    font-weight:bold;
}

.simpan{
    background:#27ae60;
}

.kembali{
    background:#34495e;
    text-decoration:none;
    display:inline-block;
}

.info{
    background:#ecf0f1;
    padding:12px;
    border-radius:8px;
    margin-bottom:20px;
}

</style>

</head>
<body>

<div class="card">

<h2>✏ Edit Pembayaran</h2>

<div class="info">
<b>ID Pembayaran :</b> <?= $data['id'] ?>
</div>

<form method="POST">

<div class="form-group">
<label>Jenis Iuran</label>

<select name="jenis_iuran_id" required>

<?php while($j=mysqli_fetch_assoc($jenis)){ ?>

<option
value="<?= $j['id'] ?>"
<?= ($data['jenis_iuran_id']==$j['id']) ? 'selected' : '' ?>>

<?= $j['nama_iuran'] ?>

</option>

<?php } ?>

</select>
</div>

<div class="form-group">
<label>Jumlah Bayar</label>

<input
type="number"
name="jumlah"
value="<?= $data['jumlah'] ?>"
required>

</div>

<div class="form-group">
<label>Tanggal Bayar</label>

<input
type="date"
name="tanggal_bayar"
value="<?= $data['tanggal_bayar'] ?>"
required>

</div>

<button
type="submit"
name="update"
class="btn simpan">

💾 Update Pembayaran

</button>

<a
href="pembayaran_warga.php"
class="btn kembali">

⬅ Kembali

</a>

</form>

</div>

</body>
</html>
