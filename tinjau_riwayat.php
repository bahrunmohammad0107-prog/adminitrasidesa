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
DATA RIWAYAT TANAH
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
DATA KEPEMILIKAN SAAT INI
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


/*
====================================================
HITUNG TOTAL
====================================================
*/

$total_kepemilikan = 0;

$data_kepemilikan = [];

while ($kp = mysqli_fetch_assoc($kepemilikan)) {

    $data_kepemilikan[] = $kp;

    $total_kepemilikan += intval($kp['luas_dimiliki']);
}


/*
====================================================
FUNGSI ESCAPE
====================================================
*/

function e($text)
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Tinjau Riwayat Tanah</title>


<style>

/* ==================================================
   GLOBAL
================================================== */

*{
    box-sizing:border-box;
}

body{

    margin:0;

    font-family:'Segoe UI',Arial,sans-serif;

    background:
    linear-gradient(
        135deg,
        #eef4ff,
        #f8fafc
    );

    color:#172033;

}


/* ==================================================
   CONTAINER
================================================== */

.container{

    max-width:1100px;

    margin:35px auto;

    padding:0 20px;

}


/* ==================================================
   HEADER
================================================== */

.header{

    background:
    linear-gradient(
        135deg,
        #0f766e,
        #16a34a
    );

    color:white;

    padding:28px 30px;

    border-radius:24px;

    box-shadow:
    0 15px 35px rgba(0,0,0,.12);

    margin-bottom:25px;

}


.header h1{

    margin:0;

    font-size:27px;

    font-weight:800;

}


.header p{

    margin:7px 0 0;

    opacity:.9;

}


/* ==================================================
   CARD
================================================== */

.card{

    background:white;

    border-radius:24px;

    padding:25px;

    margin-bottom:25px;

    box-shadow:
    0 10px 30px rgba(15,23,42,.08);

}


/* ==================================================
   DATA SPPT
================================================== */

.info-grid{

    display:grid;

    grid-template-columns:
    repeat(2,1fr);

    gap:15px;

}


.info-box{

    background:#f8fafc;

    border:1px solid #e5e7eb;

    border-radius:16px;

    padding:17px;

}


.info-label{

    color:#64748b;

    font-size:13px;

    font-weight:700;

    margin-bottom:6px;

}


.info-value{

    font-size:17px;

    font-weight:800;

    color:#172033;

}


/* ==================================================
   JUDUL
================================================== */

.section-title{

    display:flex;

    align-items:center;

    gap:10px;

    font-size:20px;

    font-weight:800;

    margin-bottom:20px;

}


/* ==================================================
   ALUR TRANSAKSI
================================================== */

.timeline{

    position:relative;

    margin-left:20px;

    padding-left:35px;

}


.timeline:before{

    content:"";

    position:absolute;

    left:8px;

    top:10px;

    bottom:10px;

    width:4px;

    background:
    linear-gradient(
        #2563eb,
        #16a34a
    );

    border-radius:10px;

}


.timeline-item{

    position:relative;

    margin-bottom:22px;

}


.timeline-dot{

    position:absolute;

    left:-42px;

    top:12px;

    width:30px;

    height:30px;

    border-radius:50%;

    background:#2563eb;

    color:white;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:13px;

    font-weight:800;

    box-shadow:
    0 0 0 5px #dbeafe;

}


.transaction-card{

    background:#f8fafc;

    border:1px solid #e2e8f0;

    border-left:5px solid #2563eb;

    border-radius:17px;

    padding:18px;

}


.transaction-top{

    display:flex;

    justify-content:space-between;

    align-items:center;

    gap:15px;

    flex-wrap:wrap;

}


.transaction-title{

    font-size:18px;

    font-weight:800;

    color:#172554;

}


.transaction-date{

    font-size:13px;

    color:#64748b;

}


.transaction-flow{

    margin-top:12px;

    display:flex;

    align-items:center;

    gap:10px;

    flex-wrap:wrap;

}


.owner{

    background:white;

    border:1px solid #cbd5e1;

    border-radius:10px;

    padding:8px 13px;

    font-weight:700;

}


.arrow{

    font-size:20px;

    font-weight:900;

    color:#2563eb;

}


.transaction-detail{

    margin-top:12px;

    display:grid;

    grid-template-columns:
    repeat(3,1fr);

    gap:10px;

}


.detail-box{

    background:white;

    padding:10px 12px;

    border-radius:10px;

    border:1px solid #e2e8f0;

}


