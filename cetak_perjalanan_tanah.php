<?php

session_start();

include "../cek_login.php";
include "../koneksi.php";

$desa_id = intval($_SESSION['desa_id'] ?? 1);
$id      = intval($_GET['id'] ?? 0);
$fokus   = trim($_GET['fokus'] ?? '');

if ($id <= 0) {
    die("ID SPPT tidak ditemukan.");
}

/* =========================================================
   FUNGSI BANTUAN
========================================================= */

function e($text)
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

function luas($nilai)
{
    return number_format(floatval($nilai), 2, ',', '.');
}

/* =========================================================
   AMBIL DATA SPPT TAHUNAN
========================================================= */

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT * FROM pajak_sppt WHERE id = ? AND desa_id = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "ii", $id, $desa_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$sppt   = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$sppt) {
    die("Data SPPT tidak ditemukan di database.");
}

$nop          = $sppt['nop'] ?? '';
$nama_wp      = $sppt['nama_wajib_pajak'] ?? $sppt['nama_wp'] ?? '';
$alamat_wp    = $sppt['alamat_wajib_pajak'] ?? $sppt['alamat_wp'] ?? '-';
$alamat_objek = $sppt['alamat_objek'] ?? $sppt['alamat_objek_pajak'] ?? $sppt['alamat_tanah'] ?? '-';
$luas_sppt    = floatval($sppt['luas_tanah'] ?? 0);

