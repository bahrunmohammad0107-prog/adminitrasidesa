<?php
session_start();
include "../cek_login.php";
include "../koneksi.php";

$desa_id  = intval($_SESSION['desa_id'] ?? 1);
$level    = strtolower(trim($_SESSION['level'] ?? 'kadus'));
$dusun_id = intval($_SESSION['dusun_id'] ?? 0);

$cari          = trim($_GET['cari'] ?? '');
$filter_status = trim($_GET['status'] ?? 'semua');

$where = "WHERE pajak_sppt.desa_id = '$desa_id'";
if ($level === 'kadus' && $dusun_id > 0) {
    $where .= " AND pajak_sppt.dusun_id = '$dusun_id'";
}

if ($filter_status === 'lunas') {
    $where .= " AND pajak_sppt.status = 'SUDAH BAYAR'";
    $judul_status = "DAFTAR SPPT SUDAH BAYAR (LUNAS)";
} elseif ($filter_status === 'belum') {
    $where .= " AND (pajak_sppt.status = 'BELUM BAYAR' OR pajak_sppt.status IS NULL OR pajak_sppt.status = '')";
    $judul_status = "DAFTAR TUNGGAKAN SPPT (BELUM BAYAR)";
} else {
    $judul_status = "DAFTAR SELURUH PEMBAYARAN SPPT PBB";
}

if ($cari !== '') {
    $cari_safe = mysqli_real_escape_string($koneksi, $cari);
    $where .= " AND (pajak_sppt.nop LIKE '%$cari_safe%' OR pajak_sppt.nama_wajib_pajak LIKE '%$cari_safe%' OR pajak_sppt.pemegang_sppt LIKE '%$cari_safe%' OR pajak_sppt.blok_tanah LIKE '%$cari_safe%')";
}

$data = mysqli_query($koneksi, "
    SELECT pajak_sppt.*, dusun.nama_dusun 
    FROM pajak_sppt 
    LEFT JOIN dusun ON pajak_sppt.dusun_id = dusun.id 
    $where 
    ORDER BY pajak_sppt.no_urut ASC, pajak_sppt.id ASC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan Pembayaran SPPT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* SETTING KERTAS LANDSCAPE & HEMAT */
        @page {
            size: A4 landscape;
            margin: 6mm 6mm 6mm 6mm;
        }

        body { 
            background: white; 
            font-family: Arial, Helvetica, sans-serif; 
            color: #000; 
            font-size: 9pt;
            line-height: 1.15;
            padding: 5px;
        }

        .kop-surat {
            border-bottom: 1.5px solid #000;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }

        .table-cetak {
            width: 100%;
            border-collapse: collapse;
        }

        .table-cetak th {
            background-color: #e2e8f0 !important;
            color: #000 !important;
            border: 1px solid #000 !important;
            padding: 3px 4px;
            text-align: center;
            vertical-align: middle;
            font-weight: bold;
            font-size: 8.5pt;
            text-transform: uppercase;
        }

        .table-cetak td {
            border: 1px solid #333 !important;
            padding: 2.5px 4px;
            vertical-align: middle;
            font-size: 8.5pt;
        }

        .t-center { text-align: center; }
        .t-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0 !important; margin: 0 !important; }
            table { page-break-inside: auto; }
            tr    { page-break-inside: avoid; page-break-after: auto; }
            thead { display: table-header-group; }
        }
    </style>
</head>
<body>

