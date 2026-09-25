<?php

session_start();

include "../cek_login.php";
include "../koneksi.php";



$sppt_id = intval($_POST['sppt_id'] ?? 0);

$pemilik_asal_id = intval($_POST['pemilik_asal'] ?? 0);

$pemilik_tujuan = strtoupper(trim($_POST['pemilik_tujuan'] ?? ''));

$luas = intval($_POST['luas_mutasi'] ?? 0);

$jenis = $_POST['jenis_mutasi'] ?? '';

$tanggal = $_POST['tanggal_mutasi'] ?? date('Y-m-d');

$keterangan = $_POST['keterangan'] ?? '';





if(
$sppt_id<=0 ||
$pemilik_asal_id<=0 ||
$pemilik_tujuan=='' ||
$luas<=0
){

die("Data belum lengkap");

}




mysqli_begin_transaction($koneksi);



try{



/*
=========================
1. AMBIL PEMILIK ASAL
=========================
*/


$q=mysqli_query($koneksi,"

SELECT

pemilik_tanah.nama_pemilik,

kepemilikan_tanah.luas_dimiliki


FROM kepemilikan_tanah


JOIN pemilik_tanah

ON kepemilikan_tanah.pemilik_id=pemilik_tanah.id


WHERE

kepemilikan_tanah.sppt_id='$sppt_id'

AND kepemilikan_tanah.pemilik_id='$pemilik_asal_id'


LIMIT 1

");


$asal=mysqli_fetch_assoc($q);



if(!$asal){

throw new Exception("Pemilik asal tidak ditemukan");

}



if($asal['luas_dimiliki'] < $luas){

throw new Exception("Luas tanah tidak mencukupi");

}




$nama_asal=$asal['nama_pemilik'];






/*
=========================
2. CARI PEMILIK TUJUAN
=========================
*/


$tujuan=mysqli_query($koneksi,"

SELECT id

FROM pemilik_tanah

WHERE nama_pemilik='$pemilik_tujuan'

LIMIT 1

");





if(mysqli_num_rows($tujuan)>0){


$t=mysqli_fetch_assoc($tujuan);

$id_tujuan=$t['id'];



}else{


mysqli_query($koneksi,"

INSERT INTO pemilik_tanah

(
nama_pemilik,
keterangan
)

VALUES

(
'$pemilik_tujuan',
'Pemilik hasil mutasi tanah'
)

");



$id_tujuan=mysqli_insert_id($koneksi);



}







/*
=========================
3. KURANGI PEMILIK ASAL
=========================
*/


mysqli_query($koneksi,"

UPDATE kepemilikan_tanah

SET

luas_dimiliki = luas_dimiliki-$luas


WHERE

sppt_id='$sppt_id'

AND pemilik_id='$pemilik_asal_id'

");







/*
=========================
4. TAMBAH PEMILIK BARU
=========================
*/


$cek=mysqli_query($koneksi,"

SELECT id

FROM kepemilikan_tanah

WHERE

sppt_id='$sppt_id'

AND pemilik_id='$id_tujuan'

");




if(mysqli_num_rows($cek)>0){


mysqli_query($koneksi,"

UPDATE kepemilikan_tanah

SET

luas_dimiliki = luas_dimiliki+$luas


WHERE

sppt_id='$sppt_id'

AND pemilik_id='$id_tujuan'

");



}else{



mysqli_query($koneksi,"

INSERT INTO kepemilikan_tanah

(
sppt_id,
pemilik_id,
luas_dimiliki,
status
)

VALUES

(
'$sppt_id',
'$id_tujuan',
'$luas',
'AKTIF'
)

");


}







/*
=========================
5. SIMPAN RIWAYAT
=========================
*/


$jenis=mysqli_real_escape_string(
$koneksi,
$jenis
);


$keterangan=mysqli_real_escape_string(
$koneksi,
$keterangan
);



mysqli_query($koneksi,"

INSERT INTO riwayat_tanah

(
sppt_id,
dari_pemilik,
kepada_pemilik,
luas_mutasi,
jenis_mutasi,
tanggal_mutasi,
keterangan
)

VALUES

(
'$sppt_id',
'$nama_asal',
'$pemilik_tujuan',
'$luas',
'$jenis',
'$tanggal',
'$keterangan'
)

");







mysqli_commit($koneksi);



echo "

<script>

alert('✅ Mutasi tanah berhasil disimpan');

window.location='riwayat_tanah.php?id=$sppt_id';

</script>

";




}catch(Exception $e){


mysqli_rollback($koneksi);



echo "

<script>

alert('".$e->getMessage()."');

history.back();

</script>

";


}



?>