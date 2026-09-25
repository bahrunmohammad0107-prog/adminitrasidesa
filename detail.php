<?php
include 'koneksi.php';

$id = $_GET['id'];

$warga = mysqli_fetch_assoc(mysqli_query($koneksi,"
SELECT *
FROM warga
WHERE id='$id'
"));

$data = mysqli_query($koneksi,"
SELECT
p.*,
j.nama_iuran
FROM pembayaran p
LEFT JOIN jenis_iuran j
ON p.jenis_iuran_id=j.id
WHERE p.warga_id='$id'
ORDER BY p.id DESC
");

$total = mysqli_fetch_assoc(mysqli_query($koneksi,"
SELECT COALESCE(SUM(jumlah),0) total
FROM pembayaran
WHERE warga_id='$id'
"));
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Struk Pembayaran</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    background:#eef2f7;
    font-family:'Courier New', monospace;
    padding:30px;
}

.toolbar{
    text-align:center;
    margin-bottom:20px;
}

.btn{
    display:inline-block;
    padding:12px 20px;
    text-decoration:none;
    color:white;
    border-radius:8px;
    font-weight:bold;
    margin:5px;
}

.btn-back{
    background:#34495e;
}

.btn-print{
    background:#27ae60;
}

.struk{
    width:750px;
    margin:auto;
    background:white;
    border:3px dashed #222;
    padding:30px;
    box-shadow:0 5px 20px rgba(0,0,0,.1);
}

.judul{
    text-align:center;
    font-size:32px;
    font-weight:bold;
    margin-bottom:10px;
}

.subjudul{
    text-align:center;
    font-size:20px;
    margin-bottom:15px;
}

.garis{
    border-top:2px dashed #000;
    margin:15px 0;
}

.info{
    font-size:22px;
    line-height:1.8;
}

.transaksi{
    margin-top:15px;
}

.transaksi table{
    width:100%;
    border-collapse:collapse;
}

.transaksi th{
    border-bottom:2px dashed #000;
    padding:8px;
    font-size:18px;
}

.transaksi td{
    padding:8px;
    font-size:18px;
}

.transaksi tr:nth-child(even){
    background:#f8f8f8;
}

.total{
    text-align:center;
    margin-top:20px;
}

.total h2{
    font-size:28px;
}

.total h1{
    font-size:42px;
    margin-top:10px;
}

.ttd{
    margin-top:50px;
    text-align:right;
    font-size:20px;
}

.footer{
    text-align:center;
    margin-top:40px;
    font-size:22px;
}

.note{
    margin-top:20px;
    background:#eaf4ff;
    border:1px solid #b7d4ff;
    padding:15px;
    border-radius:10px;
    text-align:center;
    color:#2c3e50;
}

@media print{

    .toolbar,
    .note{
        display:none;
    }

    body{
        background:white;
        padding:0;
    }

    .struk{
        width:100%;
        border:2px dashed #000;
        box-shadow:none;
    }

    @page{
        size:A4;
        margin:10mm;
    }

}

</style>
</head>
<body>

<div class="toolbar">

    <a href="pembayaran_warga.php" class="btn btn-back">
        ⬅ Kembali
    </a>

    <a href="#" onclick="window.print()" class="btn btn-print">
        🖨 Cetak Struk
    </a>

</div>

<div class="struk">

    <div class="judul">
        BUKTI PEMBAYARAN
    </div>

    <div class="subjudul">
        IURAN WARGA
    </div>

    <div class="garis"></div>

    <div class="info">
        Nama&nbsp;&nbsp;&nbsp;&nbsp;: <?= $warga['nama'] ?><br>
        NIK&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <?= $warga['nik'] ?><br>
        Alamat&nbsp;&nbsp;: <?= $warga['alamat'] ?><br>
        RT/RW&nbsp;&nbsp;&nbsp;: <?= $warga['rt'] ?>/<?= $warga['rw'] ?>
    </div>

    <div class="garis"></div>

    <div class="transaksi">

        <table>

            <tr>
                <th>Tanggal</th>
                <th>Jenis Iuran</th>
                <th>Nominal</th>
            </tr>

            <?php
            mysqli_data_seek($data,0);
            while($row=mysqli_fetch_assoc($data)){
            ?>

            <tr>
                <td>
                    <?= date('d-m-Y',strtotime($row['tanggal_bayar'])) ?>
                </td>

                <td>
                    <?= $row['nama_iuran'] ?>
                </td>

                <td align="right">
                    Rp <?= number_format($row['jumlah'],0,',','.') ?>
                </td>
            </tr>

            <?php } ?>

        </table>

    </div>

    <div class="garis"></div>

    <div class="total">

        <h2>TOTAL PEMBAYARAN</h2>

        <h1>
            Rp <?= number_format($total['total'],0,',','.') ?>
        </h1>

    </div>

    <div class="garis"></div>

    <div class="ttd">

        Ambal,
        <?= date('d-m-Y') ?>

        <br><br><br><br>

        (__________________)

    </div>

    <div class="footer">

        ===== TERIMA KASIH =====

        <br>

        Atas Partisipasinya

    </div>

</div>

<div class="note">

    Struk ini merupakan bukti pembayaran resmi yang dikeluarkan oleh Sistem Administrasi Iuran Warga.

</div>

</body>
</html>