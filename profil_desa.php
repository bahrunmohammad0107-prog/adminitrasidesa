<?php
include "cek_login.php";
include "koneksi.php";

/* ==========================================
   AMBIL DATA PROFIL DESA
========================================== */

$query = mysqli_query($koneksi, "SELECT * FROM profil_desa LIMIT 1");

if(mysqli_num_rows($query) == 0){

    mysqli_query($koneksi,"
        INSERT INTO profil_desa
        (
            nama_desa,
            kecamatan,
            kabupaten,
            alamat,
            logo
        )
        VALUES
        (
            '',
            '',
            '',
            '',
            NULL
        )
    ");

    $query = mysqli_query($koneksi,"SELECT * FROM profil_desa LIMIT 1");

}

$data = mysqli_fetch_assoc($query);


/* ==========================================
   SIMPAN DATA
========================================== */

$pesan = "";

if(isset($_POST['simpan'])){

    $nama_desa = mysqli_real_escape_string(
        $koneksi,
        $_POST['nama_desa']
    );

    $kecamatan = mysqli_real_escape_string(
        $koneksi,
        $_POST['kecamatan']
    );

    $kabupaten = mysqli_real_escape_string(
        $koneksi,
        $_POST['kabupaten']
    );

    $alamat = mysqli_real_escape_string(
        $koneksi,
        $_POST['alamat']
    );

    $logo = $data['logo'];



    /* =============================
       UPLOAD LOGO
    ============================== */

    if(isset($_FILES['logo'])){

        if($_FILES['logo']['name'] != ""){

            $namaFile = $_FILES['logo']['name'];

            $tmp = $_FILES['logo']['tmp_name'];

            $ext = strtolower(
                pathinfo(
                    $namaFile,
                    PATHINFO_EXTENSION
                )
            );

            $boleh = array(
                "jpg",
                "jpeg",
                "png",
                "webp"
            );

            if(in_array($ext,$boleh)){

                if(!is_dir("img")){

                    mkdir("img");

                }

                $logo =
                "logo_".time().".".$ext;

                move_uploaded_file(
                    $tmp,
                    "img/".$logo
                );

            }

        }

    }



    mysqli_query($koneksi,"
        UPDATE profil_desa
        SET

        nama_desa='$nama_desa',

        kecamatan='$kecamatan',

        kabupaten='$kabupaten',

        alamat='$alamat',

        logo='$logo'

        WHERE id='".$data['id']."'
    ");



    $pesan = "Data berhasil disimpan.";


    $query = mysqli_query(
        $koneksi,
        "SELECT * FROM profil_desa LIMIT 1"
    );

    $data = mysqli_fetch_assoc($query);

}
?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Profil Desa</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>

body{
    background:#f4f6f9;
    font-family:Segoe UI,Tahoma,sans-serif;
}

.container-fluid{
    margin-left:240px;
    padding:30px;
}

.card{
    border:none;
    border-radius:18px;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}

.card-header{
    background:linear-gradient(135deg,#2563eb,#1d4ed8);
    color:white;
    border-radius:18px 18px 0 0!important;
    padding:20px;
}

.form-control{
    border-radius:10px;
}

.btn-primary{
    border-radius:10px;
    padding:10px 25px;
}

.logo-preview{
    width:150px;
    height:150px;
    border-radius:15px;
    border:1px solid #ddd;
    object-fit:contain;
    background:white;
    padding:10px;
}

@media(max-width:768px){

.container-fluid{

margin-left:0;
padding:15px;

}

}

</style>

</head>

<body>

<?php include "menu.php"; ?>

<div class="container-fluid">

<div class="card">

<div class="card-header">

<h3 class="mb-0">

<i class="bi bi-building"></i>

Profil Desa

</h3>

<small>
Pengaturan identitas desa yang akan tampil pada seluruh aplikasi.
</small>

</div>

<div class="card-body">

<?php if($pesan!=""){ ?>

<div class="alert alert-success">

<i class="bi bi-check-circle-fill"></i>

<?= $pesan ?>

</div>

<?php } ?>

<form method="POST" enctype="multipart/form-data">

<div class="row">

<div class="col-md-8">

<div class="mb-3">

<label class="form-label">

Nama Desa

</label>

<input
type="text"
name="nama_desa"
class="form-control"
value="<?= $data['nama_desa'] ?>"
required>

</div>

<div class="mb-3">

<label class="form-label">

Kecamatan

</label>

<input
type="text"
name="kecamatan"
class="form-control"
value="<?= $data['kecamatan'] ?>"
required>

</div>

<div class="mb-3">

<label class="form-label">

Kabupaten

</label>

<input
type="text"
name="kabupaten"
class="form-control"
value="<?= $data['kabupaten'] ?>"
required>

</div>

<div class="mb-3">

<label class="form-label">

Alamat

</label>

<textarea
name="alamat"
rows="4"
class="form-control"><?= $data['alamat'] ?></textarea>

</div>

</div>

<div class="col-md-4 text-center">
    <div class="mb-3">

<?php

if($data['logo'] != "" && file_exists("img/".$data['logo'])){

?>

<img
src="img/<?= $data['logo'] ?>"
class="logo-preview"
id="preview">

<?php

}else{

?>

<img
src="img/OIP.jpg"
class="logo-preview"
id="preview">

<?php } ?>

</div>


<div class="mb-3">

<label class="form-label">

Logo Desa

</label>

<input
type="file"
name="logo"
class="form-control"
accept=".jpg,.jpeg,.png,.webp"
onchange="previewLogo(event)">

<small class="text-muted">

Format : JPG, JPEG, PNG, WEBP

</small>

</div>

</div>

</div>

<hr>

<div class="text-end">

<button
type="submit"
name="simpan"
class="btn btn-primary">

<i class="bi bi-save"></i>

Simpan Perubahan

</button>

<a
href="dashboard.php"
class="btn btn-secondary">

<i class="bi bi-arrow-left"></i>

Kembali

</a>

</div>

</form>

</div>

</div>

</div>


<script>

function previewLogo(event){

const reader = new FileReader();

reader.onload = function(){

document.getElementById("preview").src = reader.result;

}

reader.readAsDataURL(event.target.files[0]);

}

</script>

</body>
</html>