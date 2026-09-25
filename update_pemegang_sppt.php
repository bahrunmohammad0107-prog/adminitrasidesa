<?php

session_start();

include "../cek_login.php";
include "../koneksi.php";


$id = $_GET['id'] ?? '';

if($id==''){

    die("ID SPPT tidak ditemukan");

}


$id = mysqli_real_escape_string($koneksi,$id);



// ambil data kepemilikan aktif

$q=mysqli_query($koneksi,"

SELECT

pemilik_tanah.nama_pemilik,

kepemilikan_tanah.luas_dimiliki


FROM kepemilikan_tanah


JOIN pemilik_tanah

ON kepemilikan_tanah.pemilik_id=pemilik_tanah.id


WHERE

kepemilikan_tanah.sppt_id='$id'

AND kepemilikan_tanah.luas_dimiliki > 0


ORDER BY pemilik_tanah.nama_pemilik ASC


");



$data=[];


while($d=mysqli_fetch_assoc($q)){


$data[] = strtoupper($d['nama_pemilik'])
." ("
.number_format($d['luas_dimiliki'],0,',','.')
." m²)";


}



if(count($data)==0){


die("Belum ada data kepemilikan");


}




// gabungkan nama pemilik

$pemegang = implode(", ",$data);





// update pajak_sppt


$update=mysqli_query($koneksi,"

UPDATE pajak_sppt

SET

pemegang_sppt='$pemegang'

WHERE id='$id'


");




if($update){


echo "

<script>

alert('Pemegang SPPT berhasil diperbarui');

window.location='data_sppt.php';

</script>

";


}else{


echo "

<script>

alert('Gagal memperbarui pemegang SPPT');

history.back();

</script>

";


}



?>