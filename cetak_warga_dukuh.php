<?php

include 'koneksi.php';

require 'vendor/autoload.php';


use Dompdf\Dompdf;



$id = intval($_GET['id']);



/* ==========================
   DATA DUKUH
========================== */


$dukuh = mysqli_fetch_assoc(mysqli_query($koneksi,"


SELECT *

FROM dusun

WHERE id='$id'


"));



if(!$dukuh){

die("Dukuh tidak ditemukan");

}




/* ==========================
   DATA WARGA
========================== */


$query = mysqli_query($koneksi,"


SELECT

warga.*,

dusun.nama_dusun


FROM warga


LEFT JOIN dusun

ON warga.dusun_id=dusun.id



WHERE warga.dusun_id='$id'



ORDER BY warga.nama ASC


");




$html = '

<!DOCTYPE html>

<html>

<head>


<style>


body{

font-family:Arial, sans-serif;

font-size:12px;

}



h2{

text-align:center;

margin-bottom:5px;

}



h3{

text-align:center;

margin-top:0;

}



table{


width:100%;


border-collapse:collapse;


margin-top:20px;


}



th{


background:#ddd;


padding:8px;


border:1px solid #000;


text-align:center;


}



td{


padding:7px;


border:1px solid #000;


}



.center{


text-align:center;


}



</style>



</head>


<body>


<h2>

PEMERINTAH DESA AMBALKLIWONAN

</h2>


<h3>

DAFTAR WARGA DUKUH '.strtoupper($dukuh['nama_dusun']).'

</h3>


<p>

Tanggal Cetak : '.date('d-m-Y').'

</p>


<table>


<tr>

<th>No</th>
<th>NIK</th>
<th>Nama</th>
<th>Alamat</th>
<th>RT</th>
<th>RW</th>
<th>Dusun</th>

</tr>


';



$no=1;



while($w=mysqli_fetch_assoc($query)){



$html .= '


<tr>


<td class="center">

'.$no++.'

</td>



<td>

'.$w['nik'].'

</td>




<td>

'.strtoupper($w['nama']).'

</td>




<td>

'.$w['alamat'].'

</td>




<td class="center">

'.$w['rt'].'

</td>




<td class="center">

'.$w['rw'].'

</td>
<td>

'.$w['nama_dusun'].'

</td>



</tr>



';



}



$html .= '


</table>



<br><br>



<table style="border:none;width:100%;">


<tr>


<td style="border:none;text-align:center;">



Mengetahui,


<br>


Kepala Dusun


<br><br><br>



(........................)



</td>



<td style="border:none;text-align:center;">



Dicetak oleh,


<br>


Admin Sistem


<br><br><br>



(........................)



</td>



</tr>



</table>




</body>


</html>



';






// ==========================
// BUAT PDF
// ==========================



$dompdf = new Dompdf();



$dompdf->loadHtml($html);



$dompdf->setPaper('A4','landscape');



$dompdf->render();





$dompdf->stream(

"Laporan_Warga_".$dukuh['nama_dusun'].".pdf",

[

"Attachment"=>false

]

);



?>
