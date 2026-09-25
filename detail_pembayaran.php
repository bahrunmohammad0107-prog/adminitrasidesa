<?php
include 'koneksi.php';

$warga_id = $_GET['warga_id'];

$warga = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT * FROM warga WHERE id='$warga_id'"));

$data = mysqli_query($koneksi,"
SELECT p.*, j.nama_iuran 
FROM pembayaran p
LEFT JOIN jenis_iuran j ON p.jenis_iuran_id=j.id
WHERE p.warga_id='$warga_id'
ORDER BY p.tanggal_bayar DESC
");
?>

<h2>👁 Detail Pembayaran</h2>

<h3><?= $warga['nama'] ?> (<?= $warga['nik'] ?>)</h3>

<table border="1" cellpadding="10">
<tr>
    <th>Jenis Iuran</th>
    <th>Jumlah</th>
    <th>Bulan</th>
    <th>Tahun</th>
    <th>Tanggal Bayar</th>
</tr>

<?php while($d=mysqli_fetch_assoc($data)) { ?>
<tr>
    <td><?= $d['nama_iuran'] ?></td>
    <td>Rp <?= number_format($d['jumlah'],0,',','.') ?></td>
    <td><?= $d['bulan'] ?></td>
    <td><?= $d['tahun'] ?></td>
    <td><?= $d['tanggal_bayar'] ?></td>
</tr>
<?php } ?>
</table>

<br>
<a href="pembayaran_warga.php">⬅ Kembali</a>