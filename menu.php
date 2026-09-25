<?php
// ===================================
// MENU GLOBAL
// ===================================

$base = "/pembayaran_iuran/";

?>

<style>

.sidebar{

    width:230px;
    height:100vh;
    background:#1e293b;
    color:white;
    padding:15px;
    position:fixed;
    left:0;
    top:0;
    box-shadow:5px 0 20px rgba(0,0,0,.25);

}


.sidebar h3{

    text-align:center;
    margin:10px 0;
    font-size:20px;

}


.sidebar hr{

    border:0;
    border-top:1px solid #475569;
    margin-bottom:15px;

}


.menu-link{

    display:flex;
    align-items:center;
    gap:10px;

    padding:12px 14px;

    margin-bottom:6px;

    color:white;

    text-decoration:none;

    border-radius:10px;

    font-size:14px;

    transition:.2s;

}



.menu-link:hover{

    background:#2563eb;

    transform:translateX(5px);

}



.menu-title{

    margin-top:15px;

    margin-bottom:8px;

    font-size:11px;

    color:#94a3b8;

    text-transform:uppercase;

    padding-left:10px;

}



</style>




<div class="sidebar">


<h3>
💰 IURAN WARGA
</h3>


<hr>



<div class="menu-title">
Utama
</div>


<a href="<?= $base ?>dashboard.php" class="menu-link">

🏠 Dashboard

</a>




<div class="menu-title">
Data Penduduk
</div>


<a href="<?= $base ?>data_warga.php" class="menu-link">

👥 Data Warga

</a>




<div class="menu-title">
Keuangan
</div>


<a href="<?= $base ?>jenis_iuran.php" class="menu-link">

📌 Jenis Iuran

</a>



<a href="<?= $base ?>pemasukan.php" class="menu-link">

💰 Pemasukan

</a>





<div class="menu-title">
Laporan
</div>



<a href="<?= $base ?>laporan.php" class="menu-link">

📊 Laporan Keuangan

</a>




<a href="<?= $base ?>laporan_dukuh.php" class="menu-link">

🏘️ Laporan Warga Dukuh

</a>




</div>