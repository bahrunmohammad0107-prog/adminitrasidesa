<?php

include 'koneksi.php';


$id = intval($_GET['id']);



/* AMBIL DATA WARGA */


$data = mysqli_fetch_assoc(mysqli_query($koneksi,"

SELECT *

FROM warga

WHERE id='$id'

"));



if(!$data){

echo "Data warga tidak ditemukan";

exit;

}





/* AMBIL DATA DUSUN */


$dusun = mysqli_query($koneksi,"

SELECT *

FROM dusun

ORDER BY nama_dusun ASC

");





/* UPDATE DATA */


if(isset($_POST['update'])){


$nik = mysqli_real_escape_string($koneksi,$_POST['nik']);

$nama = mysqli_real_escape_string($koneksi,$_POST['nama']);

$alamat = mysqli_real_escape_string($koneksi,$_POST['alamat']);

$rt = mysqli_real_escape_string($koneksi,$_POST['rt']);

$rw = mysqli_real_escape_string($koneksi,$_POST['rw']);

$dusun_id = intval($_POST['dusun_id']);





mysqli_query($koneksi,"


UPDATE warga SET


nik='$nik',

nama='$nama',

alamat='$alamat',

rt='$rt',

rw='$rw',

dusun_id='$dusun_id'


WHERE id='$id'


");





header("Location:data_warga.php?type=success&pesan=Data warga berhasil diperbarui");

exit;


}



?>


<!DOCTYPE html>

<html>

<head>


<title>Edit Data Warga</title>


<meta charset="utf-8">


<meta name="viewport" content="width=device-width,initial-scale=1">



<link rel="stylesheet"

href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">



<style>


*{

box-sizing:border-box;

}



body{


margin:0;


font-family:'Segoe UI',Arial;


background:#f1f5f9;


color:#1e293b;


}



.container{


min-height:100vh;


display:flex;


justify-content:center;


align-items:center;


padding:25px;


}



.card{


width:550px;


background:white;


border-radius:25px;


overflow:hidden;


box-shadow:0 15px 40px rgba(0,0,0,.15);


}



.header{


background:linear-gradient(135deg,#1e3a8a,#2563eb);


padding:25px;


color:white;


text-align:center;


}



.header i{


font-size:40px;


}



.header h2{


margin:10px 0 5px;


}



.header p{


margin:0;


opacity:.8;


}




.form-body{


padding:25px;


}




.group{


margin-bottom:18px;


}



label{


display:block;


font-size:13px;


font-weight:600;


color:#475569;


margin-bottom:6px;


}




input,

textarea,

select{


width:100%;


padding:13px 15px;


border:1px solid #cbd5e1;


border-radius:12px;


font-size:15px;


outline:none;


background:white;


color:#1e293b;


}



textarea{


height:90px;


resize:none;


}



input:focus,

textarea:focus,

select:focus{


border-color:#2563eb;


box-shadow:0 0 0 3px rgba(37,99,235,.15);


}




.row{


display:grid;


grid-template-columns:1fr 1fr;


gap:15px;


}





.footer{


padding:20px 25px;


background:#f8fafc;


display:flex;


gap:10px;


}



.btn{


flex:1;


padding:13px;


border-radius:12px;


border:none;


text-align:center;


font-weight:600;


cursor:pointer;


text-decoration:none;


font-size:14px;


}



.simpan{


background:linear-gradient(135deg,#22c55e,#16a34a);


color:white;


}



.kembali{


background:#475569;


color:white;


}





@media(max-width:600px){


.container{


padding:10px;


}



.card{


width:100%;


}



.row{


grid-template-columns:1fr;


}



.footer{


flex-direction:column;


}



}



</style>



</head>




<body>



<div class="container">


<div class="card">



<div class="header">


<i class="fa-solid fa-user-pen"></i>


<h2>

Edit Data Warga

</h2>


<p>

Administrasi Desa Ambalkliwonan

</p>


</div>





<div class="form-body">


<form method="POST">




<div class="group">


<label>

<i class="fa fa-id-card"></i>

NIK

</label>


<input

type="text"

name="nik"

value="<?= $data['nik']; ?>"

required>


</div>





<div class="group">


<label>

<i class="fa fa-user"></i>

Nama Lengkap

</label>


<input

type="text"

name="nama"

value="<?= $data['nama']; ?>"

required>


</div>





<div class="group">


<label>

<i class="fa fa-location-dot"></i>

Alamat

</label>


<textarea

name="alamat"

required><?= $data['alamat']; ?></textarea>


</div>





<div class="group">


<label>

<i class="fa fa-map"></i>

Dusun / Dukuh

</label>



<select name="dusun_id" required>


<option value="">-- Pilih Dusun --</option>



<?php while($d=mysqli_fetch_assoc($dusun)){ ?>



<option

value="<?= $d['id']; ?>"

<?= ($data['dusun_id']==$d['id'])?'selected':''; ?>


>


<?= strtoupper($d['nama_dusun']); ?>


</option>



<?php } ?>



</select>


</div>





<div class="row">



<div class="group">


<label>

<i class="fa fa-road"></i>

RT

</label>



<input

type="text"

name="rt"

value="<?= $data['rt']; ?>">



</div>





<div class="group">


<label>

<i class="fa fa-road"></i>

RW

</label>



<input

type="text"

name="rw"

value="<?= $data['rw']; ?>">



</div>



</div>





</div>




<div class="footer">



<button

type="submit"

name="update"

class="btn simpan">


<i class="fa fa-save"></i>

Simpan Perubahan


</button>





<a

href="lihat_warga.php?id=<?= $data['id']; ?>"

class="btn kembali">


<i class="fa fa-arrow-left"></i>

Kembali


</a>



</div>
</form>
</div>
</div>
</div>
</body>
</html>