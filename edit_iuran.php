<?php
include 'koneksi.php';

$id = $_GET['id'];

$data = mysqli_query($conn,"
SELECT * FROM jenis_iuran
WHERE id='$id'
");

$d = mysqli_fetch_assoc($data);

if(isset($_POST['update'])){

    $nama_iuran = mysqli_real_escape_string(
        $conn,
        $_POST['nama_iuran']
    );

    $update = mysqli_query($conn,"
        UPDATE jenis_iuran
        SET nama_iuran='$nama_iuran'
        WHERE id='$id'
    ");

    if($update){

        echo "
        <script>
            alert('Data berhasil diperbarui');
            window.location='jenis_iuran.php';
        </script>
        ";

    }else{

        echo "
        <script>
            alert('Data gagal diperbarui');
        </script>
        ";

    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Edit Jenis Iuran</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

body{
    background:#f4f6f9;
    font-family:'Segoe UI',sans-serif;
}

.header-card{
    background:linear-gradient(135deg,#f59e0b,#fbbf24);
    color:white;
    border-radius:20px;
    padding:25px;
    box-shadow:0 5px 15px rgba(0,0,0,.15);
}

.form-card{
    background:white;
    border-radius:20px;
    padding:30px;
    margin-top:20px;
    box-shadow:0 3px 12px rgba(0,0,0,.08);
}

.form-control{
    border-radius:12px;
    padding:12px;
}

.btn{
    border-radius:12px;
}

label{
    font-weight:600;
}

</style>

</head>
<body>

<div class="container mt-4">

    <div class="header-card">

        <div class="d-flex justify-content-between align-items-center">

            <div>

                <h3 class="mb-1">
                    <i class="fa fa-edit"></i>
                    Edit Jenis Iuran
                </h3>

                <small>
                    Ubah data jenis iuran atau kegiatan
                </small>

            </div>

            <div>

                <a href="index.php"
                   class="btn btn-dark">

                    <i class="fa fa-home"></i>
                    Dashboard

                </a>

                <a href="jenis_iuran.php"
                   class="btn btn-light">

                    <i class="fa fa-arrow-left"></i>
                    Kembali

                </a>

            </div>

        </div>

    </div>

    <div class="form-card">

        <form method="POST">

            <div class="mb-3">

                <label>
                    Nama Iuran / Kegiatan
                </label>

                <input
                    type="text"
                    name="nama_iuran"
                    class="form-control"
                    value="<?= $d['nama_iuran']; ?>"
                    required>

            </div>

            <hr>

            <button
                type="submit"
                name="update"
                class="btn btn-warning">

                <i class="fa fa-save"></i>
                Simpan Perubahan

            </button>

            <a href="jenis_iuran.php"
               class="btn btn-secondary">

               Batal

            </a>

        </form>

    </div>

</div>

</body>
</html>