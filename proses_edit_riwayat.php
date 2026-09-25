<?php

session_start();

include "../cek_login.php";
include "../koneksi.php";


/*
=========================================================
CEK LOGIN / DESA
=========================================================
*/

$desa_id = intval($_SESSION['desa_id'] ?? 1);


/*
=========================================================
FUNGSI
=========================================================
*/

function kembali($sppt_id, $pesan)
{
    $pesan = urlencode($pesan);

    header(
        "Location: edit_riwayat.php?id=" .
        intval($sppt_id) .
        "&error=" .
        $pesan
    );

    exit;
}


/*
=========================================================
HARUS POST
=========================================================
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: data_sppt.php");

    exit;
}


/*
=========================================================
AMBIL DATA FORM
=========================================================
*/

$id = intval(
    $_POST['id'] ?? 0
);

$sppt_id = intval(
    $_POST['sppt_id'] ?? 0
);

$jenis_mutasi = trim(
    $_POST['jenis_mutasi'] ?? ''
);

$tanggal_mutasi = trim(
    $_POST['tanggal_mutasi'] ?? ''
);

$dari_pemilik = trim(
    $_POST['dari_pemilik'] ?? ''
);

$kepada_pemilik = trim(
    $_POST['kepada_pemilik'] ?? ''
);

$luas_mutasi = floatval(
    $_POST['luas_mutasi'] ?? 0
);

$keterangan = trim(
    $_POST['keterangan'] ?? ''
);


/*
=========================================================
VALIDASI DASAR
=========================================================
*/

if ($id <= 0) {

    die("ID transaksi tidak ditemukan.");

}


if ($sppt_id <= 0) {

    die("ID SPPT tidak ditemukan.");

}


if ($jenis_mutasi === '') {

    kembali(
        $sppt_id,
        "Jenis transaksi belum dipilih."
    );

}


if ($tanggal_mutasi === '') {

    kembali(
        $sppt_id,
        "Tanggal transaksi belum diisi."
    );

}


if ($dari_pemilik === '') {

    kembali(
        $sppt_id,
        "Pemilik asal belum dipilih."
    );

}


if ($kepada_pemilik === '') {

    kembali(
        $sppt_id,
        "Pemilik tujuan belum diisi."
    );

}


if (
    strtoupper($dari_pemilik)
    ===
    strtoupper($kepada_pemilik)
) {

    kembali(
        $sppt_id,
        "Pemilik asal dan tujuan tidak boleh sama."
    );

}


if ($luas_mutasi <= 0) {

    kembali(
        $sppt_id,
        "Luas transaksi harus lebih dari 0 m²."
    );

}


/*
=========================================================
VALIDASI TANGGAL
=========================================================
*/

$tanggal_obj =
    DateTime::createFromFormat(
        'Y-m-d',
        $tanggal_mutasi
    );

if (
    !$tanggal_obj ||
    $tanggal_obj->format('Y-m-d')
    !==
    $tanggal_mutasi
) {

    kembali(
        $sppt_id,
        "Format tanggal transaksi tidak valid."
    );

}


/*
=========================================================
CEK TRANSAKSI LAMA
=========================================================
*/

$stmt = mysqli_prepare(
    $koneksi,
    "
    SELECT
        r.*,
        s.desa_id,
        s.luas_tanah

    FROM riwayat_tanah r

    INNER JOIN pajak_sppt s
        ON s.id = r.sppt_id

    WHERE r.id = ?
    AND r.sppt_id = ?
    AND s.desa_id = ?

    LIMIT 1
    "
);


if (!$stmt) {

    die(
        "Query transaksi gagal: " .
        mysqli_error($koneksi)
    );

}


mysqli_stmt_bind_param(
    $stmt,
    "iii",
    $id,
    $sppt_id,
    $desa_id
);


mysqli_stmt_execute($stmt);


$result =
    mysqli_stmt_get_result($stmt);


$lama =
    mysqli_fetch_assoc($result);


mysqli_stmt_close($stmt);


if (!$lama) {

    die(
        "Transaksi tidak ditemukan atau bukan milik desa ini."
    );

}


/*
=========================================================
CEK LUAS TIDAK MELEBIHI LUAS SPPT
=========================================================
*/

$luas_sppt =
    floatval(
        $lama['luas_tanah'] ?? 0
    );


if (
    $luas_sppt > 0 &&
    $luas_mutasi > $luas_sppt
) {

    kembali(
        $sppt_id,
        "Luas transaksi tidak boleh lebih besar dari luas SPPT."
    );

}


/*
=========================================================
NORMALISASI NAMA
=========================================================
*/

$dari_pemilik =
    preg_replace(
        '/\s+/',
        ' ',
        $dari_pemilik
    );

$kepada_pemilik =
    preg_replace(
        '/\s+/',
        ' ',
        $kepada_pemilik
    );


/*
=========================================================
TRANSAKSI DATABASE
=========================================================
*/

mysqli_begin_transaction($koneksi);


try {


    /*
    =====================================================
    UPDATE RIWAYAT
    =====================================================
    */

    $stmt = mysqli_prepare(
        $koneksi,
        "
        UPDATE riwayat_tanah

        SET
            dari_pemilik = ?,
            kepada_pemilik = ?,
            luas_mutasi = ?,
            jenis_mutasi = ?,
            tanggal_mutasi = ?,
            keterangan = ?

        WHERE id = ?
        AND sppt_id = ?

        LIMIT 1
        "
    );


    if (!$stmt) {

        throw new Exception(
            "Gagal menyiapkan query update."
        );

    }


    mysqli_stmt_bind_param(
        $stmt,
        "ssdsssii",
        $dari_pemilik,
        $kepada_pemilik,
        $luas_mutasi,
        $jenis_mutasi,
        $tanggal_mutasi,
        $keterangan,
        $id,
        $sppt_id
    );


    if (
        !mysqli_stmt_execute($stmt)
    ) {

        throw new Exception(
            mysqli_stmt_error($stmt)
        );

    }


    mysqli_stmt_close($stmt);


    /*
    =====================================================
    COMMIT
    =====================================================
    */

    mysqli_commit($koneksi);


    /*
    =====================================================
    KEMBALI KE RIWAYAT
    =====================================================
    */

    header(
        "Location: riwayat_tanah.php?id=" .
        $sppt_id
    );

    exit;


} catch (Exception $e) {


    /*
    =====================================================
    ROLLBACK
    =====================================================
    */

    mysqli_rollback($koneksi);


    kembali(
        $sppt_id,
        "Gagal menyimpan perubahan: " .
        $e->getMessage()
    );

}

?>