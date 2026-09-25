<?php
session_start();
include "../cek_login.php";
include "../koneksi.php";

$desa_id = intval($_SESSION['desa_id'] ?? 1);
$id_trx  = intval($_GET['id'] ?? 0);

if ($id_trx <= 0) {
    die("<div style='padding:40px; font-family:sans-serif; text-align:center;'><h3>ID Transaksi Tidak Valid!</h3><a href='data_sppt.php'>Kembali ke Data SPPT</a></div>");
}

// 1. AMBIL IDENTITAS DESA DARI TABEL profil_desa
$nama_kabupaten = "KEBUMEN";
$nama_kecamatan = "AMBAL";
$nama_desa      = "AMBALKLIWONAN";
$alamat_kantor  = "Jl. Raya Desa Ambalkliwonan, Kecamatan Ambal, Kabupaten Kebumen";
$kode_pos       = "54392";
$no_telp        = "";
$email          = "";
$nama_kades     = "";
$logo_file      = "logo.png";

$q_prof = mysqli_query($koneksi, "SELECT * FROM profil_desa WHERE id = '$desa_id' LIMIT 1");
if ($q_prof && $prof = mysqli_fetch_assoc($q_prof)) {
    if (!empty($prof['kabupaten']))     $nama_kabupaten = strtoupper($prof['kabupaten']);
    if (!empty($prof['kecamatan']))     $nama_kecamatan = strtoupper($prof['kecamatan']);
    if (!empty($prof['nama_desa']))     $nama_desa      = strtoupper($prof['nama_desa']);
    if (!empty($prof['alamat_kantor'])) $alamat_kantor  = $prof['alamat_kantor'];
    if (!empty($prof['kode_pos']))      $kode_pos       = $prof['kode_pos'];
    if (!empty($prof['no_telp']))       $no_telp        = $prof['no_telp'];
    if (!empty($prof['email']))         $email          = $prof['email'];
    if (!empty($prof['nama_kades']))    $nama_kades     = $prof['nama_kades'];
    if (!empty($prof['logo']))          $logo_file      = $prof['logo'];
}

$path_logo_cetak = file_exists("../assets/" . $logo_file) ? "../assets/" . $logo_file : "logo.png";