/* =========================================================
   AMBIL SEMUA RIWAYAT TRANSAKSI (NOP & SPPT_ID)
========================================================= */

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT * FROM riwayat_tanah 
     WHERE (nop = ? AND nop != '') OR sppt_id = ? 
     ORDER BY tanggal_mutasi ASC, id ASC"
);
mysqli_stmt_bind_param($stmt, "si", $nop, $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$riwayat = [];
while ($row = mysqli_fetch_assoc($result)) {
    $riwayat[] = $row;
}
mysqli_stmt_close($stmt);

/* =========================================================
   AMBIL SEMUA PEMEGANG HAK FISIK AKTIF
========================================================= */

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT k.luas_dimiliki, p.nama_pemilik, p.nik, p.alamat 
     FROM kepemilikan_tanah k
     INNER JOIN pemilik_tanah p ON p.id = k.pemilik_id
     WHERE ((k.nop = ? AND k.nop != '') OR k.sppt_id = ?) 
       AND k.luas_dimiliki > 0 AND k.status = 'AKTIF'
     ORDER BY k.id ASC"
);
mysqli_stmt_bind_param($stmt, "si", $nop, $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$pemilik_aktif = [];
$data_fokus = null;

while ($row = mysqli_fetch_assoc($result)) {
    $pemilik_aktif[] = $row;
    if ($fokus !== '' && strcasecmp(trim($row['nama_pemilik']), trim($fokus)) === 0) {
        $data_fokus = $row;
    }
}
mysqli_stmt_close($stmt);

/* Cari transaksi terakhir terkait orang yang difokuskan */
$transaksi_fokus = null;
if (!empty($riwayat)) {
    if ($fokus !== '') {
        for ($i = count($riwayat) - 1; $i >= 0; $i--) {
            if (strcasecmp($riwayat[$i]['kepada_pemilik'], $fokus) === 0 || strcasecmp($riwayat[$i]['dari_pemilik'], $fokus) === 0) {
                $transaksi_fokus = $riwayat[$i];
                break;
            }
        }
    }
    if (!$transaksi_fokus) {
        $transaksi_fokus = $riwayat[count($riwayat) - 1];
    }
}

$nama_pihak_asal     = $transaksi_fokus ? strtoupper($transaksi_fokus['dari_pemilik']) : strtoupper($nama_wp);
$nama_pihak_penerima = $transaksi_fokus ? strtoupper($transaksi_fokus['kepada_pemilik']) : ($fokus !== '' ? strtoupper($fokus) : '-');

/* =========================================================
   SUSUN DIAGRAM POHON (MERMAID)
========================================================= */

$diagram_nodes = [];
$diagram_edges = [];

foreach ($riwayat as $r) {
    $dari    = strtoupper(trim($r['dari_pemilik'] ?? ''));
    $kepada  = strtoupper(trim($r['kepada_pemilik'] ?? ''));
    $jenis   = strtoupper(trim($r['jenis_mutasi'] ?? 'MUTASI'));
    $tgl_raw = $r['tanggal_mutasi'] ?? '';
    $tgl     = ($tgl_raw !== '' && $tgl_raw !== '0000-00-00') ? date('d/m/Y', strtotime($tgl_raw)) : '-';
    $l_mut   = luas($r['luas_mutasi'] ?? 0) . " m²";

    if ($dari !== '' && $kepada !== '') {
        $id_dari   = 'P_' . substr(md5($dari), 0, 10);
        $id_kepada = 'P_' . substr(md5($kepada), 0, 10);

        $diagram_nodes[$id_dari]   = "{$id_dari}[\"👤 {$dari}\"]";
        $diagram_nodes[$id_kepada] = "{$id_kepada}[\"👤 {$kepada}\"]";

        $label_edge = "{$tgl}<br/><b>{$jenis}</b><br/>{$l_mut}";
        $diagram_edges[] = "{$id_dari} -->|\"{$label_edge}\"| {$id_kepada}";
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Riwayat Tanah - <?= e($nop) ?></title>
    
    <!-- Mermaid Render Engine -->
    <script type="module">
        import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs';
        mermaid.initialize({ 
            startOnLoad: true, 
            theme: 'base',
            themeVariables: {
                primaryColor: '#ffffff',
                primaryTextColor: '#000000',
                primaryBorderColor: '#000000',
                lineColor: '#000000',
                edgeLabelBackground: '#ffffff',
                fontSize: '11px'
            }
        });

        window.addEventListener('load', () => {
            setTimeout(() => {
                window.print();
            }, 800);
        });
    </script>

    <style>
        * { box-sizing: border-box; }
        body {
            font-family: "Times New Roman", Times, serif;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 20px 35px;
            font-size: 11pt;
            line-height: 1.4;
        }

        .kop-surat {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 10px;
            margin-bottom: 18px;
        }
        .kop-surat h2 { margin: 0; font-size: 15pt; font-weight: bold; text-transform: uppercase; }
        .kop-surat h3 { margin: 2px 0; font-size: 13pt; font-weight: bold; text-transform: uppercase; }
        .kop-surat p { margin: 2px 0; font-size: 9.5pt; font-style: italic; }

        .judul-dokumen {
            text-align: center;
            font-weight: bold;
            font-size: 12.5pt;
            text-decoration: underline;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        .table-info {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }
        .table-info td {
            padding: 3px 6px;
            vertical-align: top;
            font-size: 10.5pt;
        }
        .table-info td.label {
            width: 25%;
            font-weight: bold;
        }
        .table-info td.separator {
            width: 2%;
        }

        .focus-box {
            border: 2px solid #000;
            background-color: #f8fafc;
            padding: 10px 14px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .focus-title {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 11pt;
            margin-bottom: 4px;
        }

        .section-title {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
            margin: 14px 0 8px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #000;
            padding: 5px 7px;
            font-size: 10pt;
        }
        table.data-table th {
            background-color: #f2f2f2 !important;
            text-align: center;
            font-weight: bold;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .diagram-container {
            width: 100%;
            border: 1px solid #000;
            padding: 10px;
            text-align: center;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        .diagram-container svg {
            max-width: 100% !important;
            height: auto !important;
        }

        /* STRUKTUR TANDA TANGAN LENGKAP */
        .ttd-wrapper {
            width: 100%;
            margin-top: 30px;
            page-break-inside: avoid;
        }
        .ttd-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .ttd-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 10.5pt;
            padding: 5px 15px;
        }
        .ttd-space {
            height: 65px;
        }

        .no-print {
            position: fixed;
            top: 15px;
            right: 15px;
            display: flex;
            gap: 10px;
            background: #fff;
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
            z-index: 9999;
        }
        .btn-print {
            background: #16a34a;
            color: #fff;
            border: none;
            padding: 8px 14px;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-close {
            background: #64748b;
            color: #fff;
            border: none;
            padding: 8px 14px;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; margin: 0; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button class="btn-print" onclick="window.print()">🖨️ Cetak Ulang</button>
    <a href="riwayat_tanah.php?id=<?= $id ?>" class="btn-close">✕ Tutup</a>
</div>

<!-- KOP RESMI DESA -->
<div class="kop-surat">
    <h2>PEMERINTAH KABUPATEN</h2>
    <h3>KANTOR KEPALA DESA / KELURAHAN</h3>
    <p>Buku Register & Lembar Catatan Riwayat Penguasaan Fisik Bidang Tanah</p>
</div>

<div class="judul-dokumen">
    <?= $fokus !== '' ? 'SURAT KETERANGAN PENGUASAAN FISIK & RIWAYAT TANAH' : 'LEMBAR BUKU REGISTER RIWAYAT PERJALANAN TANAH' ?>
</div>

<!-- INFORMASI BIDANG TANAH / SPPT -->
<table class="table-info">
    <tr>
        <td class="label">Nomor Objek Pajak (NOP)</td>
        <td class="separator">:</td>
        <td><strong><?= e($nop) ?></strong></td>
        <td class="label">Luas SPPT Terdaftar</td>
        <td class="separator">:</td>
        <td><strong><?= luas($luas_sppt) ?> m²</strong></td>
    </tr>
    <tr>
        <td class="label">Wajib Pajak SPPT Terdaftar</td>
        <td class="separator">:</td>
        <td><?= e(strtoupper($nama_wp)) ?></td>
        <td class="label">Letak Objek Pajak</td>
        <td class="separator">:</td>
        <td><?= e($alamat_objek) ?></td>
    </tr>
    <tr>
        <td class="label">Alamat Wajib Pajak</td>
        <td class="separator">:</td>
        <td colspan="4"><?= e($alamat_wp) ?></td>
    </tr>
</table>

<!-- JIKA CETAK FOKUS PADA SATU PEMILIK -->
<?php if ($data_fokus): ?>
<div class="focus-box">
    <div class="focus-title">📌 KETERANGAN HAK PEMEGANG TANAH SAAT INI:</div>
    <table style="width:100%; font-size:10.5pt;">
        <tr>
            <td style="width:25%;"><strong>Nama Pemegang Hak</strong></td>
            <td style="width:2%;">:</td>
            <td><strong><?= e(strtoupper($data_fokus['nama_pemilik'])) ?></strong></td>
            <td style="width:25%;"><strong>Sisa Tanah Dikuasai</strong></td>
            <td style="width:2%;">:</td>
            <td><strong style="font-size:12pt;"><?= luas($data_fokus['luas_dimiliki']) ?> m²</strong></td>
        </tr>
        <tr>
            <td>NIK</td>
            <td>:</td>
            <td><?= e($data_fokus['nik'] ?: '-') ?></td>
            <td>Alamat Domisili</td>
            <td>:</td>
            <td><?= e($data_fokus['alamat'] ?: '-') ?></td>
        </tr>
    </table>
</div>
<?php endif; ?>

<!-- BAGIAN 1: GRAFIK DIAGRAM ALUR (MERMAID) -->
<?php if (!empty($diagram_edges)): ?>
<div class="section-title">I. Bagan Silsilah & Pohon Perjalanan Tanah</div>
<div class="diagram-container">
    <pre class="mermaid" style="background:transparent; border:none; margin:0;">
flowchart LR
    classDef default fill:#ffffff,stroke:#000000,stroke-width:1.5px,color:#000000,font-weight:bold,rx:5,ry:5;

    <?php foreach ($diagram_nodes as $node): ?>
    <?= $node . "\n"; ?>
    <?php endforeach; ?>

    <?php foreach ($diagram_edges as $edge): ?>
    <?= $edge . "\n"; ?>
    <?php endforeach; ?>
    </pre>
</div>
<?php endif; ?>

<!-- BAGIAN 2: DAFTAR KRONOLOGIS TRANSAKSI -->
<div class="section-title">II. Rincian Kronologis Transaksi & Peralihan Hak</div>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 5%;">No</th>
            <th style="width: 13%;">Tanggal</th>
            <th style="width: 22%;">Dari Pemilik</th>
            <th style="width: 22%;">Kepada Pemilik</th>
            <th style="width: 12%;">Luas Mutasi</th>
            <th style="width: 12%;">Jenis Peralihan</th>
            <th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($riwayat)): ?>
        <tr>
            <td colspan="7" style="text-align: center; font-style: italic;">Belum ada catatan mutasi riwayat untuk bidang tanah ini.</td>
        </tr>
        <?php else: ?>
            <?php foreach ($riwayat as $idx => $r): 
                $isFokusRow = ($fokus !== '' && (strcasecmp($r['dari_pemilik'], $fokus) === 0 || strcasecmp($r['kepada_pemilik'], $fokus) === 0));
            ?>
            <tr <?= $isFokusRow ? 'style="background-color:#f8fafc; font-weight:bold;"' : '' ?>>
                <td style="text-align: center;"><?= $idx + 1 ?></td>
                <td style="text-align: center;"><?= date('d/m/Y', strtotime($r['tanggal_mutasi'])) ?></td>
                <td><?= e(strtoupper($r['dari_pemilik'])) ?></td>
                <td><?= e(strtoupper($r['kepada_pemilik'])) ?></td>
                <td style="text-align: right;"><?= luas($r['luas_mutasi']) ?> m²</td>
                <td style="text-align: center;"><?= e(strtoupper($r['jenis_mutasi'])) ?></td>
                <td><?= trim($r['keterangan'] ?? '') !== '' ? e($r['keterangan']) : '-' ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<!-- BAGIAN 3: REKAPITULASI SISA TANAH SEMUA PEMILIK -->
<div class="section-title">III. Rekapitulasi Sisa Tanah Seluruh Pemilik Aktif</div>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 5%;">No</th>
            <th style="width: 30%;">Nama Pemilik Fisik</th>
            <th style="width: 20%;">NIK</th>
            <th>Alamat Domisili</th>
            <th style="width: 18%;">Sisa Tanah Dikuasai</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($pemilik_aktif)): ?>
        <tr>
            <td colspan="5" style="text-align: center; font-style: italic;">Data pemegang hak belum tercatat.</td>
        </tr>
        <?php else: ?>
            <?php foreach ($pemilik_aktif as $idx => $pa): 
                $isFokusOwner = ($fokus !== '' && strcasecmp($pa['nama_pemilik'], $fokus) === 0);
            ?>
            <tr <?= $isFokusOwner ? 'style="background-color:#f1f5f9; font-weight:bold;"' : '' ?>>
                <td style="text-align: center;"><?= $idx + 1 ?></td>
                <td><?= e(strtoupper($pa['nama_pemilik'])) ?> <?= $isFokusOwner ? '(★ Pemilik Terpilih)' : '' ?></td>
                <td><?= e($pa['nik'] ?: '-') ?></td>
                <td><?= e($pa['alamat'] ?: '-') ?></td>
                <td style="text-align: right; font-weight: bold;"><?= luas($pa['luas_dimiliki']) ?> m²</td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<!-- =========================================================
     BAGIAN TANDA TANGAN PARA PIHAK, SAKSI & KEPALA DESA
========================================================= -->
<div class="ttd-wrapper">
    <div style="text-align: right; margin-bottom: 15px; font-size: 10.5pt;">
        Dibuat di: ......................................., Tanggal: <?= date('d F Y') ?>
    </div>

    <!-- BARIS 1: PEMILIK ASAL & PENERIMA HAK -->
    <table class="ttd-table">
        <tr>
            <td>
                Pihak yang Menyerahkan Tanah /<br>
                <strong>Pemilik Tanah Asal</strong>
                <div class="ttd-space"></div>
                ( <strong><?= e($nama_pihak_asal) ?></strong> )
            </td>
            <td>
                Pihak yang Menerima Hak /<br>
                <strong>Penerima Tanah</strong>
                <div class="ttd-space"></div>
                ( <strong><?= e($nama_pihak_penerima) ?></strong> )
            </td>
        </tr>
    </table>

    <!-- BARIS 2: SAKSI 1 & SAKSI 2 -->
    <table class="ttd-table">
        <tr>
            <td>
                Saksi I<br>
                ( Perangkat Desa / Tokoh Masyarakat )
                <div class="ttd-space"></div>
                ( ..................................................... )
            </td>
            <td>
                Saksi II<br>
                ( Batas Tanah / Saksi Keluarga )
                <div class="ttd-space"></div>
                ( ..................................................... )
            </td>
        </tr>
    </table>

    <!-- BARIS 3: MENGETAHUI KEPALA DESA / LURAH -->
    <table class="ttd-table" style="margin-top: 10px;">
        <tr>
            <td colspan="2" style="width: 100%;">
                Mengetahui,<br>
                <strong>KEPALA DESA / KELURAHAN</strong>
                <div class="ttd-space" style="height: 75px;"></div>
                ( <strong>.....................................................</strong> )<br>
                <span style="font-size: 9.5pt;">NIP. .....................................................</span>
            </td>
        </tr>
    </table>
</div>

</body>
</html>