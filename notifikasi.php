<?php

$type = $_GET['type'] ?? '';
$pesan = $_GET['pesan'] ?? '';

if($pesan != ''){

    switch($type){

        case 'error':
            $warna = '#dc3545';
            $icon  = '❌';
        break;

        case 'warning':
            $warna = '#ffc107';
            $icon  = '⚠️';
        break;

        case 'info':
            $warna = '#0dcaf0';
            $icon  = 'ℹ️';
        break;

        default:
            $warna = '#198754';
            $icon  = '✅';

    }

?>

<div id="toast"
style="
position:fixed;
top:20px;
right:20px;
min-width:320px;
max-width:420px;
background:<?= $warna ?>;
color:white;
padding:16px 22px;
border-radius:14px;
box-shadow:0 15px 35px rgba(0,0,0,.25);
font-family:Segoe UI;
font-size:15px;
font-weight:600;
z-index:999999;
animation:slideMasuk .35s;
">

<span style="font-size:20px;margin-right:8px;">
<?= $icon ?>
</span>

<?= htmlspecialchars($pesan) ?>

</div>

<style>

@keyframes slideMasuk{

from{

opacity:0;
transform:translateX(120px);

}

to{

opacity:1;
transform:translateX(0);

}

}

</style>

<script>

setTimeout(function(){

let toast=document.getElementById("toast");

if(toast){

toast.style.transition=".5s";
toast.style.opacity="0";
toast.style.transform="translateX(80px)";

setTimeout(function(){

toast.remove();

},500);

}

},2500);

</script>

<?php } ?>