<?php

session_start();

include "../cek_login.php";
include "../koneksi.php";

$desa_id = intval($_SESSION['desa_id'] ?? 1);

function kembali($sppt_id, $pesan)
{
    header("Location: tambah_riwayat.php?id=" . intval($sppt_id) . "&error=" . urlencode($pesan));
    exit;
}

// 1. Ambil Input Form
$sppt_id        = intval($_POST['sppt_id'] ?? 0);
$jenis_mutasi   = trim($_POST['jenis_mutasi'] ?? '');
$tanggal_mutasi = trim($_POST['tanggal_mutasi'] ?? '');
$dari_pemilik   = trim($_POST['dari_pemilik'] ?? '');
$kepada_pemilik = trim($_POST['kepada_pemilik'] ?? '');
$luas_mutasi    = floatval($_POST['luas_mutasi'] ?? 0);
$keterangan     = trim($_POST['keterangan'] ?? '');

$nik_asal       = trim($_POST['nik_asal'] ?? '');
$alamat_asal    = trim($_POST['alamat_asal'] ?? '');
$nik_baru       = trim($_POST['nik_baru'] ?? '');
$alamat_baru    = trim($_POST['alamat_baru'] ?? '');

// 2. Validasi Input
if ($sppt_id <= 0) die("ID SPPT tidak valid.");
if ($jenis_mutasi === '') kembali($sppt_id, "Jenis transaksi belum dipilih.");
if ($tanggal_mutasi === '') kembali($sppt_id, "Tanggal transaksi belum diisi.");
if ($dari_pemilik === '') kembali($sppt_id, "Nama pemilik asal belum diisi.");
if ($kepada_pemilik === '') kembali($sppt_id, "Nama pemilik tujuan belum diisi.");
if (strcasecmp($dari_pemilik, $kepada_pemilik) === 0) {
    kembali($sppt_id, "Pemilik asal dan penerima hak tidak boleh orang yang sama.");
}
if ($luas_mutasi <= 0) kembali($sppt_id, "Luas transaksi harus lebih dari 0 m².");

// 3. Ambil Info SPPT & NOP
$stmt = mysqli_prepare($koneksi, "SELECT id, nop, luas_tanah FROM pajak_sppt WHERE id = ? AND desa_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "ii", $sppt_id, $desa_id);
mysqli_stmt_execute($stmt);
$sppt = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$sppt) kembali($sppt_id, "Data SPPT tidak ditemukan.");

$nop       = $sppt['nop'] ?? '';
$luas_sppt = floatval($sppt['luas_tanah'] ?? 0);

if ($luas_sppt <= 0) kembali($sppt_id, "Luas tanah pada SPPT belum diisi.");
if ($luas_mutasi > $luas_sppt) {
    kembali($sppt_id, "Luas transaksi tidak boleh melebihi luas SPPT (" . number_format($luas_sppt, 2, ',', '.') . " m²).");
}

mysqli_begin_transaction($koneksi);