<div class="container-fluid p-0">
    <!-- Tombol Aksi (Tidak Tercetak) -->
    <div class="d-flex justify-content-between align-items-center mb-2 no-print p-2 bg-light border rounded">
        <span class="fw-bold text-primary">Mode Hemat Kertas Aktif (Total hanya muncul di akhir data)</span>
        <div>
            <button onclick="window.print()" class="btn btn-sm btn-success fw-bold px-3 rounded-pill">
                🖨️ Cetak Dokumen
            </button>
            <button onclick="window.close()" class="btn btn-sm btn-secondary fw-bold px-3 rounded-pill ms-1">
                ✖ Tutup
            </button>
        </div>
    </div>

    <!-- KOP LAPORAN -->
    <div class="kop-surat text-center">
        <h6 class="fw-bold mb-0" style="font-size: 11pt;"><?= $judul_status ?></h6>
        <div class="fw-bold" style="font-size: 10pt;">PEMERINTAH DESA AMBALKLIWONAN - KEC. AMBAL, KAB. KEBUMEN</div>
        <small style="font-size: 7.5pt; color: #555;">Dicetak pada: <?= date('d/m/Y H:i') ?></small>
    </div>

    <!-- TABEL DATA -->
    <table class="table-cetak">
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th style="width: 125px;">NOP</th>
                <th>Nama Wajib Pajak (SPPT)</th>
                <th>Penanggung Jawab (Pemegang)</th>
                <th style="width: 65px;">Dusun</th>
                <th>Blok / Lokasi Objek</th>
                <th style="width: 85px;">Tagihan (Rp)</th>
                <th style="width: 55px;">Status</th>
                <th style="width: 75px;">Tgl Bayar</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $total_pajak = 0;
            if (mysqli_num_rows($data) == 0):
            ?>
            <tr>
                <td colspan="9" class="t-center py-2 fw-bold">Tidak ada data untuk laporan ini.</td>
            </tr>
            <?php 
            else:
                while ($r = mysqli_fetch_assoc($data)): 
                    $total_pajak += floatval($r['pajak_terhitung']);
            ?>
            <tr>
                <td class="t-center"><?= $no++ ?></td>
                <td class="t-center font-mono" style="font-size: 8pt;"><?= htmlspecialchars($r['nop']) ?></td>
                <td class="fw-bold"><?= strtoupper(htmlspecialchars($r['nama_wajib_pajak'])) ?></td>
                <td>
                    <?php if (!empty($r['pemegang_sppt'])): ?>
                        <b><?= strtoupper(htmlspecialchars($r['pemegang_sppt'])) ?></b>
                        <?php if (!empty($r['keterangan_pemegang'])): ?>
                            <span style="font-size: 7.5pt; color: #444;">(<?= htmlspecialchars($r['keterangan_pemegang']) ?>)</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color:#888;">-</span>
                    <?php endif; ?>
                </td>
                <td class="t-center"><?= htmlspecialchars($r['nama_dusun'] ?: '-') ?></td>
                <td><?= htmlspecialchars($r['blok_tanah'] ?: $r['alamat_objek_pajak']) ?></td>
                <td class="t-right fw-bold"><?= number_format($r['pajak_terhitung'], 0, ',', '.') ?></td>
                <td class="t-center fw-bold" style="font-size: 8pt;">
                    <?= ($r['status'] === 'SUDAH BAYAR') ? 'LUNAS' : 'BELUM' ?>
                </td>
                <td class="t-center" style="font-size: 7.5pt;"><?= !empty($r['tanggal_bayar']) ? date('d/m/y', strtotime($r['tanggal_bayar'])) : '-' ?></td>
            </tr>
            <?php 
                endwhile; 
            ?>
            <!-- TOTAL HANYA MUNCUL DI BARIS TERAKHIR DATA -->
            <tr style="font-weight: bold; background: #e2e8f0;">
                <td colspan="6" class="t-right" style="border: 1px solid #000 !important;">TOTAL TAGIHAN:</td>
                <td class="t-right" style="border: 1px solid #000 !important;">Rp <?= number_format($total_pajak, 0, ',', '.') ?></td>
                <td colspan="2" style="border: 1px solid #000 !important;"></td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- TANDA TANGAN -->
    <div class="row mt-2" style="page-break-inside: avoid; font-size: 8.5pt;">
        <div class="col-8"></div>
        <div class="col-4 text-center">
            <p class="mb-4">Ambalkliwonan, <?= date('d/m/Y') ?><br>Petugas Pemungut / Kadus,</p>
            <p class="fw-bold text-decoration-underline mb-0"><?= strtoupper($_SESSION['nama'] ?? 'Petugas') ?></p>
        </div>
    </div>
</div>

</body>
</html>