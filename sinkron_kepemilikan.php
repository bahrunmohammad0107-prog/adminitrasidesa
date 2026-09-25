<?php

session_start();

include "../cek_login.php";
include "../koneksi.php";


// AMBIL SEMUA SPPT

$data=mysqli_query($koneksi,"

SELECT 

id,
nama_wajib_pajak,
luas_tanah

FROM pajak_sppt

ORDER BY id ASC

");



$jumlah=0;
$baru=0;



while($d=mysqli_fetch_assoc($data)){



    $sppt_id=$d['id'];

    $nama=mysqli_real_escape_string(
        $koneksi,
        trim($d['nama_wajib_pajak'])
    );


    $luas=intval($d['luas_tanah']);



    if($nama=='' || $luas<=0){

        continue;

    }



    // CEK PEMILIK SUDAH ADA ATAU BELUM


    $cek=mysqli_query($koneksi,"

    SELECT id

    FROM pemilik_tanah

    WHERE nama_pemilik='$nama'

    LIMIT 1

    ");



    if(mysqli_num_rows($cek)>0){


        $pemilik=mysqli_fetch_assoc($cek);

        $pemilik_id=$pemilik['id'];



    }else{


        // BUAT PEMILIK BARU


        mysqli_query($koneksi,"

        INSERT INTO pemilik_tanah

        (
        nama_pemilik
        )

        VALUES

        (
        '$nama'
        )

        ");



        $pemilik_id=mysqli_insert_id($koneksi);

        $baru++;


    }





    // CEK KEPEMILIKAN SUDAH ADA


    $cek_kepemilikan=mysqli_query($koneksi,"

    SELECT id

    FROM kepemilikan_tanah

    WHERE

    sppt_id='$sppt_id'

    AND pemilik_id='$pemilik_id'

    ");



    if(mysqli_num_rows($cek_kepemilikan)==0){



        mysqli_query($koneksi,"

        INSERT INTO kepemilikan_tanah

        (
        sppt_id,
        pemilik_id,
        luas_dimiliki
        )

        VALUES

        (
        '$sppt_id',
        '$pemilik_id',
        '$luas'
        )

        ");


        $jumlah++;


    }



}




echo "

<!DOCTYPE html>

<html>

<head>

<title>Sinkronisasi</title>

<style>

body{

font-family:Segoe UI;

background:#eef2ff;

padding:40px;

}

.box{

background:white;

padding:30px;

border-radius:20px;

max-width:500px;

margin:auto;

text-align:center;

box-shadow:0 10px 25px rgba(0,0,0,.1);

}

h2{

color:#166534;

}

</style>

</head>


<body>


<div class='box'>


<h2>
✅ Sinkronisasi Berhasil
</h2>


<p>

Data kepemilikan dibuat :

<b>$jumlah</b>

</p>


<p>

Pemilik baru ditambahkan :

<b>$baru</b>

</p>


<br>


<a href='data_sppt.php'>

⬅ Kembali Data SPPT

</a>


</div>


</body>

</html>

";

?>