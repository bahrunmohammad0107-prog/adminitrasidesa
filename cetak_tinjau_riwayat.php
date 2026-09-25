<?php

session_start();

include "../cek_login.php";
include "../koneksi.php";

$desa_id = $_SESSION['desa_id'] ?? 1;

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    die("ID SPPT tidak ditemukan");
}


/*
====================================================
DATA SPPT
====================================================
*/

$q = mysqli_query($koneksi, "
    SELECT *
    FROM pajak_sppt
    WHERE id='$id'
    AND desa_id='$desa_id'
    LIMIT 1
");

$data = mysqli_fetch_assoc($q);

if (!$data) {
    die("Data SPPT tidak ditemukan");
}


/*
====================================================
DATA RIWAYAT
====================================================
*/

$riwayat = mysqli_query($koneksi, "

    SELECT *

    FROM riwayat_tanah

    WHERE sppt_id='$id'

    ORDER BY tanggal_mutasi ASC, id ASC

");


/*
====================================================
DATA KEPEMILIKAN
====================================================
*/

$kepemilikan = mysqli_query($koneksi, "

    SELECT
        k.id,
        k.pemilik_id,
        p.nama_pemilik,
        k.luas_dimiliki

    FROM kepemilikan_tanah k

    JOIN pemilik_tanah p
        ON k.pemilik_id = p.id

    WHERE k.sppt_id='$id'

    AND k.luas_dimiliki > 0

    ORDER BY p.nama_pemilik ASC

");


$data_kepemilikan = [];

$total_kepemilikan = 0;

while ($kp = mysqli_fetch_assoc($kepemilikan)) {

    $data_kepemilikan[] = $kp;

    $total_kepemilikan += intval($kp['luas_dimiliki']);

}


function e($text)
{
    return htmlspecialchars(
        $text ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}

?>

<!DOCTYPE html>

<html lang="id">

<head>

<meta charset="UTF-8">

<title>
Tinjauan Riwayat Tanah
</title>


<style>

*{
    box-sizing:border-box;
}

body{

    margin:0;

    background:#eef2f7;

    font-family:
    "Times New Roman",
    Arial,
    sans-serif;

    color:#111827;

}


.page{

    width:210mm;

    min-height:297mm;

    background:white;

    margin:25px auto;

    padding:18mm;

    box-shadow:
    0 5px 25px rgba(0,0,0,.15);

}


.header{

    text-align:center;

    border-bottom:3px solid #111;

    padding-bottom:15px;

    margin-bottom:20px;

}


.header h1{

    margin:0;

    font-size:22px;

    font-weight:bold;

}


.header h2{

    margin:5px 0;

    font-size:18px;

}


.header p{

    margin:3px 0;

    font-size:14px;

}


.info{

    width:100%;

    border-collapse:collapse;

    margin-bottom:25px;

}


.info td{

    padding:6px;

    vertical-align:top;

}


.info td:first-child{

    width:180px;

    font-weight:bold;

}


.judul{

    font-size:16px;

    font-weight:bold;

    margin-top:20px;

    margin-bottom:10px;

    border-left:5px solid #111;

    padding-left:10px;

}


.riwayat{

    width:100%;

    border-collapse:collapse;

    margin-bottom:25px;

}


.riwayat th{

    background:#1e3a8a;

    color:white;

    border:1px solid #111;

    padding:8px;

    font-size:12px;

}


.riwayat td{

    border:1px solid #555;

    padding:7px;

    font-size:12px;

    vertical-align:top;

}


.rekap{

    width:100%;

    border-collapse:collapse;

    margin-top:10px;

}


.rekap th{

    background:#166534;

    color:white;

    border:1px solid #111;

    padding:8px;

}


.rekap td{

    border:1px solid #555;

    padding:8px;

}


.total{

    font-weight:bold;

    background:#f3f4f6;

}


.ttd{

    width:100%;

    margin-top:55px;

}


.ttd td{

    width:50%;

    text-align:center;

    vertical-align:top;

}


.tombol{

    width:210mm;

    margin:20px auto;

    display:flex;

    gap:10px;

}


.btn{

    border:none;

    padding:11px 18px;

    border-radius:8px;

    color:white;

    text-decoration:none;

    cursor:pointer;

    font-weight:bold;

    font-family:Arial;

}


.btn-print{

    background:#2563eb;

}


.btn-back{

    background:#111827;

}


@media print{

    body{

        background:white;

    }

    .page{

        width:100%;

        min-height:auto;

        margin:0;

        padding:10mm;

        box-shadow:none;

    }

    .tombol{

        display:none;

    }

}


</style>

</head>


<body>


<!-- ==================================================
     TOMBOL
================================================== -->

<div class="tombol">

    <button
        onclick="window.print()"
        class="btn btn-print"
    >

        🖨️ Cetak

    </button>


    <a
        href="tinjau_riwayat.php?id=<?= $id ?>"
        class="btn btn-back"
    >

        ⬅️ Kembali

    </a>

</div>



<div class="page">


<!-- ==================================================
     HEADER
================================================== -->

<div class="header">

    <h1>
        PEMERINTAH DESA AMBALKLIWONAN
    </h1>

    <h2>
        RIWAYAT PERJALANAN TANAH
    </h2>

    <p>
        Dokumen Tinjauan Riwayat Peralihan Tanah
    </p>

</div>



<!-- ==================================================
     DATA SPPT
================================================== -->

<div class="judul">

    I. DATA SPPT

</div>


<table class="info">

<tr>

<td>
NOP
</td>

<td>
:
<?= e($data['nop']) ?>
</td>

</tr>


<tr>

<td>
Nama Wajib Pajak
</td>

<td>
:
<?= strtoupper(
    e($data['nama_wajib_pajak'])
) ?>
</td>

</tr>


<tr>

<td>
Alamat Wajib Pajak
</td>

<td>
:
<?= e($data['alamat_wajib_pajak']) ?>
</td>

</tr>


<tr>

<td>
Alamat Objek Pajak
</td>

<td>
:
<?= e($data['alamat_objek_pajak']) ?>
</td>

</tr>


<tr>

<td>
Luas Tanah
</td>

<td>
:
<?= number_format(
    $data['luas_tanah'],
    0,
    ',',
    '.'
) ?>

m²

</td>

</tr>

</table>



<!-- ==================================================
     SEJARAH TRANSAKSI
================================================== -->

<div class="judul">

    II. SEJARAH PERJALANAN TANAH

</div>


<table class="riwayat">

<thead>

<tr>

<th width="35">
No
</th>

<th width="80">
Tanggal
</th>

<th width="100">
Jenis
</th>

<th>
Dari Pemilik
</th>

<th>
Kepada Pemilik
</th>

<th width="75">
Luas
</th>

<th>
Keterangan
</th>

</tr>

</thead>


<tbody>

<?php

$no = 1;

if (mysqli_num_rows($riwayat) > 0):

while ($r = mysqli_fetch_assoc($riwayat)):

?>

<tr>

<td align="center">

<?= $no ?>

</td>


<td>

<?= date(
    'd-m-Y',
    strtotime($r['tanggal_mutasi'])
) ?>

</td>


<td>

<strong>

<?= strtoupper(
    e($r['jenis_mutasi'])
) ?>

</strong>

</td>


<td>

<?= strtoupper(
    e($r['dari_pemilik'])
) ?>

</td>


<td>

<?= strtoupper(
    e($r['kepada_pemilik'])
) ?>

</td>


<td align="right">

<?= number_format(
    $r['luas_mutasi'],
    0,
    ',',
    '.'
) ?>

m²

</td>


<td>

<?= e($r['keterangan']) ?>

</td>

</tr>


<?php

$no++;

endwhile;

else:

?>

<tr>

<td
    colspan="7"
    align="center"
>

Belum ada riwayat transaksi.

</td>

</tr>

<?php endif; ?>

</tbody>

</table>



<!-- ==================================================
     REKAP KEPEMILIKAN
================================================== -->

<div class="judul">

    III. REKAP KEPEMILIKAN TANAH SAAT INI

</div>


<table class="rekap">

<thead>

<tr>

<th width="60">
No
</th>

<th>
Nama Pemilik
</th>

<th width="180">
Luas Dimiliki
</th>

</tr>

</thead>


<tbody>


<?php

$no = 1;

foreach ($data_kepemilikan as $kp):

?>

<tr>

<td align="center">

<?= $no ?>

</td>


<td>

<strong>

<?= strtoupper(
    e($kp['nama_pemilik'])
) ?>

</strong>

</td>


<td align="right">

<?= number_format(
    $kp['luas_dimiliki'],
    0,
    ',',
    '.'
) ?>

m²

</td>

</tr>


<?php

$no++;

endforeach;

?>


<tr class="total">

<td
    colspan="2"
    align="right"
>

TOTAL LUAS TANAH

</td>


<td align="right">

<?= number_format(
    $total_kepemilikan,
    0,
    ',',
    '.'
) ?>

m²

</td>

</tr>


</tbody>

</table>



<!-- ==================================================
     KETERANGAN
================================================== -->

<div class="judul">

    IV. KETERANGAN

</div>


<p style="line-height:1.6;">

Dokumen ini merupakan hasil pencatatan riwayat
perjalanan dan peralihan tanah berdasarkan data
transaksi yang tersimpan pada administrasi
Pemerintah Desa Ambalkliwonan.

</p>


<!-- ==================================================
     TANDA TANGAN
================================================== -->

<table class="ttd">

<tr>

<td>

Mengetahui,

<br>

Kepala Desa Ambalkliwonan

<br><br><br><br><br>

(....................................)

</td>


<td>

Ambalkliwonan,

<br>

<?= date('d-m-Y') ?>

<br><br><br><br><br>

(....................................)

</td>

</tr>

</table>


</div>


</body>

</html>