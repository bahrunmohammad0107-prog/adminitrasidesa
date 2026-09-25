<?php
include 'koneksi.php';

$iuran_id = isset($_GET['iuran']) ? intval($_GET['iuran']) : 0;

$where = "";
$judul = "SEMUA JENIS IURAN";

if($iuran_id > 0){

    $where = " WHERE p.iuran_id='$iuran_id' ";

    $qjudul = mysqli_query($conn,"
    SELECT nama_iuran
    FROM jenis_iuran
    WHERE id='$iuran_id'
    ");

    if(mysqli_num_rows($qjudul)>0){
        $j = mysqli_fetch_assoc($qjudul);
        $judul = $j['nama_iuran'];
    }
}

/* DATA */
$data = mysqli_query($conn,"
SELECT p.*, w.nama, w.nik, w.rt, j.nama_iuran
FROM pembayaran p
LEFT JOIN warga w ON w.id = p.warga_id
LEFT JOIN jenis_iuran j ON j.id = p.iuran_id
$where
ORDER BY p.tanggal DESC
");

/* TOTAL */
$total = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COALESCE(SUM(p.jumlah_bayar),0) AS total
FROM pembayaran p
$where
"))['total'];
?>

<style>
@page{
    size:A4 portrait;
    margin:10mm;
}

body{
    font-family:Arial, sans-serif;
    font-size:12px;
}

.header{
    text-align:center;
    margin-bottom:20px;
}

.header h2{
    margin:0;
}

.header h3{
    margin:5px 0;
    color:#0d6efd;
}

table{
    width:100%;
    border-collapse:collapse;
}

table th,
table td{
    border:1px solid #000;
    padding:6px;
}

table th{
    background:#dbeafe;
}

.total{
    margin-top:15px;
    text-align:right;
    font-weight:bold;
    font-size:14px;
}

.no-print{
    margin-bottom:15px;
}

@media print{
    .no-print{
        display:none;
    }
}
</style>

<div class="no-print">

    <a href="pemasukan.php?iuran=<?= $iuran_id; ?>"
       style="
       padding:8px 15px;
       background:#6c757d;
       color:white;
       text-decoration:none;
       border-radius:5px;">
       ← Kembali
    </a>

    <button onclick="window.print()"
       style="
       padding:8px 15px;
       background:#dc3545;
       color:white;
       border:none;
       border-radius:5px;
       cursor:pointer;">
       Cetak PDF
    </button>

</div>

<div class="header">

    <h2>LAPORAN PEMASUKAN IURAN</h2>

    <h3><?= strtoupper($judul); ?></h3>

    <p>Tanggal Cetak : <?= date('d-m-Y'); ?></p>

</div>

<table>

<thead>
<tr>
    <th>No</th>
    <th>NIK</th>
    <th>Nama</th>
    <th>RT</th>
    <th>Jenis Iuran</th>
    <th>Tanggal</th>
    <th>Jumlah</th>
</tr>
</thead>

<tbody>

<?php
$no=1;

while($d=mysqli_fetch_assoc($data)){
?>

<tr>
    <td><?= $no++; ?></td>
    <td><?= $d['nik']; ?></td>
    <td><?= $d['nama']; ?></td>
    <td><?= $d['rt']; ?></td>
    <td><?= $d['nama_iuran']; ?></td>
    <td><?= date('d-m-Y',strtotime($d['tanggal'])); ?></td>
    <td>
        Rp <?= number_format($d['jumlah_bayar'],0,',','.'); ?>
    </td>
</tr>

<?php } ?>

</tbody>

</table>

<div class="total">
    TOTAL PEMASUKAN :
    Rp <?= number_format($total,0,',','.'); ?>
</div>