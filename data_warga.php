<?php

session_start();

include "cek_login.php";
include "koneksi.php";

$desa_id = $_SESSION['desa_id'];
$level    = $_SESSION['level'];
$dusun_id = $_SESSION['dusun_id'];

$qProfil = mysqli_query($koneksi,"
    SELECT *
    FROM profil_desa
    WHERE id='$desa_id'
    LIMIT 1
");

$profil = mysqli_fetch_assoc($qProfil);

/* ==================================
   HAPUS MASSAL
================================== */


if(isset($_POST['hapus_massal'])){


    if(!empty($_POST['pilih'])){


        foreach($_POST['pilih'] as $id){


            $id=intval($id);


            mysqli_query($koneksi,"
                DELETE FROM warga
                WHERE id='$id'
            ");


        }


        header("Location:data_warga.php?type=success&pesan=Data warga berhasil dihapus");
        exit;


    }else{


        header("Location:data_warga.php?type=warning&pesan=Pilih data terlebih dahulu");
        exit;


    }


}
/* ==================================
   PENCARIAN
================================== */
$search=$_GET['search'] ?? '';

$search=mysqli_real_escape_string($koneksi,$search);
$level     = $_SESSION['level'];
$dusun_id  = $_SESSION['dusun_id'];

if($level=="admin"){

    $filter = "
    warga.desa_id='$desa_id'
    AND
    (
        warga.nama LIKE '%$search%'
        OR
        warga.nik LIKE '%$search%'
    )
    ";

}else{

    $filter = "
    warga.desa_id='$desa_id'
    AND warga.dusun_id='$dusun_id'
    AND
    (
        warga.nama LIKE '%$search%'
        OR
        warga.nik LIKE '%$search%'
    )
    ";

}
$qTotal = mysqli_query($koneksi,"
SELECT COUNT(*) AS total
FROM warga
WHERE $filter
");

$total = mysqli_fetch_assoc($qTotal);
$query=mysqli_query($koneksi,"

SELECT

warga.*,

dusun.nama_dusun

FROM warga

LEFT JOIN dusun
ON warga.dusun_id=dusun.id

WHERE $filter

ORDER BY

dusun.nama_dusun ASC,

warga.nama ASC

");
?>
<!
<html>
<head>
<title>Data Warga</title>
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


padding:25px;


}



.card{


background:white;


padding:25px;


border-radius:20px;


box-shadow:0 10px 30px rgba(0,0,0,.10);


}





.topbar{


display:flex;


justify-content:space-between;


align-items:center;


gap:20px;


flex-wrap:wrap;


}




.title h2{


margin:0;


font-size:25px;


color:#0f172a;


}


.title p{


margin:6px 0;


color:#64748b;


}




/* ================================
   BUTTON
================================ */


.btn{


display:inline-flex;


align-items:center;


justify-content:center;


gap:7px;


padding:10px 16px;


border-radius:10px;


text-decoration:none;


border:none;


font-size:14px;


cursor:pointer;


margin:3px;


transition:.2s;


}



.btn:hover{


transform:translateY(-2px);


}




.dashboard{


background:#475569;


color:white;


}


.tambah{


background:#2563eb;


color:white;


}



.upload{


background:#16a34a;


color:white;


}



.pdf{


background:#7c3aed;


color:white;


}



.template{


background:#eab308;


color:#111827;


}



.hapus{


background:#dc2626;


color:white;


}





/* ================================
   TOTAL
================================ */


.info{


background:#eff6ff;


border:1px solid #dbeafe;


padding:15px 25px;


border-radius:15px;


text-align:center;


color:#334155;


}



.info b{


display:block;


font-size:28px;


color:#2563eb;


}





/* ================================
 SEARCH
================================ */


.search{


display:flex;


gap:10px;


margin-top:25px;


}



.search input{


flex:1;


padding:13px 15px;


border:1px solid #cbd5e1;


border-radius:12px;


font-size:15px;


outline:none;


}



.search input:focus{


border-color:#2563eb;


}



.search button{


background:#2563eb;


color:white;


}





/* ================================
 TOOLBAR
================================ */


.toolbar{


margin-top:20px;


background:#f8fafc;


padding:15px;


border-radius:12px;


display:flex;


justify-content:space-between;


align-items:center;


flex-wrap:wrap;


}



.pilih{


display:flex;


align-items:center;


gap:10px;


}



.checkbox{


width:18px;


height:18px;


cursor:pointer;


}



#jumlahDipilih{


color:#16a34a;


font-size:18px;


}





/* ================================
 TABLE
================================ */



.table-responsive{


width:100%;


overflow-x:auto;


margin-top:20px;


}



table{


width:100%;


border-collapse:collapse;


min-width:1100px;


}




thead th{


background:linear-gradient(135deg,#1e3a8a,#2563eb);


color:white;


padding:14px;


text-align:center;


font-size:13px;


letter-spacing:.5px;


text-transform:uppercase;


}



tbody td{


padding:12px;


border-bottom:1px solid #e2e8f0;


vertical-align:middle;


}



tbody tr:hover{


background:#f8fafc;


}



.selected{


background:#dbeafe!important;


}





/* ================================
 DATA STYLE
================================ */


.nik{


font-family:monospace;


font-weight:bold;


color:#475569;


letter-spacing:1px;


}



.nama{


font-weight:700;


font-size:15px;


color:#0f172a;


}



.alamat{


max-width:260px;


white-space:nowrap;


overflow:hidden;


text-overflow:ellipsis;


}



.status{


display:inline-block;


padding:6px 14px;


border-radius:30px;


background:#dcfce7;


color:#166534;


font-size:12px;


font-weight:600;


}




/* ================================
 AKSI
================================ */


.aksi{


white-space:nowrap;


}



.btn-aksi{


display:inline-flex;


align-items:center;


gap:5px;


padding:7px 10px;


border-radius:8px;


font-size:12px;


text-decoration:none;


margin:2px;


}



.view{


background:#2563eb;


color:white;


}



.edit{


background:#f59e0b;


color:white;


}



.delete{


background:#dc2626;


color:white;


}





/* ================================
 MOBILE
================================ */



@media(max-width:768px){


.container{


padding:10px;


}



.card{


padding:15px;


}



.topbar{


flex-direction:column;


align-items:stretch;


}



.search{


flex-direction:column;


}



.btn{


width:100%;


}



.toolbar{


flex-direction:column;


align-items:stretch;


}



}


</style>



</head>



<body>



<?php include 'notifikasi.php'; ?>



<div class="container">


<div class="card">



<div class="topbar">



<div class="title">


<h2>
👥 DATA WARGA BENDAN
</h2>


<p>
Administrasi Penduduk Desa Ambalkliwonan
</p>



<div style="margin-top:15px;">


<a href="dashboard.php" class="btn dashboard">

<i class="fa fa-arrow-left"></i>
Dashboard

</a>


<a href="tambah_warga.php" class="btn tambah">

<i class="fa fa-user-plus"></i>
Tambah Warga

</a>


<a href="upload_warga.php" class="btn upload">

<i class="fa fa-upload"></i>
Upload

</a>


<a href="cetak_warga.php" target="_blank" class="btn pdf">

<i class="fa fa-print"></i>
Cetak

</a>


<a href="template_warga.csv?v=2" class="btn template">

<i class="fa fa-download"></i>
Template

</a>



</div>


</div>




<div class="info">

Total Warga

<b>

<?= $total['total']; ?>

</b>

Jiwa


</div>



</div>





<form method="GET" class="search">


<input

type="text"

name="search"

placeholder="🔍 Cari NIK atau Nama Warga..."

value="<?= htmlspecialchars($search); ?>">



<button class="btn">

<i class="fa fa-search"></i>

Cari

</button>


</form>

<form method="POST" id="formHapus">


<div class="toolbar">


<div class="pilih">


<label>


<input 

type="checkbox"

id="cekSemua"

class="checkbox">


<b>Pilih Semua</b>


</label>



&nbsp;&nbsp;


<span>


Dipilih :

<b id="jumlahDipilih">

0

</b>

Data


</span>



</div>





<button

type="submit"

name="hapus_massal"

class="btn hapus"

id="btnHapus"

disabled

onclick="return confirm('Yakin ingin menghapus data yang dipilih?')">


<i class="fa fa-trash"></i>

Hapus Terpilih


</button>



</div>





<div class="table-responsive">


<table>



<thead>


<tr>


<th width="50">

✓

</th>



<th width="60">

NO

</th>



<th width="160">

NIK

</th>



<th width="220">

NAMA LENGKAP

</th>



<th>

ALAMAT

</th>



<th width="60">

RT

</th>



<th width="60">

RW

</th>



<th width="150">

DUSUN

</th>



<th width="230">

AKSI

</th>



</tr>


</thead>




<tbody>



<?php


$no=1;



while($row=mysqli_fetch_assoc($query)){


?>



<tr>



<td align="center">


<input


type="checkbox"


name="pilih[]"


value="<?= $row['id']; ?>"


class="cek checkbox">



</td>





<td align="center">


<?= $no++; ?>


</td>





<td class="nik">


<?= $row['nik']; ?>


</td>





<td>


<div class="nama">


<?= strtoupper($row['nama']); ?>


</div>


</td>





<td class="alamat">


<?= $row['alamat']; ?>


</td>





<td align="center">


<?= $row['rt']; ?>


</td>





<td align="center">


<?= $row['rw']; ?>


</td>





<td align="center">


<span class="status">


<?= $row['nama_dusun'] ?? '-'; ?>


</span>


</td>





<td class="aksi">



<a


href="lihat_warga.php?id=<?= $row['id']; ?>"


class="btn-aksi view">
<i class="fa fa-eye"></i>
Lihat
</a>
<a
href="edit_warga.php?id=<?= $row['id']; ?>"
class="btn-aksi edit">
<i class="fa fa-edit"></i>
Edit
</a>
<a
href="hapus_warga.php?id=<?= $row['id']; ?>"
class="btn-aksi delete"
onclick="return confirm('Hapus data warga ini?')">
<i class="fa fa-trash"></i>
Hapus
</a>
</td>
</tr>
<?php } ?>
</tbody>
</table>
</div>
</form>
</div>
</div>
<script>
// ===============================
// CHECKBOX PILIH SEMUA
// ===============================
const cekSemua = document.getElementById("cekSemua");
const semuaCek = document.querySelectorAll(".cek");
const jumlahDipilih = document.getElementById("jumlahDipilih");
const btnHapus = document.getElementById("btnHapus");
function updateJumlah(){
let jumlah=0;
semuaCek.forEach(function(item){
if(item.checked){
jumlah++;
item.closest("tr").classList.add("selected");
}else{
item.closest("tr").classList.remove("selected");
}
});
jumlahDipilih.innerHTML=jumlah;
if(jumlah>0){
btnHapus.disabled=false;
}else{
btnHapus.disabled=true;
}
}
cekSemua.addEventListener("change",function(){
semuaCek.forEach(function(item){
item.checked=cekSemua.checked;
});
updateJumlah();
});
semuaCek.forEach(function(item){
item.addEventListener("change",function(){
updateJumlah();
});
});
updateJumlah();
</script>
</body>
</html>