.detail-label{

    font-size:11px;

    color:#64748b;

    font-weight:700;

}


.detail-value{

    font-weight:800;

    margin-top:3px;

}


.badge{

    display:inline-block;

    padding:6px 11px;

    background:#dcfce7;

    color:#166534;

    border-radius:999px;

    font-size:12px;

    font-weight:800;

}


/* ==================================================
   REKAP KEPEMILIKAN
================================================== */

.owner-grid{

    display:grid;

    grid-template-columns:
    repeat(3,1fr);

    gap:15px;

}


.owner-card{

    background:
    linear-gradient(
        135deg,
        #f0fdf4,
        #dcfce7
    );

    border:1px solid #bbf7d0;

    border-radius:18px;

    padding:20px;

}


.owner-name{

    font-size:18px;

    font-weight:900;

    color:#166534;

}


.owner-area{

    margin-top:8px;

    font-size:25px;

    font-weight:900;

    color:#14532d;

}


.owner-label{

    font-size:12px;

    color:#64748b;

    margin-top:3px;

}


/* ==================================================
   TOTAL
================================================== */

.total-box{

    margin-top:20px;

    padding:18px;

    border-radius:16px;

    background:#eff6ff;

    border:1px solid #bfdbfe;

    display:flex;

    justify-content:space-between;

    align-items:center;

}


.total-label{

    font-weight:800;

    color:#1e3a8a;

}


.total-value{

    font-size:24px;

    font-weight:900;

    color:#1d4ed8;

}


/* ==================================================
   BUTTON
================================================== */

.actions{

    display:flex;

    gap:12px;

    flex-wrap:wrap;

    margin-top:20px;

}


.btn{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    gap:7px;

    padding:13px 20px;

    border-radius:13px;

    text-decoration:none;

    font-weight:800;

    border:none;

    cursor:pointer;

    transition:.2s;

}


.btn:hover{

    transform:translateY(-2px);

    box-shadow:
    0 7px 15px rgba(0,0,0,.12);

}


.btn-blue{

    background:#2563eb;

    color:white;

}


.btn-green{

    background:#16a34a;

    color:white;

}


.btn-dark{

    background:#111827;

    color:white;

}


/* ==================================================
   EMPTY
================================================== */

.empty{

    padding:30px;

    text-align:center;

    background:#fff7ed;

    border:1px solid #fed7aa;

    border-radius:16px;

    color:#9a3412;

    font-weight:700;

}


/* ==================================================
   RESPONSIVE
================================================== */

@media(max-width:768px){

    .info-grid{

        grid-template-columns:1fr;

    }

    .owner-grid{

        grid-template-columns:1fr;

    }

    .transaction-detail{

        grid-template-columns:1fr;

    }

    .header h1{

        font-size:22px;

    }

    .container{

        margin:15px auto;

    }

}


/* ==================================================
   PRINT
================================================== */

@media print{

    body{

        background:white;

    }

    .container{

        max-width:none;

        margin:0;

        padding:0;

    }

    .header{

        box-shadow:none;

        print-color-adjust:exact;

        -webkit-print-color-adjust:exact;

    }

    .actions{

        display:none;

    }

    .card{

        box-shadow:none;

        border:1px solid #ddd;

        break-inside:avoid;

    }

}

</style>

</head>


<body>


<div class="container">


<!-- ==================================================
     HEADER
================================================== -->

<div class="header">

    <h1>
        📜 TINJAU RIWAYAT PERJALANAN TANAH
    </h1>

    <p>
        Desa Ambalkliwonan
    </p>

</div>



<!-- ==================================================
     DATA SPPT
================================================== -->

<div class="card">

    <div class="section-title">

        📋 Informasi SPPT

    </div>


    <div class="info-grid">


        <div class="info-box">

            <div class="info-label">
                NOP
            </div>

            <div class="info-value">
                <?= e($data['nop']) ?>
            </div>

        </div>



        <div class="info-box">

            <div class="info-label">
                Pemilik SPPT
            </div>

            <div class="info-value">

                <?= strtoupper(
                    e($data['nama_wajib_pajak'])
                ) ?>

            </div>

        </div>



        <div class="info-box">

            <div class="info-label">
                Alamat
            </div>

            <div class="info-value">

                <?= e($data['alamat_wajib_pajak']) ?>

            </div>

        </div>



        <div class="info-box">

            <div class="info-label">
                Luas Tanah
            </div>

            <div class="info-value">

                <?= number_format(
                    $data['luas_tanah'],
                    0,
                    ',',
                    '.'
                ) ?>

                m²

            </div>

        </div>


    </div>

