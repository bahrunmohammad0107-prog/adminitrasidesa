<?php

session_start();

include "../cek_login.php";
include "../koneksi.php";

$desa_id = $_SESSION['desa_id'] ?? 1;
$id      = intval($_GET['id'] ?? 0);


/* =========================================================
   AMBIL DATA SPPT
========================================================= */

$q = mysqli_query(
    $koneksi,
    "
    SELECT *
    FROM pajak_sppt
    WHERE id='$id'
    AND desa_id='$desa_id'
    LIMIT 1
    "
);

if (!$q) {
    die("Gagal mengambil data SPPT: " . mysqli_error($koneksi));
}

$data = mysqli_fetch_assoc($q);

if (!$data) {
    die("Data SPPT tidak ditemukan");
}


/* =========================================================
   PROSES SIMPAN
========================================================= */

if (isset($_POST['simpan'])) {

    $nama_wajib_pajak = trim($_POST['nama_wajib_pajak'] ?? '');
    $pemegang_sppt    = trim($_POST['pemegang_sppt'] ?? '');
    $alamat_wp        = trim($_POST['alamat_wajib_pajak'] ?? '');
    $alamat_objek     = trim($_POST['alamat_objek_pajak'] ?? '');

    $luas_tanah       = intval($_POST['luas_tanah'] ?? 0);
    $luas_bangunan    = intval($_POST['luas_bangunan'] ?? 0);
    $pajak_terhitung  = intval($_POST['pajak_terhitung'] ?? 0);


    /* =====================================================
       VALIDASI
    ===================================================== */

    if ($nama_wajib_pajak === '') {

        echo "
        <script>
        alert('Nama Wajib Pajak tidak boleh kosong.');
        history.back();
        </script>
        ";

        exit;
    }


    /* =====================================================
       ESCAPE
    ===================================================== */

    $nama_wajib_pajak = mysqli_real_escape_string(
        $koneksi,
        $nama_wajib_pajak
    );

    $pemegang_sppt = mysqli_real_escape_string(
        $koneksi,
        $pemegang_sppt
    );

    $alamat_wp = mysqli_real_escape_string(
        $koneksi,
        $alamat_wp
    );

    $alamat_objek = mysqli_real_escape_string(
        $koneksi,
        $alamat_objek
    );


    /* =====================================================
       UPDATE DATA SPPT
       
       TIDAK MENYENTUH:
       - kepemilikan_tanah
       - pemilik_tanah
       - riwayat tanah
       - status pembayaran
       - tanggal pembayaran
       - petugas
       - dusun
       - no_urut
       - NOP
    ===================================================== */

    $update = mysqli_query(
        $koneksi,
        "
        UPDATE pajak_sppt SET

            nama_wajib_pajak='$nama_wajib_pajak',

            pemegang_sppt='$pemegang_sppt',

            alamat_wajib_pajak='$alamat_wp',

            alamat_objek_pajak='$alamat_objek',

            luas_tanah='$luas_tanah',

            luas_bangunan='$luas_bangunan',

            pajak_terhitung='$pajak_terhitung'

        WHERE id='$id'
        AND desa_id='$desa_id'
        "
    );


    if (!$update) {

        die(
            "Gagal menyimpan perubahan: "
            . mysqli_error($koneksi)
        );

    }


    echo "
    <script>

    alert('Data SPPT berhasil diperbarui.');

    window.location='data_sppt.php';

    </script>
    ";

    exit;
}

?>


<!DOCTYPE html>

<html lang="id">

<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Edit SPPT</title>


<style>

*{
    box-sizing:border-box;
}


body{

    margin:0;

    padding:30px;

    font-family:'Segoe UI',Arial,sans-serif;

    background:
    linear-gradient(
        135deg,
        #e0f2fe,
        #f8fafc
    );

}


.card{

    max-width:900px;

    margin:auto;

    background:white;

    border-radius:24px;

    padding:35px;

    box-shadow:
    0 15px 40px rgba(0,0,0,.10);

}


.header{

    display:flex;

    align-items:center;

    gap:15px;

    margin-bottom:25px;

}


.icon{

    width:60px;

    height:60px;

    display:flex;

    align-items:center;

    justify-content:center;

    background:#dbeafe;

    border-radius:18px;

    font-size:30px;

}


h2{

    margin:0;

    color:#1e3a8a;

}


.subtitle{

    margin-top:5px;

    color:#64748b;

    font-size:14px;

}


.info{

    background:#eff6ff;

    border-left:5px solid #2563eb;

    padding:18px;

    margin-bottom:25px;

    border-radius:12px;

}


.info-title{

    font-size:13px;

    color:#64748b;

    margin-bottom:5px;

}


.nop{

    font-size:20px;

    font-weight:800;

    color:#1d4ed8;

    letter-spacing:.5px;

}


