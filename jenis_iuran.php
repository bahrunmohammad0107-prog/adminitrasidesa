<?php
include 'koneksi.php';

/*=====================================
=            SIMPAN DATA              =
=====================================*/
if(isset($_POST['simpan'])){

    $nama = mysqli_real_escape_string($koneksi,$_POST['nama_iuran']);
    $tipe = $_POST['tipe'];

    if($tipe=="TETAP"){
        $nominal = str_replace(".","",$_POST['nominal']);
    }else{
        $nominal = 0;
    }

    mysqli_query($koneksi,"
    INSERT INTO jenis_iuran
    (nama_iuran,nominal,tipe)
    VALUES
    ('$nama','$nominal','$tipe')
    ");

    
    header("Location: jenis_iuran.php?type=success&pesan=Data berhasil disimpan");
exit;
}
/*=====================================
=            EDIT DATA                =
=====================================*/

if(isset($_POST['update'])){

    $id   = $_POST['id'];
    $nama = mysqli_real_escape_string($koneksi,$_POST['nama_iuran']);
    $tipe = $_POST['tipe'];

    if($tipe=="TETAP"){
        $nominal = str_replace(".","",$_POST['nominal']);
    }else{
        $nominal = 0;
    }

    mysqli_query($koneksi,"
    UPDATE jenis_iuran
    SET
        nama_iuran='$nama',
        nominal='$nominal',
        tipe='$tipe'
    WHERE id='$id'
    ");

    header("Location: jenis_iuran.php?type=success&pesan=Data berhasil diubah");
exit;

}


/*=====================================
=            HAPUS DATA               =
=====================================*/

if(isset($_GET['hapus'])){

    mysqli_query($koneksi,"
    DELETE FROM jenis_iuran
    WHERE id='$_GET[hapus]'
    ");

    header("Location: jenis_iuran.php?type=success&pesan=Data berhasil dihapus");
exit;

}


/*=====================================
=            STATISTIK                =
=====================================*/

$totalJenis = mysqli_fetch_assoc(mysqli_query($koneksi,"
SELECT COUNT(*) total
FROM jenis_iuran
"));

$totalNominal = mysqli_fetch_assoc(mysqli_query($koneksi,"
SELECT SUM(nominal) total
FROM jenis_iuran
"));

$cari = $_GET['cari'] ?? '';

$data = mysqli_query($koneksi,"
SELECT *
FROM jenis_iuran
WHERE nama_iuran LIKE '%$cari%'
ORDER BY id DESC
");

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1">

<title>Master Jenis Iuran</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">

<style>

body{

background:#edf2f7;

font-family:'Segoe UI',sans-serif;

}

.header{

background:linear-gradient(135deg,#2563eb,#7c3aed);

padding:25px;

border-radius:20px;

color:white;

box-shadow:0 15px 40px rgba(0,0,0,.15);

margin-bottom:25px;

}

.header h2{

font-weight:bold;

margin:0;

}

.header p{

margin-top:5px;

opacity:.9;

}

.info-card{

border:none;

border-radius:18px;

color:white;

overflow:hidden;

transition:.3s;

}

.info-card:hover{

transform:translateY(-6px);

}

.bg-blue{

background:linear-gradient(135deg,#2563eb,#60a5fa);

}

.bg-green{

background:linear-gradient(135deg,#16a34a,#4ade80);

}

.bg-orange{

background:linear-gradient(135deg,#ea580c,#fb923c);

}

.info-card .card-body{

position:relative;

padding:25px;

}

.info-card i{

position:absolute;

right:20px;

top:20px;

font-size:45px;

opacity:.25;

}

.info-title{

font-size:13px;

text-transform:uppercase;

}

.info-value{

font-size:30px;

font-weight:bold;

}

.box{

background:white;

border-radius:20px;

padding:20px;

box-shadow:0 8px 30px rgba(0,0,0,.08);

}

.btn-radius{

border-radius:12px;

}

.table thead{

background:#2563eb;

color:white;

}

.table tbody tr:hover{

background:#eef6ff;

}

.badge-nominal{

font-size:15px;

padding:8px 14px;

}

</style>

</head>

<body>
<?php include "notifikasi.php"; ?>   

<div class="container-fluid mt-4">

<div class="header">

<div class="d-flex justify-content-between align-items-center">

<div>

<h2>

<i class="fas fa-wallet"></i>

MASTER JENIS IURAN

</h2>

<p>

Kelola seluruh jenis iuran warga

</p>

</div>

<a
href="dashboard.php"
class="btn btn-light btn-radius">

<i class="fas fa-arrow-left"></i>

Dashboard

</a>

</div>

</div>

<div class="row mb-4">

<div class="col-md-4">

<div class="card info-card bg-blue">

<div class="card-body">

<i class="fas fa-layer-group"></i>

<div class="info-title">

Jumlah Jenis Iuran

</div>

<div class="info-value">

<?= $totalJenis['total']; ?>

</div>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card info-card bg-green">

<div class="card-body">

<i class="fas fa-money-bill-wave"></i>

<div class="info-title">

Total Nominal

</div>

<div class="info-value">

Rp <?= number_format($totalNominal['total'] ?? 0,0,',','.'); ?>

</div>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card info-card bg-orange">

<div class="card-body">

<i class="fas fa-circle-check"></i>

<div class="info-title">

Status

</div>

<div class="info-value">

AKTIF

</div>

</div>

</div>

</div>

</div>

<div class="box">

<div class="d-flex justify-content-between mb-3">

<form method="GET" class="w-25">

<div class="input-group">

<input
type="text"
name="cari"
class="form-control"
placeholder="Cari Jenis Iuran..."
value="<?= $cari ?>">

<button class="btn btn-primary">

<i class="fas fa-search"></i>

</button>

</div>

</form>

<button
class="btn btn-success btn-radius"
data-bs-toggle="modal"
data-bs-target="#modalTambah">

<i class="fas fa-plus-circle"></i>

Tambah Jenis Iuran

</button>

</div>

<table class="table table-bordered table-hover align-middle">

<thead>

<tr>

<th width="60">No</th>

<th>Nama Iuran</th>

<th width="160">Tipe</th>

<th width="180">Nominal</th>

<th width="170">Aksi</th>

</tr>

</thead>

<tbody>

<?php
$no=1;
while($d=mysqli_fetch_assoc($data)){
?>
<tr>

    <td><?= $no++; ?></td>

    <td>
        <b><?= strtoupper($d['nama_iuran'] ?? ''); ?></b>
    </td>

    <td align="center">

        <?php if($d['tipe']=="TETAP"){ ?>

            <span class="badge bg-primary">
                NOMINAL TETAP
            </span>

        <?php }else{ ?>

            <span class="badge bg-warning text-dark">
                FLEKSIBEL
            </span>

        <?php } ?>

    </td>

    <td align="center">

        <?php if($d['tipe']=="TETAP"){ ?>

            <span class="badge bg-success badge-nominal">

                Rp <?= number_format($d['nominal'],0,',','.'); ?>

            </span>

        <?php }else{ ?>

            <span class="badge bg-secondary badge-nominal">

                DIISI SAAT PEMBAYARAN

            </span>

        <?php } ?>

    </td>

    <td align="center">

        <button
        class="btn btn-warning btn-sm btn-radius"
        data-bs-toggle="modal"
        data-bs-target="#edit<?= $d['id']; ?>">

            <i class="fas fa-edit"></i>

        </button>

        <a
        href="?hapus=<?= $d['id']; ?>"
        class="btn btn-danger btn-sm btn-radius"
        onclick="return confirm('Yakin ingin menghapus data ini ?')">

            <i class="fas fa-trash"></i>

        </a>

    </td>

</tr>

<!-- MODAL EDIT -->

<div
class="modal fade"
id="edit<?= $d['id']; ?>"
tabindex="-1">

<div class="modal-dialog">

<div class="modal-content">

<form method="POST">

<div class="modal-header bg-warning">

<h5 class="modal-title">

<i class="fas fa-edit"></i>

Edit Jenis Iuran

</h5>

<button
type="button"
class="btn-close"
data-bs-dismiss="modal">
</button>

</div>

<div class="modal-body">

<input
type="hidden"
name="id"
value="<?= $d['id']; ?>">

<div class="mb-3">

<label>Nama Iuran</label>

<input
type="text"
name="nama_iuran"
class="form-control"
value="<?= $d['nama_iuran']; ?>"
required>

</div>

<div class="mb-3">

<label>Tipe Iuran</label>

<select
name="tipe"
class="form-select tipe-edit"
data-target="nominal<?= $d['id']; ?>">

<option
value="TETAP"
<?= $d['tipe']=="TETAP" ? "selected" : "" ?>>

Nominal Tetap

</option>

<option
value="FLEKSIBEL"
<?= $d['tipe']=="FLEKSIBEL" ? "selected" : "" ?>>

Nominal Fleksibel

</option>

</select>

</div>

<div
class="mb-3"
id="nominal<?= $d['id']; ?>">

<label>Nominal</label>

<input
type="number"
name="nominal"
class="form-control"
value="<?= $d['nominal']; ?>">

</div>

</div>

<div class="modal-footer">

<button
type="submit"
name="update"
class="btn btn-primary">

<i class="fas fa-save"></i>

Simpan Perubahan

</button>

</div>

</form>

</div>

</div>

</div>

<?php } ?>

</tbody>

</table>

</div>

<!-- ===========================
     MODAL TAMBAH
============================ -->

<div
class="modal fade"
id="modalTambah"
tabindex="-1">

<div class="modal-dialog">

<div class="modal-content">

<form method="POST">

<div class="modal-header bg-success text-white">

<h5 class="modal-title">

<i class="fas fa-plus-circle"></i>

Tambah Jenis Iuran

</h5>

<button
type="button"
class="btn-close btn-close-white"
data-bs-dismiss="modal">
</button>

</div>

<div class="modal-body">

<div class="mb-3">

<label>Nama Iuran</label>

<input
type="text"
name="nama_iuran"
class="form-control"
placeholder="Contoh : Iuran Kebersihan"
required>

</div>

<div class="mb-3">

<label>Tipe Iuran</label>

<select
name="tipe"
class="form-select"
id="tipe">

<option value="TETAP">

Nominal Tetap

</option>

<option value="FLEKSIBEL">

Nominal Fleksibel

</option>

</select>

</div>

<div
class="mb-3"
id="boxNominal">

<label>Nominal</label>

<input
type="number"
name="nominal"
class="form-control"
placeholder="Contoh : 25000">

</div>

</div>

<div class="modal-footer">

<button
type="submit"
name="simpan"
class="btn btn-success">

<i class="fas fa-save"></i>

Simpan Data

</button>

</div>

</form>

</div>

</div>

</div>
</div>
<!-- END CONTAINER -->

<footer class="text-center mt-4 mb-3 text-muted">

<hr>

<b>Sistem Administrasi Iuran Warga</b>

<br>

© <?= date('Y'); ?> All Rights Reserved

</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>

// ==============================
// TAMBAH DATA
// ==============================

const tipeTambah = document.getElementById("tipe");
const boxNominal = document.getElementById("boxNominal");

function cekTambah(){

    if(tipeTambah.value=="FLEKSIBEL"){

        boxNominal.style.display="none";

    }else{

        boxNominal.style.display="block";

    }

}

cekTambah();

tipeTambah.addEventListener("change",cekTambah);


// ==============================
// EDIT DATA
// ==============================

document.querySelectorAll(".tipe-edit").forEach(function(item){

    function tampilkan(){

        let target=item.dataset.target;

        let box=document.getElementById(target);

        if(item.value=="FLEKSIBEL"){

            box.style.display="none";

        }else{

            box.style.display="block";

        }

    }

    tampilkan();

    item.addEventListener("change",tampilkan);

});


// ==============================
// FORMAT RUPIAH
// ==============================

document.querySelectorAll("input[name='nominal']").forEach(function(input){

    input.addEventListener("keyup",function(){

        let angka=this.value.replace(/\D/g,'');

        this.value=angka;

    });

});


// ==============================
// KONFIRMASI HAPUS
// ==============================

document.querySelectorAll("a[href*='hapus']").forEach(function(btn){

    btn.onclick=function(){

        return confirm("Apakah Anda yakin ingin menghapus jenis iuran ini ?");

    }

});


// ==============================
// ANIMASI CARD
// ==============================

document.querySelectorAll(".info-card").forEach(function(card){

    card.addEventListener("mouseenter",function(){

        this.style.transform="translateY(-8px)";
        this.style.transition=".3s";

    });

    card.addEventListener("mouseleave",function(){

        this.style.transform="translateY(0px)";

    });

});


// ==============================
// HOVER TABLE
// ==============================

document.querySelectorAll("tbody tr").forEach(function(row){

    row.addEventListener("mouseenter",function(){

        this.style.background="#eef7ff";

    });

    row.addEventListener("mouseleave",function(){

        this.style.background="white";

    });

});

</script>

</body>
</html>