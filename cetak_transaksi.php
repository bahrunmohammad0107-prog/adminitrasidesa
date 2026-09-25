<?php

session_start();

include "../cek_login.php";
include "../koneksi.php";

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) die("ID Transaksi tidak valid.");

function e($text) { return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8'); }
function luas($nilai) { return number_format(floatval($nilai), 2, ',', '.'); }

$stmt = mysqli_prepare($koneksi, "SELECT r.*, s.nama_wajib_pajak, s.alamat_objek FROM riwayat_tanah r LEFT JOIN pajak_sppt s ON s.id = r.sppt_id WHERE r.id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$row) die("Data tidak ditemukan.");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Bukti Transaksi Tanah - <?= e($row['nop']) ?></title>
    <style>
        body { font-family: "Times New Roman", Times, serif; padding: 30px 40px; color: #000; line-height: 1.6; }
        .kop { text-align: center; border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 20px; }
        .kop h2 { margin: 0; font-size: 15pt; text-transform: uppercase; }
        .kop p { margin: 2px 0 0; font-size: 10pt; font-style: italic; }
        .title { text-align: center; font-weight: bold; text-decoration: underline; font-size: 13pt; margin-bottom: 20px; text-transform: uppercase; }
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.info td { padding: 5px; vertical-align: top; font-size: 11pt; }
        .ttd-table { width: 100%; margin-top: 35px; border-collapse: collapse; text-align: center; }
        .ttd-table td { width: 50%; padding: 10px; vertical-align: top; }
        .space { height: 70px; }
        @media print { .no-print { display: none; } }
    </style>
    <script>
        window.onload = function() { setTimeout(() => window.print(), 500); };
    </script>
</head>
<body>

<div class="kop">
    <h2>PEMERINTAH KABUPATEN</h2>
    <h3>KANTOR KEPALA DESA / KELURAHAN</h3>
    <p>Surat Keterangan Peralihan / Penguasaan Fisik Sebagian Bidang Tanah</p>
</div>

<div class="title">SURAT BUKTI MUTASI HAK TANAH</div>

<p>Yang bertanda tangan di bawah ini menerangkan dengan sesungguhnya bahwa:</p>

<table class="info">
    <tr>
        <td style="width: 25%;"><strong>Nomor Objek Pajak (NOP)</strong></td>
        <td style="width: 2%;">:</td>
        <td><strong><?= e($row['nop']) ?></strong></td>
    </tr>
    <tr>
        <td><strong>Letak Objek Tanah</strong></td>
        <td>:</td>
        <td><?= e($row['alamat_objek'] ?: '-') ?></td>
    </tr>
    <tr>
        <td><strong>Pihak yang Menyerahkan</strong></td>
        <td>:</td>
        <td><strong><?= e(strtoupper($row['dari_pemilik'])) ?></strong></td>
    </tr>
    <tr>
        <td><strong>Pihak yang Menerima</strong></td>
        <td>:</td>
        <td><strong><?= e(strtoupper($row['kepada_pemilik'])) ?></strong></td>
    </tr>
    <tr>
        <td><strong>Luas yang Dialihkan</strong></td>
        <td>:</td>
        <td><strong><?= luas($row['luas_mutasi']) ?> m²</strong></td>
    </tr>
    <tr>
        <td><strong>Jenis Transaksi / Peralihan</strong></td>
        <td>:</td>
        <td><strong><?= e(strtoupper($row['jenis_mutasi'])) ?></strong></td>
    </tr>
    <tr>
        <td><strong>Tanggal Transaksi</strong></td>
        <td>:</td>
        <td><?= date('d F Y', strtotime($row['tanggal_mutasi'])) ?></td>
    </tr>
    <tr>
        <td><strong>Keterangan</strong></td>
        <td>:</td>
        <td><?= trim($row['keterangan'] ?? '') !== '' ? e($row['keterangan']) : '-' ?></td>
    </tr>
</table>

<p>Demikian surat keterangan mutasi peralihan hak fisik bidang tanah ini dibuat untuk dapat dipergunakan sebagaimana mestinya.</p>

<table class="ttd-table">
    <tr>
        <td>
            Pihak yang Menyerahkan,<br><strong>Pemilik Asal</strong>
            <div class="space"></div>
            ( <strong><?= e(strtoupper($row['dari_pemilik'])) ?></strong> )
        </td>
        <td>
            Pihak yang Menerima,<br><strong>Penerima Hak</strong>
            <div class="space"></div>
            ( <strong><?= e(strtoupper($row['kepada_pemilik'])) ?></strong> )
        </td>
    </tr>
    <tr>
        <td colspan="2" style="padding-top: 30px;">
            Mengetahui,<br><strong>Kepala Desa / Kelurahan</strong>
            <div class="space"></div>
            ( ..................................................... )
        </td>
    </tr>
</table>

</body>
</html>