</div>



<!-- ==================================================
     ALUR TRANSAKSI
================================================== -->

<div class="card">

    <div class="section-title">

        🌳 Alur Perjalanan Tanah

    </div>


    <?php if(mysqli_num_rows($riwayat) == 0): ?>

        <div class="empty">

            Belum ada transaksi/peralihan tanah.

        </div>

    <?php else: ?>


    <div class="timeline">


        <?php

        $no = 1;

        while($r = mysqli_fetch_assoc($riwayat)):

        ?>


        <div class="timeline-item">


            <div class="timeline-dot">

                <?= $no ?>

            </div>



            <div class="transaction-card">


                <div class="transaction-top">


                    <div class="transaction-title">

                        <?= strtoupper(
                            e($r['jenis_mutasi'])
                        ) ?>

                    </div>


                    <div class="transaction-date">

                        📅

                        <?= date(
                            'd-m-Y',
                            strtotime(
                                $r['tanggal_mutasi']
                            )
                        ) ?>

                    </div>


                </div>



                <div class="transaction-flow">


                    <div class="owner">

                        <?= strtoupper(
                            e($r['dari_pemilik'])
                        ) ?>

                    </div>


                    <div class="arrow">

                        →

                    </div>


                    <div class="owner">

                        <?= strtoupper(
                            e($r['kepada_pemilik'])
                        ) ?>

                    </div>


                </div>



                <div class="transaction-detail">


                    <div class="detail-box">

                        <div class="detail-label">

                            LUAS TRANSAKSI

                        </div>

                        <div class="detail-value">

                            <?= number_format(
                                $r['luas_mutasi'],
                                0,
                                ',',
                                '.'
                            ) ?>

                            m²

                        </div>

                    </div>



                    <div class="detail-box">

                        <div class="detail-label">

                            JENIS

                        </div>

                        <div class="detail-value">

                            <span class="badge">

                                <?= strtoupper(
                                    e($r['jenis_mutasi'])
                                ) ?>

                            </span>

                        </div>

                    </div>



                    <div class="detail-box">

                        <div class="detail-label">

                            KETERANGAN

                        </div>

                        <div class="detail-value">

                            <?= e(
                                $r['keterangan']
                            ) ?>

                        </div>

                    </div>


                </div>


            </div>


        </div>


        <?php

        $no++;

        endwhile;

        ?>


    </div>


    <?php endif; ?>


</div>



<!-- ==================================================
     REKAP KEPEMILIKAN TERAKHIR
================================================== -->

<div class="card">


    <div class="section-title">

        👥 Rekap Kepemilikan Saat Ini

    </div>


    <?php if(count($data_kepemilikan) == 0): ?>


        <div class="empty">

            Belum ada data kepemilikan.

        </div>


    <?php else: ?>


    <div class="owner-grid">


        <?php foreach($data_kepemilikan as $kp): ?>


        <div class="owner-card">


            <div class="owner-name">

                <?= strtoupper(
                    e($kp['nama_pemilik'])
                ) ?>

            </div>


            <div class="owner-area">

                <?= number_format(
                    $kp['luas_dimiliki'],
                    0,
                    ',',
                    '.'
                ) ?>

                m²

            </div>


            <div class="owner-label">

                Luas tanah yang dimiliki saat ini

            </div>


        </div>


        <?php endforeach; ?>


    </div>



    <div class="total-box">


        <div class="total-label">

            TOTAL LUAS KEPEMILIKAN

        </div>


        <div class="total-value">

            <?= number_format(
                $total_kepemilikan,
                0,
                ',',
                '.'
            ) ?>

            m²

        </div>


    </div>


    <?php endif; ?>


</div>



<!-- ==================================================
     TOMBOL
================================================== -->

<div class="card">


    <div class="actions">


        <!-- CETAK SELURUH TINJAUAN -->

        <a href="cetak_tinjau_riwayat.php?id=<?= $id ?>"
           class="btn btn-blue">

            🖨️ Cetak

        </a>



        <!-- KEMBALI KE RIWAYAT -->

        <a href="riwayat_tanah.php?id=<?= $id ?>"
           class="btn btn-green">

            📜 Riwayat Transaksi

        </a>



        <!-- KEMBALI SPPT -->

        <a href="data_sppt.php"
           class="btn btn-dark">

            ⬅️ Kembali Data SPPT

        </a>


    </div>


</div>


</div>


</body>

</html>