// 2. AMBIL DATA TRANSAKSI
$q_trx = mysqli_query($koneksi, "
    SELECT r.*, p.nop, p.nama_wajib_pajak, p.alamat_wajib_pajak, p.alamat_objek_pajak, p.blok_tanah, p.luas_tanah, p.pajak_terhitung, d.nama_dusun
    FROM riwayat_tanah r
    JOIN pajak_sppt p ON r.sppt_id = p.id
    LEFT JOIN dusun d ON p.dusun_id = d.id
    WHERE r.id = '$id_trx'
    LIMIT 1
");

$trx = mysqli_fetch_assoc($q_trx);
if (!$trx) {
    die("<div style='padding:40px; font-family:sans-serif; text-align:center;'><h3>Data Transaksi Tidak Ditemukan!</h3><a href='data_sppt.php'>Kembali ke Data SPPT</a></div>");
}

$sppt_id        = $trx['sppt_id'];
$nama_wp_awal   = strtoupper(trim($trx['nama_wajib_pajak']));
$luas_asli_sppt = floatval($trx['luas_tanah']);
$jenis_mutasi   = strtoupper(trim($trx['jenis_mutasi']));

// Nomor Registrasi Manual / Otomatis
$nomor_registrasi_final = !empty($trx['no_registrasi']) 
    ? htmlspecialchars($trx['no_registrasi']) 
    : sprintf("%04d/MUTASI/%s", $trx['id'], date('Y', strtotime($trx['tanggal_mutasi'])));

// 3. HITUNG REKAP SALDO REAL-TIME
$q_all_trx = mysqli_query($koneksi, "SELECT * FROM riwayat_tanah WHERE sppt_id = '$sppt_id' ORDER BY tanggal_mutasi ASC, id ASC");
$daftar_orang = array($nama_wp_awal => true);
$total_keluar = array();
$total_masuk  = array();

while ($t = mysqli_fetch_assoc($q_all_trx)) {
    $dari = strtoupper(trim($t['dari_pemilik']));
    $ke   = strtoupper(trim($t['kepada_pemilik']));
    $luas = floatval($t['luas_mutasi']);

    $daftar_orang[$dari] = true;
    $daftar_orang[$ke]   = true;

    if (!isset($total_keluar[$dari])) $total_keluar[$dari] = 0;
    $total_keluar[$dari] += $luas;

    if (!isset($total_masuk[$ke])) $total_masuk[$ke] = 0;
    $total_masuk[$ke] += $luas;
}

$rekap_pemilik = array();
$total_luas_tercatat = 0;
foreach ($daftar_orang as $nama_p => $val) {
    $masuk  = floatval($total_masuk[$nama_p] ?? 0);
    $keluar = floatval($total_keluar[$nama_p] ?? 0);
    $modal_awal = ($nama_p === $nama_wp_awal) ? $luas_asli_sppt : 0;
    $saldo_sisa = max(0, $modal_awal + $masuk - $keluar);

    if ($saldo_sisa > 0) {
        $rekap_pemilik[] = array(
            'nama' => $nama_p,
            'luas' => $saldo_sisa
        );
        $total_luas_tercatat += $saldo_sisa;
    }
}

// 4. LABEL DINAMIS SESUAI TRANSAKSI
if ($jenis_mutasi === 'HIBAH') {
    $label_pihak_1 = "Yang Menghibahkan / Pemberi";
    $label_pihak_2 = "Penerima Hibah";
    $judul_surat   = "SURAT BUKTI RIWAYAT PERALIHAN HAK TANAH (HIBAH)";
} elseif ($jenis_mutasi === 'WARIS') {
    $label_pihak_1 = "Pemberi Waris / Ahli Waris";
    $label_pihak_2 = "Penerima Waris";
    $judul_surat   = "SURAT BUKTI RIWAYAT PERALIHAN HAK TANAH (WARIS)";
} else {
    $label_pihak_1 = "Pihak Pertama (Penjual)";
    $label_pihak_2 = "Pihak Kedua (Pembeli)";
    $judul_surat   = "SURAT BUKTI RIWAYAT PERALIHAN HAK TANAH (JUAL BELI)";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Riwayat Tanah - <?= htmlspecialchars($trx['nop']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 13px;
            color: #000;
            margin: 0;
            padding: 20px;
            background: #f1f5f9;
            line-height: 1.4;
        }
        .container {
            max-width: 820px;
            margin: 0 auto;
            background: #fff;
            padding: 35px 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border-radius: 6px;
        }

        .action-bar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 12.5px;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s;
        }
        .btn-back { background: #ffffff; color: #475569; border-color: #cbd5e1; }
        .btn-back:hover { background: #f8fafc; color: #0f172a; }
        .btn-download { background: #0284c7; color: #ffffff; }
        .btn-download:hover { background: #0369a1; }
        .btn-print { background: #0f172a; color: #ffffff; }
        .btn-print:hover { background: #1e293b; }

        /* KOP SURAT DINAMIS DENGAN LOGO */
        .kop-surat {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            border-bottom: 3px double #000;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .kop-logo {
            position: absolute;
            left: 5px;
            top: 50%;
            transform: translateY(-50%);
            max-width: 75px;
            max-height: 75px;
            object-fit: contain;
        }
        .kop-teks {
            text-align: center;
            width: 100%;
            padding: 0 80px;
        }
        .kop-teks h3 { margin: 0; font-size: 15px; font-weight: bold; text-transform: uppercase; }
        .kop-teks h2 { margin: 2px 0; font-size: 18px; font-weight: bold; text-transform: uppercase; }
        .kop-teks p  { margin: 0; font-size: 11px; font-style: italic; }

        .judul-dokumen { text-align: center; margin-bottom: 20px; }
        .judul-dokumen h4 { margin: 0; font-size: 15px; font-weight: bold; text-decoration: underline; text-transform: uppercase; }
        .judul-dokumen span { font-size: 12px; font-weight: bold; }

        .section-title {
            font-weight: bold;
            font-size: 13px;
            margin-top: 14px;
            margin-bottom: 6px;
            text-transform: uppercase;
            background: #f2f2f2;
            padding: 4px 8px;
            border-left: 4px solid #000;
        }

        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.data-table td { padding: 4px 6px; vertical-align: top; }

        table.bordered-table { width: 100%; border-collapse: collapse; margin-top: 6px; margin-bottom: 12px; }
        table.bordered-table th, table.bordered-table td { border: 1px solid #000; padding: 5px 8px; font-size: 12.5px; }
        table.bordered-table th { background: #e9e9e9; text-align: center; font-weight: bold; }

        .catatan-box {
            font-size: 11.5px;
            font-style: italic;
            border: 1px dashed #666;
            padding: 8px 12px;
            margin-top: 8px;
            margin-bottom: 20px;
            background: #fafafa;
        }

        .ttd-wrapper { width: 100%; margin-top: 20px; page-break-inside: avoid; }
        .ttd-row { display: flex; justify-content: space-between; text-align: center; margin-bottom: 24px; }
        .ttd-col { width: 45%; }
        .ttd-space { height: 65px; }
        .ttd-name { font-weight: bold; text-decoration: underline; text-transform: uppercase; }

        @media print {
            body { padding: 0; background: #fff; }
            .container { padding: 0; box-shadow: none; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="container">
    
    <!-- BILAH TOMBOL AKSI -->
    <div class="action-bar no-print">
        <a href="riwayat_tanah.php?id=<?= $sppt_id ?>" class="btn-action btn-back">
            <i class="fa fa-arrow-left"></i> Kembali ke Riwayat
        </a>
        <button onclick="window.print()" class="btn-action btn-download">
            <i class="fa fa-file-pdf"></i> Unduh PDF / Simpan
        </button>
        <button onclick="window.print()" class="btn-action btn-print">
            <i class="fa fa-print"></i> Cetak Dokumen Ini
        </button>
    </div>

    <!-- KOP SURAT RESMI DINAMIS -->
    <div class="kop-surat">
        <img src="<?= $path_logo_cetak ?>" alt="Logo" class="kop-logo" onerror="this.style.display='none'">
        <div class="kop-teks">
            <h3>PEMERINTAH KABUPATEN <?= $nama_kabupaten ?></h3>
            <h3>KECAMATAN <?= $nama_kecamatan ?></h3>
            <h2>KANTOR KEPALA DESA <?= $nama_desa ?></h2>
            <p>
                <?= htmlspecialchars($alamat_kantor) ?> - Kode Pos <?= htmlspecialchars($kode_pos) ?>
                <?php if (!empty($no_telp)): ?> | Telp: <?= htmlspecialchars($no_telp) ?><?php endif; ?>
                <?php if (!empty($email)): ?> | Email: <?= htmlspecialchars($email) ?><?php endif; ?>
            </p>
        </div>
    </div>

    <!-- JUDUL DOKUMEN -->
    <div class="judul-dokumen">
        <h4><?= $judul_surat ?></h4>
        <span>Nomor Registrasi Mutasi: <?= $nomor_registrasi_final ?></span>
    </div>

    <!-- A. IDENTITAS OBJEK PAJAK INDUK -->
    <div class="section-title">A. Data Objek Pajak (SPPT Asli)</div>
    <table class="data-table">
        <tr>
            <td style="width: 210px;">Nomor Objek Pajak (NOP)</td>
            <td style="width: 10px;">:</td>
            <td><b><?= htmlspecialchars($trx['nop']) ?></b></td>
        </tr>
        <tr>
            <td>Nama Wajib Pajak Asli</td>
            <td>:</td>
            <td><b><?= strtoupper(htmlspecialchars($nama_wp_awal)) ?></b></td>
        </tr>
        <tr>
            <td>Alamat Wajib Pajak</td>
            <td>:</td>
            <td><?= htmlspecialchars($trx['alamat_wajib_pajak'] ?: '-') ?></td>
        </tr>
        <tr>
            <td>Letak Objek Pajak</td>
            <td>:</td>
            <td><?= htmlspecialchars($trx['alamat_objek_pajak'] ?: '-') ?> (Dusun <?= htmlspecialchars($trx['nama_dusun'] ?: '-') ?> / Blok <?= htmlspecialchars($trx['blok_tanah'] ?: '-') ?>)</td>
        </tr>
        <tr>
            <td>Luas Tanah SPPT Induk</td>
            <td>:</td>
            <td><b><?= number_format($luas_asli_sppt, 0, ',', '.') ?> m²</b></td>
        </tr>
    </table>

    <!-- B. IDENTITAS DUA BELAH PIHAK & PERALIHAN HAK -->
    <div class="section-title">B. Identitas Para Pihak & Rincian Peralihan Hak</div>
    <table class="data-table">
        <tr>
            <td colspan="3" style="background:#f9f9f9; font-weight:bold;">1. <?= $label_pihak_1 ?>:</td>
        </tr>
        <tr>
            <td style="padding-left:15px; width: 210px;">Nama Lengkap</td>
            <td style="width: 10px;">:</td>
            <td><b><?= strtoupper(htmlspecialchars($trx['dari_pemilik'])) ?></b></td>
        </tr>
        <tr>
            <td style="padding-left:15px;">NIK KTP</td>
            <td>:</td>
            <td><b><?= htmlspecialchars($trx['nik_asal'] ?: '-') ?></b></td>
        </tr>
        <tr>
            <td style="padding-left:15px;">Alamat Sesuai KTP</td>
            <td>:</td>
            <td><?= htmlspecialchars($trx['alamat_asal'] ?: '-') ?></td>
        </tr>

        <tr>
            <td colspan="3" style="background:#f9f9f9; font-weight:bold; padding-top:6px;">2. <?= $label_pihak_2 ?>:</td>
        </tr>
        <tr>
            <td style="padding-left:15px;">Nama Lengkap</td>
            <td>:</td>
            <td><b><?= strtoupper(htmlspecialchars($trx['kepada_pemilik'])) ?></b></td>
        </tr>
        <tr>
            <td style="padding-left:15px;">NIK KTP</td>
            <td>:</td>
            <td><b><?= htmlspecialchars($trx['nik_tujuan'] ?: '-') ?></b></td>
        </tr>
        <tr>
            <td style="padding-left:15px;">Alamat Sesuai KTP</td>
            <td>:</td>
            <td><?= htmlspecialchars($trx['alamat_tujuan'] ?: '-') ?></td>
        </tr>

        <tr>
            <td colspan="3" style="background:#f9f9f9; font-weight:bold; padding-top:6px;">3. Rincian Objek Peralihan:</td>
        </tr>
        <tr>
            <td style="padding-left:15px;">Tanggal Transaksi</td>
            <td>:</td>
            <td><b><?= date('d F Y', strtotime($trx['tanggal_mutasi'])) ?></b></td>
        </tr>
        <tr>
            <td style="padding-left:15px;">Jenis Peralihan</td>
            <td>:</td>
            <td><b><?= $jenis_mutasi ?></b></td>
        </tr>
        <tr>
            <td style="padding-left:15px;">Luas yang Dialihkan</td>
            <td>:</td>
            <td><b style="font-size: 14px;"><?= number_format($trx['luas_mutasi'], 2, ',', '.') ?> m²</b></td>
        </tr>
        <tr>
            <td style="padding-left:15px;">Dasar Surat / Keterangan</td>
            <td>:</td>
            <td><?= htmlspecialchars($trx['keterangan'] ?: '-') ?></td>
        </tr>
    </table>

    <!-- C. REKAPITULASI KEPEMILIKAN SAAT INI -->
    <div class="section-title">C. Rekapitulasi Posisi Kepemilikan Tanah Saat Ini</div>
    <table class="bordered-table">
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th>Nama Pemilik / Pemegang Hak</th>
                <th style="width: 160px; text-align: right;">Luas Dimiliki (m²)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no_r = 1;
            foreach ($rekap_pemilik as $rp): 
            ?>
            <tr>
                <td style="text-align: center;"><?= $no_r++ ?></td>
                <td><b><?= strtoupper(htmlspecialchars($rp['nama'])) ?></b></td>
                <td style="text-align: right; font-weight: bold;"><?= number_format($rp['luas'], 0, ',', '.') ?> m²</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background: #f2f2f2; font-weight: bold;">
                <td colspan="2" style="text-align: right;">TOTAL LUAS BIDANG TANAH:</td>
                <td style="text-align: right;"><?= number_format($total_luas_tercatat, 0, ',', '.') ?> m²</td>
            </tr>
        </tfoot>
    </table>

    <div class="catatan-box">
        <b>Catatan:</b> Rekapitulasi di atas adalah mutasi kepemilikan tanah yang sah dan tercatat pada buku administrasi pertanahan Desa. Dokumen ini dicetak sebagai bukti sah transaksi peralihan hak atas bidang tanah tersebut.
    </div>

    <!-- D. TANDA TANGAN LENGKAP MENYESUAIKAN NAMA DESA & KADES -->
    <div class="ttd-wrapper">
        
        <!-- Baris 1: Pihak Pertama & Pihak Kedua -->
        <div class="ttd-row">
            <div class="ttd-col">
                <div><?= $label_pihak_1 ?>,</div>
                <div class="ttd-space"></div>
                <div class="ttd-name"><?= strtoupper(htmlspecialchars($trx['dari_pemilik'])) ?></div>
                <?php if (!empty($trx['nik_asal'])): ?>
                    <small>NIK: <?= htmlspecialchars($trx['nik_asal']) ?></small>
                <?php endif; ?>
            </div>
            <div class="ttd-col">
                <div><?= $label_pihak_2 ?>,</div>
                <div class="ttd-space"></div>
                <div class="ttd-name"><?= strtoupper(htmlspecialchars($trx['kepada_pemilik'])) ?></div>
                <?php if (!empty($trx['nik_tujuan'])): ?>
                    <small>NIK: <?= htmlspecialchars($trx['nik_tujuan']) ?></small>
                <?php endif; ?>
            </div>
        </div>

        <!-- Baris 2: Saksi I & Saksi II -->
        <div class="ttd-row">
            <div class="ttd-col">
                <div>Saksi I,</div>
                <div class="ttd-space"></div>
                <div class="ttd-name">( .................................................. )</div>
            </div>
            <div class="ttd-col">
                <div>Saksi II,</div>
                <div class="ttd-space"></div>
                <div class="ttd-name">( .................................................. )</div>
            </div>
        </div>

        <!-- Baris 3: Mengetahui Kepala Desa Dinamis -->
        <div style="text-align: center; margin-top: 10px;">
            <div><?= ucwords(strtolower($nama_desa)) ?>, <?= date('d F Y', strtotime($trx['tanggal_mutasi'])) ?></div>
            <div style="font-weight: bold; margin-top: 2px;">Mengetahui,</div>
            <div style="font-weight: bold;">Kepala Desa <?= ucwords(strtolower($nama_desa)) ?></div>
            <div class="ttd-space" style="height: 70px;"></div>
            <div class="ttd-name" style="display: inline-block; min-width: 220px;">
                <?= !empty($nama_kades) ? strtoupper(htmlspecialchars($nama_kades)) : '( .................................................. )' ?>
            </div>
        </div>

    </div>

</div>

</body>
</html>