try {
    // 4. Pastikan Master Pemilik Asal Terdaftar
    $stmt = mysqli_prepare($koneksi, "SELECT id FROM pemilik_tanah WHERE LOWER(TRIM(nama_pemilik)) = LOWER(TRIM(?)) LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $dari_pemilik);
    mysqli_stmt_execute($stmt);
    $res_asal = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$res_asal) {
        $stmt = mysqli_prepare($koneksi, "INSERT INTO pemilik_tanah (nama_pemilik, nik, alamat) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sss", $dari_pemilik, $nik_asal, $alamat_asal);
        mysqli_stmt_execute($stmt);
        $pemilik_asal_id = intval(mysqli_insert_id($koneksi));
        mysqli_stmt_close($stmt);
    } else {
        $pemilik_asal_id = intval($res_asal['id']);
    }

    // 5. Cek Saldo Kepemilikan Asal
    $stmt = mysqli_prepare($koneksi, "
        SELECT id, luas_dimiliki 
        FROM kepemilikan_tanah 
        WHERE (nop = ? OR sppt_id = ?) 
          AND pemilik_id = ? 
          AND luas_dimiliki > 0 
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, "sii", $nop, $sppt_id, $pemilik_asal_id);
    mysqli_stmt_execute($stmt);
    $kepemilikan_asal = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    // Inisialisasi jika transaksi pertama
    if (!$kepemilikan_asal) {
        $stmt = mysqli_prepare($koneksi, "SELECT COUNT(*) as total FROM kepemilikan_tanah WHERE nop = ? OR sppt_id = ?");
        mysqli_stmt_bind_param($stmt, "si", $nop, $sppt_id);
        mysqli_stmt_execute($stmt);
        $cek_total = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (intval($cek_total['total'] ?? 0) === 0) {
            $status_aktif = 'AKTIF';
            $stmt = mysqli_prepare($koneksi, "INSERT INTO kepemilikan_tanah (sppt_id, nop, pemilik_id, luas_dimiliki, status) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "isids", $sppt_id, $nop, $pemilik_asal_id, $luas_sppt, $status_aktif);
            mysqli_stmt_execute($stmt);
            $kepemilikan_asal_id = intval(mysqli_insert_id($koneksi));
            $luas_asal = $luas_sppt;
            mysqli_stmt_close($stmt);
        } else {
            throw new Exception("Pemilik " . strtoupper($dari_pemilik) . " tidak memiliki saldo sisa tanah aktif.");
        }
    } else {
        $kepemilikan_asal_id = intval($kepemilikan_asal['id']);
        $luas_asal           = floatval($kepemilikan_asal['luas_dimiliki']);
    }

    // 6. Validasi Sisa Luas
    if ($luas_mutasi > $luas_asal) {
        throw new Exception("Luas yang dialihkan (" . number_format($luas_mutasi, 2, ',', '.') . " m²) melebihi sisa tanah milik " . strtoupper($dari_pemilik) . " (" . number_format($luas_asal, 2, ',', '.') . " m²).");
    }

    // 7. Potong Luas Pemilik Asal (Gunakan status 'AKTIF' jika > 0, atau 'NONAKTIF' jika 0)
    $sisa_luas = max(0.0, $luas_asal - $luas_mutasi);
    $status_asal = ($sisa_luas > 0) ? 'AKTIF' : 'NONAKTIF';

    $stmt = mysqli_prepare($koneksi, "UPDATE kepemilikan_tanah SET luas_dimiliki = ?, status = ? WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "dsi", $sisa_luas, $status_asal, $kepemilikan_asal_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // 8. Pastikan Master Pemilik Tujuan Terdaftar
    $stmt = mysqli_prepare($koneksi, "SELECT id, nik, alamat FROM pemilik_tanah WHERE LOWER(TRIM(nama_pemilik)) = LOWER(TRIM(?)) LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $kepada_pemilik);
    mysqli_stmt_execute($stmt);
    $res_tujuan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$res_tujuan) {
        $stmt = mysqli_prepare($koneksi, "INSERT INTO pemilik_tanah (nama_pemilik, nik, alamat) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sss", $kepada_pemilik, $nik_baru, $alamat_baru);
        mysqli_stmt_execute($stmt);
        $pemilik_tujuan_id = intval(mysqli_insert_id($koneksi));
        mysqli_stmt_close($stmt);
    } else {
        $pemilik_tujuan_id = intval($res_tujuan['id']);
    }

    // 9. Tambah Luas ke Pemilik Tujuan
    $stmt = mysqli_prepare($koneksi, "
        SELECT id, luas_dimiliki 
        FROM kepemilikan_tanah 
        WHERE (nop = ? OR sppt_id = ?) 
          AND pemilik_id = ? 
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, "sii", $nop, $sppt_id, $pemilik_tujuan_id);
    mysqli_stmt_execute($stmt);
    $kepemilikan_tujuan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    $status_aktif = 'AKTIF';
    if ($kepemilikan_tujuan) {
        $luas_tujuan_baru = floatval($kepemilikan_tujuan['luas_dimiliki']) + $luas_mutasi;

        $stmt = mysqli_prepare($koneksi, "UPDATE kepemilikan_tanah SET luas_dimiliki = ?, status = ? WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "dsi", $luas_tujuan_baru, $status_aktif, $kepemilikan_tujuan['id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        $stmt = mysqli_prepare($koneksi, "INSERT INTO kepemilikan_tanah (sppt_id, nop, pemilik_id, luas_dimiliki, status) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isids", $sppt_id, $nop, $pemilik_tujuan_id, $luas_mutasi, $status_aktif);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // 10. Catat ke Tabel Riwayat
    $stmt = mysqli_prepare($koneksi, "
        INSERT INTO riwayat_tanah (sppt_id, nop, dari_pemilik, kepada_pemilik, luas_mutasi, jenis_mutasi, tanggal_mutasi, keterangan) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    mysqli_stmt_bind_param($stmt, "isssdsss", $sppt_id, $nop, $dari_pemilik, $kepada_pemilik, $luas_mutasi, $jenis_mutasi, $tanggal_mutasi, $keterangan);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_commit($koneksi);

    header("Location: riwayat_tanah.php?id=" . $sppt_id . "&success=" . urlencode("Transaksi dari " . strtoupper($dari_pemilik) . " ke " . strtoupper($kepada_pemilik) . " (" . number_format($luas_mutasi, 2, ',', '.') . " m²) berhasil disimpan.") . "&fokus=" . urlencode($kepada_pemilik));
    exit;

} catch (Throwable $e) {
    mysqli_rollback($koneksi);
    kembali($sppt_id, $e->getMessage());
}