.warning{

    background:#fff7ed;

    border-left:5px solid #f97316;

    padding:15px;

    margin-bottom:25px;

    border-radius:10px;

    color:#9a3412;

    line-height:1.6;

}


.form-group{

    margin-bottom:18px;

}


label{

    display:block;

    margin-bottom:7px;

    font-weight:700;

    color:#334155;

}


input,
textarea{

    width:100%;

    padding:13px 15px;

    border:1px solid #cbd5e1;

    border-radius:12px;

    font-size:15px;

    font-family:inherit;

    outline:none;

    transition:.2s;

}


input:focus,
textarea:focus{

    border-color:#2563eb;

    box-shadow:
    0 0 0 3px rgba(37,99,235,.10);

}


textarea{

    min-height:90px;

    resize:vertical;

}


.row{

    display:flex;

    gap:18px;

}


.col{

    flex:1;

}


.buttons{

    display:flex;

    gap:12px;

    margin-top:25px;

}


.btn{

    padding:14px 22px;

    border:none;

    border-radius:12px;

    cursor:pointer;

    font-weight:700;

    font-size:15px;

    text-decoration:none;

    display:inline-flex;

    align-items:center;

    justify-content:center;

}


.simpan{

    background:#16a34a;

    color:white;

    flex:1;

}


.simpan:hover{

    background:#15803d;

}


.kembali{

    background:#111827;

    color:white;

}


.kembali:hover{

    background:#1f2937;

}


@media(max-width:700px){

    body{

        padding:15px;

    }

    .card{

        padding:20px;

        border-radius:18px;

    }

    .row{

        flex-direction:column;

        gap:0;

    }

    .buttons{

        flex-direction:column;

    }

}

</style>

</head>


<body>


<div class="card">


<div class="header">

    <div class="icon">
        ✏️
    </div>

    <div>

        <h2>
            Edit Data SPPT
        </h2>

        <div class="subtitle">
            Perubahan data administrasi SPPT
        </div>

    </div>

</div>


<div class="info">

    <div class="info-title">
        NOMOR OBJEK PAJAK
    </div>

    <div class="nop">
        <?= htmlspecialchars($data['nop'] ?? '') ?>
    </div>

</div>


<div class="warning">

    <b>⚠️ Perhatian</b><br>

    Halaman ini hanya untuk memperbaiki data administrasi SPPT.
    <br>

    <b>Pemilik tanah dan riwayat jual beli, hibah, atau waris
    tidak diubah dari halaman ini.</b>

    <br><br>

    Untuk perubahan pemilik tanah gunakan menu
    <b>Riwayat Tanah</b>.

</div>


<form method="POST">


<div class="form-group">

<label>
Nama Wajib Pajak
</label>

<input
    type="text"
    name="nama_wajib_pajak"
    value="<?= htmlspecialchars($data['nama_wajib_pajak'] ?? '') ?>"
    required
>

</div>


<div class="form-group">

<label>
Pemegang SPPT
</label>

<input
    type="text"
    name="pemegang_sppt"
    value="<?= htmlspecialchars($data['pemegang_sppt'] ?? '') ?>"
    placeholder="Belum ditentukan"
>

<small style="color:#64748b;">
Nama yang tercantum sebagai pemegang SPPT.
</small>

</div>


<div class="form-group">

<label>
Alamat Wajib Pajak
</label>

<textarea
    name="alamat_wajib_pajak"
><?= htmlspecialchars($data['alamat_wajib_pajak'] ?? '') ?></textarea>

</div>


<div class="form-group">

<label>
Alamat Objek Pajak
</label>

<textarea
    name="alamat_objek_pajak"
><?= htmlspecialchars($data['alamat_objek_pajak'] ?? '') ?></textarea>

</div>


<div class="row">


<div class="col">

<div class="form-group">

<label>
Luas Tanah (m²)
</label>

<input
    type="number"
    name="luas_tanah"
    min="0"
    value="<?= intval($data['luas_tanah'] ?? 0) ?>"
>

</div>

</div>


<div class="col">

<div class="form-group">

<label>
Luas Bangunan (m²)
</label>

<input
    type="number"
    name="luas_bangunan"
    min="0"
    value="<?= intval($data['luas_bangunan'] ?? 0) ?>"
>

</div>

</div>


</div>


<div class="form-group">

<label>
Pajak Terhutang (Rp)
</label>

<input
    type="number"
    name="pajak_terhitung"
    min="0"
    value="<?= intval($data['pajak_terhitung'] ?? 0) ?>"
>

</div>


<div class="buttons">


<button
    type="submit"
    name="simpan"
    class="btn simpan"
>

💾 Simpan Perubahan

</button>


<a
    href="data_sppt.php"
    class="btn kembali"
>

⬅ Kembali

</a>


</div>


</form>


</div>


</body>

</html>