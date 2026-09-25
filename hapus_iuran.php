<?php
include 'koneksi.php';

$id = $_GET['id'];

$hapus = mysqli_query($conn,"
DELETE FROM jenis_iuran
WHERE id='$id'
");

if($hapus){
    echo "
    <script>
        alert('Data berhasil dihapus');
        window.location='jenis_iuran.php';
    </script>
    ";
}else{
    echo "
    <script>
        alert('Data gagal dihapus');
        window.location='jenis_iuran.php';
    </script>
    ";
}
?>