<?php
include 'koneksi.php';

$id_warga = $_GET['id'] ?? 0;

$warga = mysqli_fetch_assoc(
    mysqli_query($koneksi,"
        SELECT * FROM warga
        WHERE id='$id_warga'
    ")
);

if(!$warga){
    die("Data warga tidak ditemukan!");
}

if(isset($_POST['simpan'])){

    $warga_id = $_POST['warga_id'];
    $jenis_iuran_id = $_POST['jenis_iuran_id'];
    $bulan = $_POST['bulan'];
    $tahun = $_POST['tahun'];
    $jumlah = $_POST['jumlah'];

    mysqli_query($koneksi,"
        INSERT INTO pembayaran
        (
            warga_id,
            bulan,
            tahun,
            jumlah,
            status,
            jenis_iuran_id
        )
        VALUES
        (
            '$warga_id',
            '$bulan',
            '$tahun',
            '$jumlah',
            'LUNAS',
            '$jenis_iuran_id'
        )
    ");

    echo "
    <script>
        alert('Pembayaran berhasil disimpan');
        window.location='pembayaran_warga.php';
    </script>
    ";
}

$jenis_iuran = mysqli_query($koneksi,"
    SELECT *
    FROM jenis_iuran
");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Pembayaran Iuran</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    background:#eef2f7;
    font-family:'Segoe UI',sans-serif;
}

.container{
    width:95%;
    max-width:900px;
    margin:30px auto;
}

.card{
    background:white;
    border-radius:15px;
    padding:25px;
    box-shadow:0 5px 20px rgba(0,0,0,.08);
}

.header{
    margin-bottom:25px;
}

.header h2{
    color:#2c3e50;
}

.info{
    background:#f8f9fa;
    padding:15px;
    border-radius:10px;
    margin-bottom:20px;
}

.info table{
    width:100%;
}

.info td{
    padding:5px;
}

.form-group{
    margin-bottom:15px;
}

label{
    display:block;
    margin-bottom:5px;
    font-weight:bold;
}

input,
select{
    width:100%;
    padding:12px;
    border:1px solid #ddd;
    border-radius:8px;
}

.btn{
    border:none;
    padding:12px 20px;
    border-radius:8px;
    cursor:pointer;
    font-weight:bold;
}

.btn-success{
    background:#27ae60;
    color:white;
}

.btn-secondary{
    background:#34495e;
    color:white;
    text-decoration:none;
    padding:12px 20px;
    border-radius:8px;
}

.row{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:15px;
}

.nominal-box{
    background:#e8fff1;
    border:2px solid #27ae60;
    padding:15px;
    border-radius:10px;
    margin-bottom:20px;
    text-align:center;
    font-size:22px;
    font-weight:bold;
    color:#27ae60;
}

.button-area{
    margin-top:20px;
    display:flex;
    gap:10px;
}

</style>
</head>

<body>

<div class="container">

<div class="card">

<div class="header">
    <h2>💰 Form Pembayaran Iuran</h2>
</div>

<div class="info">

<table>
<tr>
    <td width="150">Nama</td>
    <td>: <?= $warga['nama'] ?></td>
</tr>

<tr>
    <td>NIK</td>
    <td>: <?= $warga['nik'] ?></td>
</tr>

<tr>
    <td>Alamat</td>
    <td>: <?= $warga['alamat'] ?></td>
</tr>

<tr>
    <td>RT/RW</td>
    <td>: <?= $warga['rt'] ?>/<?= $warga['rw'] ?></td>
</tr>
</table>

</div>

<form method="POST">

<input type="hidden"
       name="warga_id"
       value="<?= $warga['id'] ?>">

<div class="form-group">

<label>Jenis Iuran</label>

<select name="jenis_iuran_id"
        id="jenis_iuran"
        onchange="updateNominal()"
        required>

<option value="">-- Pilih Iuran --</option>

<?php while($j=mysqli_fetch_assoc($jenis_iuran)){ ?>

<option
value="<?= $j['id'] ?>"
data-nominal="<?= $j['nominal'] ?>">

<?= $j['nama_iuran'] ?>
(Rp <?= number_format($j['nominal'] ?? 0) ?>)

</option>

<?php } ?>

</select>

</div>

<div class="nominal-box">
Rp <span id="nominal_text">0</span>
</div>

<div class="row">

<div class="form-group">

<div class="form-group">

<label>Tanggal Bayar</label>

<input
    type="date"
    name="tanggal_bayar"
    value="<?= date('Y-m-d') ?>"
    required>

</div>
<div class="form-group">

<label>Tahun</label>

<select name="tahun">

<?php
for($i=date('Y');$i<=2035;$i++){
echo "<option>$i</option>";
}
?>

</select>

</div>

</div>

<div class="form-group">

<label>Jumlah Bayar</label>

<input type="number"
       name="jumlah"
       id="jumlah"
       required>

</div>

<div class="button-area">

<button type="submit"
        name="simpan"
        class="btn btn-success">

💾 Simpan Pembayaran

</button>

<a href="pembayaran_warga.php"
   class="btn-secondary">

⬅ Kembali

</a>

</div>

</form>

</div>

</div>

<script>

function updateNominal(){

    let select =
        document.getElementById('jenis_iuran');

    let nominal =
        select.options[
        select.selectedIndex
        ].getAttribute('data-nominal');

    if(!nominal){
        nominal = 0;
    }

    document.getElementById(
        'jumlah'
    ).value = nominal;

    document.getElementById(
        'nominal_text'
    ).innerHTML =
    Number(nominal).toLocaleString('id-ID');

}

</script>

</body>
</html>