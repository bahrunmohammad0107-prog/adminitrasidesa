<?php

session_start();

include "../cek_login.php";
include "../koneksi.php";

$desa_id = $_SESSION['desa_id'] ?? 1;

$id = intval($_GET['id'] ?? 0);

$q = mysqli_query($koneksi,"
SELECT *
FROM pajak_sppt
WHERE id='$id'
AND desa_id='$desa_id'
LIMIT 1
");

$data = mysqli_fetch_assoc($q);

if(!$data){
    die("Data tidak ditemukan");
}

mysqli_query($koneksi,"
DELETE FROM pajak_sppt
WHERE id='$id'
");

echo "
<script>
alert('Data SPPT berhasil dihapus');
window.location='data_sppt.php';
</script>
";
exit;