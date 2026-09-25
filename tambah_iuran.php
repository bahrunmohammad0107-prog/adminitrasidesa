<?php
include 'koneksi.php';

if(isset($_POST['simpan'])){

    $nama_iuran = mysqli_real_escape_string($conn, $_POST['nama_iuran']);
    $nominal    = $_POST['nominal'];

    if($nominal == ''){
        $nominal = "NULL";
    }

    $query = mysqli_query($conn,"
        INSERT INTO jenis_iuran (nama_iuran, nominal)
        VALUES ('$nama_iuran', $nominal)
    ");

    if($query){
        echo "<script>
            alert('Jenis iuran berhasil ditambahkan');
            window.location='jenis_iuran.php';
        </script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Jenis Iuran</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body{
            background: linear-gradient(135deg,#0d6efd,#6f42c1);
            height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
        }

        .card-custom{
            width:100%;
            max-width:520px;
            border-radius:20px;
            border:none;
            box-shadow:0 10px 30px rgba(0,0,0,0.2);
            overflow:hidden;
        }

        .card-header{
            background: linear-gradient(135deg,#198754,#20c997);
            color:white;
            text-align:center;
            padding:20px;
            font-size:20px;
            font-weight:bold;
        }

        .form-control{
            border-radius:12px;
            padding:12px;
        }

        .btn{
            border-radius:12px;
            padding:10px 20px;
        }

        .btn-success{
            background: linear-gradient(135deg,#198754,#20c997);
            border:none;
        }

        .btn-secondary{
            border-radius:12px;
        }

        .footer-note{
            font-size:12px;
            color:#888;
            text-align:center;
            margin-top:10px;
        }
    </style>
</head>

<body>

<div class="card card-custom">

    <div class="card-header">
        ➕ Tambah Jenis Iuran
    </div>

    <div class="card-body p-4">

        <form method="POST">

            <div class="mb-3">
                <label class="form-label">Nama Iuran</label>
                <input type="text" name="nama_iuran"
                       class="form-control"
                       placeholder="Contoh: IURAN HUT 2026"
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label">Nominal (opsional)</label>
                <input type="number" name="nominal"
                       class="form-control"``````Warga
                       placeholder="Kosongkan jika fleksibel">
            </div>

            <div class="d-flex justify-content-between mt-4">

                <a href="jenis_iuran.php" class="btn btn-secondary">
                    ← Kembali
                </a>

                <button type="submit" name="simpan" class="btn btn-success">
                    💾 Simpan
                </button>

            </div>

        </form>

        <div class="footer-note">
            Sistem Iuran Warga v1.0
        </div>

    </div>
</div>

</body>
</html> dibuat oleh MOH.BAHRUN 