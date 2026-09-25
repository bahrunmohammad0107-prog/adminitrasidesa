<?php
include 'koneksi.php';

$id = $_GET['id'] ?? 0;

if($id == 0){
    die("ID tidak ditemukan");
}

/*
|--------------------------------------------------------------------------
| Hapus seluruh pembayaran warga
|--------------------------------------------------------------------------
*/

mysqli_query($koneksi,"
DELETE FROM pembayaran
WHERE warga_id='$id'
");

/*
|--------------------------------------------------------------------------
| Hapus data warga
|--------------------------------------------------------------------------
*/

mysqli_query($koneksi,"
DELETE FROM warga
WHERE id='$id'
");

echo "
<script>
alert('Data warga dan seluruh riwayat pembayaran berhasil dihapus');
window.location='data_warga.php';
</script>
";
?>