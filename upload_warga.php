<?php
include 'koneksi.php';
error_reporting(E_ALL);
ini_set('display_errors',1);
if(isset($_POST['upload'])){
    if($_FILES['file_csv']['error'] != 0){
        die("File CSV gagal dibaca");
    }
    $file = $_FILES['file_csv']['tmp_name'];

    $handle = fopen($file,"r");
    if(!$handle){
        die("File tidak bisa dibuka");
    }
    // deteksi pemisah
    $baris = fgets($handle);
    rewind($handle);
    if(strpos($baris,";")!==false){
        $delimiter=";";
    }else{
        $delimiter=",";
    }
    // header
    $header = fgetcsv($handle,10000,$delimiter);
    if(!$header){
        die("Header CSV kosong");
    }
    $header=array_map(function($v){

        $v = strtolower(trim($v));
        $v = str_replace("\xEF\xBB\xBF","",$v);

        return $v;

    },$header);
    // posisi kolom

    $kolom_nik=array_search('nik',$header);
    $kolom_nama=array_search('nama',$header);
    $kolom_alamat=array_search('alamat',$header);
    $kolom_rt=array_search('rt',$header);
    $kolom_rw=array_search('rw',$header);
    $kolom_dusun=array_search('dusun',$header);
    if($kolom_dusun===false){

        die("Kolom dusun tidak ditemukan");

    }
    $berhasil=0;
    $duplikat=0;
    while(($data=fgetcsv($handle,10000,$delimiter))!==FALSE){


    // lewati baris kosong
    if(count(array_filter($data))==0){
        continue;
    }


        if(count($data)<6){
            continue;
        }
        $nik=mysqli_real_escape_string(
            $koneksi,
            trim($data[$kolom_nik])
        );
        $nama=mysqli_real_escape_string(
            $koneksi,
            trim($data[$kolom_nama])
        );
        $alamat=mysqli_real_escape_string(
            $koneksi,
            trim($data[$kolom_alamat])
        );
        $rt=mysqli_real_escape_string(
            $koneksi,
            trim($data[$kolom_rt])
        );


        $rw=mysqli_real_escape_string(
            $koneksi,
            trim($data[$kolom_rw])
        );


        $dusun = mysqli_real_escape_string(
    $koneksi,
    strtoupper(trim($data[$kolom_dusun] ?? ''))
);


if($dusun==''){

    continue;

}




        // =========================
        // CARI DUSUN
        // =========================


        $qDusun=mysqli_query($koneksi,"
            SELECT id
            FROM dusun
            WHERE UPPER(TRIM(nama_dusun))='$dusun'
            LIMIT 1
        ");



        if(!$qDusun){

            die(
            "Error query dusun : "
            .mysqli_error($koneksi)
            );

        }



        if(mysqli_num_rows($qDusun)==0){


            die(
            "Dusun tidak ditemukan : [$dusun]"
            );


        }



        $dus=mysqli_fetch_assoc($qDusun);


        $dusun_id=$dus['id'];





        // =========================
        // CEK NIK
        // =========================


        $cek=mysqli_query($koneksi,"
            SELECT id
            FROM warga
            WHERE nik='$nik'
        ");



        if(mysqli_num_rows($cek)>0){

            $duplikat++;

            continue;

        }




        // =========================
        // SIMPAN WARGA
        // =========================


        $insert=mysqli_query($koneksi,"
            INSERT INTO warga
            (
                nik,
                nama,
                alamat,
                rt,
                rw,
                dusun_id
            )
            VALUES
            (
                '$nik',
                '$nama',
                '$alamat',
                '$rt',
                '$rw',
                '$dusun_id'
            )
        ");



        if(!$insert){

            die(
            "Gagal simpan : "
            .mysqli_error($koneksi)
            );

        }



        $berhasil++;


    }


    fclose($handle);



    echo "
    <script>

    alert(
    'UPLOAD SELESAI\\n\\nBerhasil : $berhasil data\\nDuplikat : $duplikat data'
    );

    window.location='data_warga.php';

    </script>";



}

?>


<!DOCTYPE html>
<html>

<head>

<title>Upload Data Warga</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>


<body>


<div class="container mt-5">


<div class="card shadow">


<div class="card-header bg-success text-white">

<h4>
Upload Data Warga CSV
</h4>

</div>


<div class="card-body">


<form method="post" enctype="multipart/form-data">


<input 
type="file"
name="file_csv"
class="form-control mb-3"
accept=".csv"
required>


<button 
type="submit"
name="upload"
class="btn btn-success">

Upload Data

</button>


<a href="data_warga.php"
class="btn btn-secondary">

Kembali

</a>


</form>


</div>

</div>

</div>


</body>

</html>