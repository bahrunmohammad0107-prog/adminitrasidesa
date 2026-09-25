<?php
session_start();
include "../cek_login.php";
include "../koneksi.php";

$desa_id  = intval($_SESSION['desa_id'] ?? 1);
$level    = strtolower(trim($_SESSION['level'] ?? 'admin'));
$id_user  = intval($_SESSION['id_user'] ?? $_SESSION['id'] ?? 0);
$dusun_id = intval($_SESSION['dusun_id'] ?? 0);

if ($level === 'kadus' && $dusun_id <= 0 && $id_user > 0) {
    $qu = mysqli_query($koneksi, "SELECT dusun_id FROM users WHERE id = '$id_user' LIMIT 1");
    if ($qu && $ru = mysqli_fetch_assoc($qu)) {
        $dusun_id = intval($ru['dusun_id']);
        $_SESSION['dusun_id'] = $dusun_id;
    }
}

$sppt_id = intval($_GET['id'] ?? 0);
if ($sppt_id <= 0) {
    die("<div style='padding:40px; font-family:sans-serif; text-align:center;'><h3>ID SPPT Tidak Valid!</h3><a href='data_sppt.php'>Kembali</a></div>");
}

// 1. FILTER AKSES DUSUN
$where_akses = "WHERE p.id = '$sppt_id' AND p.desa_id = '$desa_id'";
if ($level === 'kadus' && $dusun_id > 0) {
    $where_akses .= " AND p.dusun_id = '$dusun_id'";
}

$qsppt = mysqli_query($koneksi, "
    SELECT p.*, d.nama_dusun 
    FROM pajak_sppt p
    LEFT JOIN dusun d ON p.dusun_id = d.id
    $where_akses
    LIMIT 1
");

$sppt = mysqli_fetch_assoc($qsppt);
if (!$sppt) {
    die("<div style='padding:40px; font-family:sans-serif; text-align:center;'><h3>Akses Ditolak</h3><p>Data tidak ditemukan di wilayah dusun Anda.</p><a href='data_sppt.php'>Kembali</a></div>");
}

$nama_wp_awal   = strtoupper(trim($sppt['nama_wajib_pajak']));
$luas_asli_sppt = floatval($sppt['luas_tanah']);
$pesan = "";

// Cek otomatis kolom database
$chk_col = mysqli_query($koneksi, "SHOW COLUMNS FROM riwayat_tanah LIKE 'no_registrasi'");
if (mysqli_num_rows($chk_col) == 0) {
    mysqli_query($koneksi, "ALTER TABLE riwayat_tanah ADD COLUMN no_registrasi VARCHAR(100) NULL AFTER sppt_id");
}

// 2. SIMPAN / EDIT TRANSAKSI
if (isset($_POST['simpan_transaksi'])) {
    $mode           = trim($_POST['mode'] ?? 'tambah');
    $trx_id         = intval($_POST['trx_id'] ?? 0);
    $no_reg         = trim($_POST['no_registrasi'] ?? '');
    $nama_asal      = strtoupper(trim($_POST['nama_asal'] ?? ''));
    $nik_asal       = trim($_POST['nik_asal'] ?? '');
    $alamat_asal    = trim($_POST['alamat_asal'] ?? '');
    
    $nama_tujuan    = strtoupper(trim($_POST['nama_tujuan'] ?? ''));
    $nik_tujuan     = trim($_POST['nik_tujuan'] ?? '');
    $alamat_tujuan  = trim($_POST['alamat_tujuan'] ?? '');

    $luas_mutasi    = floatval(str_replace(array('.', ','), array('', '.'), $_POST['luas_mutasi'] ?? 0));
    $jenis_mutasi   = strtoupper(trim($_POST['jenis_mutasi'] ?? 'JUAL BELI'));
    $tanggal_mutasi = trim($_POST['tanggal_mutasi'] ?? date('Y-m-d'));
    $keterangan     = trim($_POST['keterangan'] ?? '');

    if ($nama_asal !== '' && $nama_tujuan !== '' && $luas_mutasi > 0) {
        $no_reg_s        = mysqli_real_escape_string($koneksi, $no_reg);
        $nama_asal_s     = mysqli_real_escape_string($koneksi, $nama_asal);
        $nik_asal_s      = mysqli_real_escape_string($koneksi, $nik_asal);
        $alamat_asal_s   = mysqli_real_escape_string($koneksi, $alamat_asal);
        $nama_tujuan_s   = mysqli_real_escape_string($koneksi, $nama_tujuan);
        $nik_tujuan_s    = mysqli_real_escape_string($koneksi, $nik_tujuan);
        $alamat_tujuan_s = mysqli_real_escape_string($koneksi, $alamat_tujuan);
        $keterangan_s    = mysqli_real_escape_string($koneksi, $keterangan);

        if ($mode === 'edit' && $trx_id > 0) {
            mysqli_query($koneksi, "UPDATE riwayat_tanah SET 
                no_registrasi = '$no_reg_s',
                dari_pemilik = '$nama_asal_s', 
                nik_asal = '$nik_asal_s',
                alamat_asal = '$alamat_asal_s',
                kepada_pemilik = '$nama_tujuan_s', 
                nik_tujuan = '$nik_tujuan_s',
                alamat_tujuan = '$alamat_tujuan_s',
                luas_mutasi = '$luas_mutasi', 
                jenis_mutasi = '$jenis_mutasi', 
                tanggal_mutasi = '$tanggal_mutasi', 
                keterangan = '$keterangan_s' 
                WHERE id = '$trx_id' AND sppt_id = '$sppt_id'");
            $pesan = "✅ Transaksi berhasil diperbarui!";
        } else {
            mysqli_query($koneksi, "INSERT INTO riwayat_tanah 
                (sppt_id, no_registrasi, dari_pemilik, nik_asal, alamat_asal, kepada_pemilik, nik_tujuan, alamat_tujuan, luas_mutasi, jenis_mutasi, tanggal_mutasi, keterangan) 
                VALUES 
                ('$sppt_id', '$no_reg_s', '$nama_asal_s', '$nik_asal_s', '$alamat_asal_s', '$nama_tujuan_s', '$nik_tujuan_s', '$alamat_tujuan_s', '$luas_mutasi', '$jenis_mutasi', '$tanggal_mutasi', '$keterangan_s')");
            $pesan = "✅ Transaksi $jenis_mutasi dari <b>$nama_asal</b> ke <b>$nama_tujuan</b> ($luas_mutasi m²) berhasil disimpan!";
        }
    }
}

// 3. HAPUS TRANSAKSI
if (isset($_GET['hapus_id'])) {
    $hapus_id = intval($_GET['hapus_id']);
    mysqli_query($koneksi, "DELETE FROM riwayat_tanah WHERE id = '$hapus_id' AND sppt_id = '$sppt_id'");
    header("Location: riwayat_tanah.php?id=" . $sppt_id . "&pesan=hapus_sukses");
    exit;
}

// 4. DATA LOG
$q_riwayat = mysqli_query($koneksi, "SELECT * FROM riwayat_tanah WHERE sppt_id = '$sppt_id' ORDER BY tanggal_mutasi ASC, id ASC");
$semua_transaksi = array();
$total_transaksi = mysqli_num_rows($q_riwayat);
$transaksi_terakhir = "-";

while ($rw = mysqli_fetch_assoc($q_riwayat)) {
    $semua_transaksi[] = $rw;
    $transaksi_terakhir = strtoupper($rw['jenis_mutasi']);
}

// 5. LEDGER SALDO REAL-TIME
$daftar_orang = array($nama_wp_awal => true);
$total_keluar = array();
$total_masuk  = array();

foreach ($semua_transaksi as $t) {
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

$daftar_semua_pemilik = array();
$daftar_pemilik_bisa_jual = array();

foreach ($daftar_orang as $nama_p => $val) {
    $masuk  = floatval($total_masuk[$nama_p] ?? 0);
    $keluar = floatval($total_keluar[$nama_p] ?? 0);
    $modal_awal = ($nama_p === $nama_wp_awal) ? $luas_asli_sppt : 0;
    $saldo_sisa = max(0, $modal_awal + $masuk - $keluar);

    $data_pemilik = array(
        'nama_pemilik' => $nama_p,
        'luas_dimiliki' => $saldo_sisa,
        'is_wp_asli' => ($nama_p === $nama_wp_awal)
    );

    $daftar_semua_pemilik[] = $data_pemilik;
    if ($saldo_sisa > 0) {
        $daftar_pemilik_bisa_jual[] = $data_pemilik;
    }
}
$total_pemilik_terlibat = count($daftar_semua_pemilik);

// 6. GENERATE MERMAID CODE
$mermaid_code = "graph LR\n";
$mermaid_code .= "classDef rootBox fill:#0f172a,stroke:#334155,stroke-width:2px,color:#ffffff,font-weight:700,rx:8,ry:8;\n";
$mermaid_code .= "classDef activeBox fill:#f0fdf4,stroke:#16a34a,stroke-width:2px,color:#14532d,font-weight:700,rx:8,ry:8;\n";
$mermaid_code .= "classDef zeroBox fill:#fffbeb,stroke:#f59e0b,stroke-width:2px,color:#92400e,font-weight:700,rx:8,ry:8;\n";

function clean_node_id($nama) {
    return "N_" . strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $nama));
}

$relasi_garis = array();
if (!empty($semua_transaksi)) {
    foreach ($semua_transaksi as $t) {
        $key = strtoupper(trim($t['dari_pemilik'])) . "___" . strtoupper(trim($t['kepada_pemilik']));
        if (!isset($relasi_garis[$key])) {
            $relasi_garis[$key] = array(
                'dari' => strtoupper(trim($t['dari_pemilik'])),
                'ke'   => strtoupper(trim($t['kepada_pemilik'])),
                'total_luas' => 0,
                'jenis_list' => array(),
                'tgl_terakhir' => $t['tanggal_mutasi']
            );
        }
        $relasi_garis[$key]['total_luas'] += floatval($t['luas_mutasi']);
        $relasi_garis[$key]['jenis_list'][strtoupper(trim($t['jenis_mutasi']))] = true;
        $relasi_garis[$key]['tgl_terakhir'] = $t['tanggal_mutasi'];
    }
}

foreach ($daftar_semua_pemilik as $p) {
    $nid = clean_node_id($p['nama_pemilik']);
    $cls = $p['is_wp_asli'] ? 'rootBox' : (($p['luas_dimiliki'] > 0) ? 'activeBox' : 'zeroBox');
    $mermaid_code .= $nid . "[\"👤 " . strtoupper(htmlspecialchars($p['nama_pemilik'])) . "\"]:::" . $cls . "\n";
}

foreach ($relasi_garis as $r) {
    $dari_id  = clean_node_id($r['dari']);
    $ke_id    = clean_node_id($r['ke']);
    $tgl_lbl  = date('d/m/Y', strtotime($r['tgl_terakhir']));
    $jns_lbl  = implode('/', array_keys($r['jenis_list']));
    $luas_lbl = number_format($r['total_luas'], 0, ',', '.') . " m²";

    $mermaid_code .= $dari_id . " -->|\"" . $tgl_lbl . "<br><b>" . $jns_lbl . "</b><br>" . $luas_lbl . "\"| " . $ke_id . "\n";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Riwayat Perjalanan Tanah - <?= htmlspecialchars($sppt['nop']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
    <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
    <style>
        body { background-color: #f8fafc; font-family: 'Plus Jakarta Sans', sans-serif; color: #0f172a; }
        .main-card { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.04); margin-bottom: 24px; }
        .stat-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px 20px; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03); }
        .stat-box .title { font-size: 11px; font-weight: 700; letter-spacing: 0.6px; color: #64748b; text-transform: uppercase; }
        .stat-box .value { font-size: 22px; font-weight: 800; color: #0f172a; margin-top: 4px; }
        
        .pemegang-card { background: #ffffff; border-radius: 14px; padding: 18px; border: 1.5px solid #cbd5e1; height: 100%; display: flex; flex-direction: column; justify-content: space-between; }
        .pemegang-card.is-active { border-color: #10b981; }
        .pemegang-card.is-zero { border-color: #f59e0b; background: #fffdfa; }

        .form-section-card { background: #ffffff; border: 1px solid #d1fae5; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.05); }
        .party-card-left { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; }
        .party-card-right { background: #f0fdf4; border: 1px solid #dcfce7; border-radius: 12px; padding: 20px; }
        
        .form-label-custom { font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 6px; display: flex; align-items: center; }
        .form-control-custom { font-size: 13px; border-radius: 9px; border: 1px solid #cbd5e1; padding: 9px 13px; font-weight: 500; background-color: #ffffff; }
        .form-control-custom:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.12); }

        .badge-party-first { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; font-size: 11px; font-weight: 700; padding: 5px 12px; border-radius: 20px; }
        .badge-party-second { background: #ecfdf5; color: #059669; border: 1px solid #d1fae5; font-size: 11px; font-weight: 700; padding: 5px 12px; border-radius: 20px; }

        .diagram-wrapper { background: #ffffff; border: 1.5px dashed #cbd5e1; border-radius: 14px; padding: 35px 20px; min-height: 240px; display: flex; justify-content: center; align-items: center; overflow-x: auto; }
        .table-custom th { background: #f1f5f9 !important; color: #334155 !important; font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 14px; border-bottom: 2px solid #e2e8f0; }
        .table-custom td { padding: 14px; font-size: 13px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }

        .btn-theme-edit   { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-weight: 600; font-size: 11.5px; border-radius: 6px; padding: 4px 10px; text-decoration: none; }
        .btn-theme-cetak  { background: #faf5ff; color: #7e22ce; border: 1px solid #e9d5ff; font-weight: 600; font-size: 11.5px; border-radius: 6px; padding: 4px 10px; text-decoration: none; }
        .btn-theme-hapus  { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; font-weight: 600; font-size: 11.5px; border-radius: 6px; padding: 4px 10px; text-decoration: none; }
    </style>
</head>
<body class="p-4">

<div class="container-fluid" style="max-width: 1320px;">
    
    <?php if ($pesan != ""): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4 fw-bold"><?= $pesan ?></div>
    <?php endif; ?>

    <!-- 1. STATISTIK UTAMA -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-box">
                <div class="title">TOTAL TRANSAKSI</div>
                <div class="value text-primary"><?= $total_transaksi ?></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-box">
                <div class="title">PEMILIK TERLIBAT</div>
                <div class="value" style="color: #6366f1;"><?= $total_pemilik_terlibat ?> Orang</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-box">
                <div class="title">LUAS SPPT INDUK</div>
                <div class="value text-success"><?= number_format($luas_asli_sppt, 2, ',', '.') ?> m²</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-box">
                <div class="title">TRANSAKSI TERAKHIR</div>
                <div class="value" style="font-size: 18px;"><?= $transaksi_terakhir ?></div>
            </div>
        </div>
    </div>

    <!-- 2. IDENTITAS & LOKASI OBJEK PAJAK -->
    <div class="main-card p-4 mb-4 border-start border-dark border-4">
        <h6 class="fw-bold mb-3 text-dark"><i class="fa fa-map-marked-alt me-2 text-primary"></i>Informasi & Lokasi Objek Pajak</h6>
        <div class="row g-3">
            <div class="col-md-3 col-sm-6">
                <small class="text-muted fw-bold d-block">NOMOR OBJEK PAJAK (NOP)</small>
                <span class="fw-bold text-primary font-monospace fs-6"><?= htmlspecialchars($sppt['nop']) ?></span>
            </div>
            <div class="col-md-3 col-sm-6">
                <small class="text-muted fw-bold d-block">NAMA WAJIB PAJAK ASLI</small>
                <span class="fw-bold text-dark fs-6">👤 <?= strtoupper(htmlspecialchars($nama_wp_awal)) ?></span>
            </div>
            <div class="col-md-3 col-sm-6">
                <small class="text-muted fw-bold d-block">DUSUN / BLOK</small>
                <span class="fw-bold text-dark"><?= htmlspecialchars($sppt['nama_dusun'] ?: '-') ?> / <?= htmlspecialchars($sppt['blok_tanah'] ?: '-') ?></span>
            </div>
            <div class="col-md-3 col-sm-6">
                <small class="text-muted fw-bold d-block">LUAS AWAL & TAGIHAN</small>
                <span class="fw-bold text-success"><?= number_format($luas_asli_sppt, 0, ',', '.') ?> m²</span>
                <span class="text-muted small">(Rp <?= number_format($sppt['pajak_terhitung'], 0, ',', '.') ?>)</span>
            </div>
            <div class="col-md-6">
                <small class="text-muted fw-bold d-block">ALAMAT WAJIB PAJAK:</small>
                <div class="p-2 bg-light rounded-3 text-dark small border mt-1">
                    <?= htmlspecialchars($sppt['alamat_wajib_pajak'] ?: '-') ?>
                </div>
            </div>
            <div class="col-md-6">
                <small class="text-muted fw-bold d-block">ALAMAT OBJEK PAJAK (LETAK TANAH):</small>
                <div class="p-2 bg-light rounded-3 text-dark small border mt-1">
                    <?= htmlspecialchars($sppt['alamat_objek_pajak'] ?: '-') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. STATUS PEMEGANG HAK (GRID HORIZONTAL) -->
    <div class="main-card p-4">
        <h6 class="fw-bold mb-3 text-dark"><i class="fa fa-users-rectangle me-2 text-primary"></i>Status Pemegang Hak Tanah Saat Ini</h6>
        
        <div class="row g-3">
            <?php foreach ($daftar_semua_pemilik as $sp): 
                $saldo = floatval($sp['luas_dimiliki']);
                $nama_p = $sp['nama_pemilik'];
            ?>
            <div class="col-lg-4 col-md-6 col-12">
                <div class="pemegang-card <?= ($saldo > 0) ? 'is-active' : 'is-zero' ?>">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge <?= ($saldo > 0) ? 'bg-success' : 'bg-warning text-dark' ?>" style="font-size: 11px;">
                                <?= ($saldo > 0) ? 'PEMILIK AKTIF' : '0 m² (HABIS DIALIHKAN)' ?>
                            </span>
                            <?php if ($sp['is_wp_asli']): ?>
                                <span class="badge bg-dark" style="font-size: 10px;">WP ASLI</span>
                            <?php endif; ?>
                        </div>

                        <h5 class="fw-bold text-dark mb-1 text-truncate">
                            <i class="fa fa-user me-2 text-secondary"></i><?= strtoupper(htmlspecialchars($nama_p)) ?>
                        </h5>

                        <div class="fw-bold <?= ($saldo > 0) ? 'text-primary' : 'text-danger' ?> fs-6 mb-3">
                            Sisa Tanah: <?= number_format($saldo, 2, ',', '.') ?> m²
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-auto">
                        <button type="button" class="btn btn-success btn-sm w-100 fw-bold rounded-pill" onclick="isiFormTransaksi('<?= addslashes($nama_p) ?>', <?= $saldo ?>)" <?= ($saldo <= 0) ? 'disabled' : '' ?>>
                            + Transaksi Milik <?= strtoupper(htmlspecialchars($nama_p)) ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 4. FORM TRANSAKSI DENGAN NOMOR REGISTRASI SURAT MANUAL -->
    <div class="form-section-card p-4 mb-4" id="kotakTransaksiAtas">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold text-dark mb-1"><i class="fa fa-file-signature text-success me-2"></i>Formulir Mutasi & Peralihan Hak Tanah</h5>
                <small class="text-muted">Nomor surat registrasi mutasi dapat diisi sesuai format buku agenda register desa Anda.</small>
            </div>
        </div>
        
        <form method="POST" class="row g-3">
            <input type="hidden" name="mode" value="tambah">
            <input type="hidden" name="trx_id" value="0">

            <!-- NOMOR REGISTRASI MUTASI MANUAL -->
            <div class="col-12">
                <div class="p-3 bg-light rounded-3 border">
                    <label class="form-label-custom"><i class="fa fa-hashtag text-primary me-1"></i> Nomor Registrasi Surat Mutasi (Bisa Diisi Manual Sesuai Format Desa):</label>
                    <input type="text" name="no_registrasi" class="form-control form-control-custom fw-bold font-monospace" placeholder="Contoh: 590/012/MUTASI/<?= date('Y') ?> (Bisa dikosongkan jika pakai nomor otomatis)">
                </div>
            </div>

            <!-- PIHAK PERTAMA -->
            <div class="col-lg-6 col-12">
                <div class="party-card-left h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge-party-first"><i class="fa fa-user-minus me-1"></i> Pihak Pertama (Pemberi / Penjual)</span>
                        <small class="text-muted fw-bold">Sumber Tanah</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label-custom"><i class="fa fa-user text-secondary me-1"></i> Dari Pemilik (Pemegang Hak):</label>
                        <select name="nama_asal" id="form_nama_asal" class="form-select form-control-custom fw-bold" onchange="sesuaikanMaksLuas()" required>
                            <?php if (empty($daftar_pemilik_bisa_jual)): ?>
                                <option value="">-- Saldo Tanah Habis Semua --</option>
                            <?php else: ?>
                                <?php foreach ($daftar_pemilik_bisa_jual as $dp): ?>
                                    <option value="<?= htmlspecialchars($dp['nama_pemilik']) ?>" data-saldo="<?= floatval($dp['luas_dimiliki']) ?>">
                                        👤 <?= strtoupper(htmlspecialchars($dp['nama_pemilik'])) ?> (Tersedia: <?= number_format($dp['luas_dimiliki'], 0, ',', '.') ?> m²)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom"><i class="fa fa-id-card text-secondary me-1"></i> Nomor NIK KTP Pihak Pertama:</label>
                        <input type="text" name="nik_asal" class="form-control form-control-custom font-monospace" placeholder="Contoh: 330507xxxxxxxxxx" maxlength="20">
                    </div>

                    <div class="mb-0">
                        <label class="form-label-custom"><i class="fa fa-map-marker-alt text-secondary me-1"></i> Alamat Sesuai KTP Pihak Pertama:</label>
                        <input type="text" name="alamat_asal" class="form-control form-control-custom" placeholder="Alamat lengkap sesuai KTP">
                    </div>
                </div>
            </div>

            <!-- PIHAK KEDUA -->
            <div class="col-lg-6 col-12">
                <div class="party-card-right h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge-party-second"><i class="fa fa-user-plus me-1"></i> Pihak Kedua (Penerima / Pembeli)</span>
                        <small class="text-muted fw-bold">Penerima Baru</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom"><i class="fa fa-user text-success me-1"></i> Nama Lengkap Penerima:</label>
                        <input type="text" name="nama_tujuan" class="form-control form-control-custom fw-bold" placeholder="Contoh: BAHRUN / DONI / SARIF" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom"><i class="fa fa-id-card text-success me-1"></i> Nomor NIK KTP Penerima:</label>
                        <input type="text" name="nik_tujuan" class="form-control form-control-custom font-monospace" placeholder="Contoh: 330507xxxxxxxxxx" maxlength="20">
                    </div>

                    <div class="mb-0">
                        <label class="form-label-custom"><i class="fa fa-map-marker-alt text-success me-1"></i> Alamat Sesuai KTP Penerima:</label>
                        <input type="text" name="alamat_tujuan" class="form-control form-control-custom" placeholder="Alamat lengkap sesuai KTP">
                    </div>
                </div>
            </div>

            <!-- RINCIAN MUTASI BAWAH -->
            <div class="col-md-3 col-6">
                <label class="form-label-custom"><i class="fa fa-vector-square text-primary me-1"></i> Luas Dialihkan (m²):</label>
                <input type="number" step="any" name="luas_mutasi" id="form_luas_mutasi" class="form-control form-control-custom fw-bold" placeholder="m²" required>
            </div>

            <div class="col-md-3 col-6">
                <label class="form-label-custom"><i class="fa fa-tags text-primary me-1"></i> Jenis Transaksi:</label>
                <select name="jenis_mutasi" class="form-select form-control-custom fw-bold">
                    <option value="JUAL BELI">Jual Beli</option>
                    <option value="HIBAH">Hibah</option>
                    <option value="WARIS">Waris</option>
                    <option value="UBAH NAMA / MUTASI">Ubah Nama / Mutasi</option>
                </select>
            </div>

            <div class="col-md-3 col-6">
                <label class="form-label-custom"><i class="fa fa-calendar-alt text-primary me-1"></i> Tanggal Transaksi:</label>
                <input type="date" name="tanggal_mutasi" class="form-control form-control-custom" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="col-md-3 col-6">
                <label class="form-label-custom"><i class="fa fa-file-lines text-primary me-1"></i> Keterangan / Dasar Surat:</label>
                <input type="text" name="keterangan" class="form-control form-control-custom" placeholder="Contoh: Akta Hibah / Tanpa Akta">
            </div>

            <div class="col-12 text-end pt-2">
                <button type="submit" name="simpan_transaksi" class="btn btn-success rounded-pill px-5 fw-bold py-2 shadow-sm" <?= empty($daftar_pemilik_bisa_jual) ? 'disabled' : '' ?>>
                    <i class="fa fa-save me-2"></i> Simpan Transaksi Lengkap
                </button>
            </div>
        </form>
    </div>

    <!-- 5. GRAFIK ALUR 1 GARIS -->
    <div class="main-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h6 class="fw-bold mb-0 text-dark"><i class="fa fa-diagram-project me-2 text-primary"></i>Grafik Alur & Silsilah Perjalanan Tanah</h6>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-dark fw-bold rounded-pill px-3" onclick="cetakGrafik()">
                    <i class="fa fa-print me-1"></i> Cetak Gambar
                </button>
                <button type="button" class="btn btn-sm btn-primary fw-bold rounded-pill px-3" onclick="unduhGrafikPNG()">
                    <i class="fa fa-download me-1"></i> Unduh (PNG)
                </button>
            </div>
        </div>

        <div class="diagram-wrapper" id="areaGrafik">
            <pre class="mermaid" style="background: transparent;">
<?= $mermaid_code ?>
            </pre>
        </div>
    </div>

    <!-- 6. TABEL DAFTAR TRANSAKSI -->
    <div class="main-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0 text-dark">📜 Catatan Riwayat Perjalanan Tanah</h6>
            <span class="badge bg-dark text-white px-3 py-2 rounded-pill fw-bold"><?= $total_transaksi ?> Transaksi</span>
        </div>

        <div class="table-responsive rounded-3 border">
            <table class="table table-custom table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 40px;">NO</th>
                        <th>NO. REGISTRASI / TANGGAL</th>
                        <th>DARI PEMILIK (NIK/KTP)</th>
                        <th>KEPADA PEMILIK (NIK/KTP)</th>
                        <th>LUAS</th>
                        <th>JENIS</th>
                        <th>KETERANGAN</th>
                        <th class="text-center" style="width: 180px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    if ($total_transaksi == 0):
                    ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted fw-bold">Belum ada riwayat transaksi.</td></tr>
                    <?php 
                    else:
                        foreach ($semua_transaksi as $r): 
                    ?>
                    <tr>
                        <td class="text-center text-muted fw-bold"><?= $no++ ?></td>
                        <td>
                            <div class="fw-bold text-dark font-monospace"><?= htmlspecialchars($r['no_registrasi'] ?: sprintf("%04d/MUTASI/%s", $r['id'], date('Y', strtotime($r['tanggal_mutasi'])))) ?></div>
                            <small class="text-muted"><?= date('d-m-Y', strtotime($r['tanggal_mutasi'])) ?></small>
                        </td>
                        <td>
                            <div class="fw-bold text-danger">👤 <?= strtoupper(htmlspecialchars($r['dari_pemilik'])) ?></div>
                            <?php if (!empty($r['nik_asal'])): ?>
                                <small class="text-muted font-monospace">NIK: <?= htmlspecialchars($r['nik_asal']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-bold text-success">👤 <?= strtoupper(htmlspecialchars($r['kepada_pemilik'])) ?></div>
                            <?php if (!empty($r['nik_tujuan'])): ?>
                                <small class="text-muted font-monospace">NIK: <?= htmlspecialchars($r['nik_tujuan']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="fw-bold text-primary"><?= number_format($r['luas_mutasi'], 2, ',', '.') ?> m²</td>
                        <td><span class="badge bg-light text-dark border"><?= strtoupper($r['jenis_mutasi']) ?></span></td>
                        <td><small class="text-muted"><?= htmlspecialchars($r['keterangan'] ?: '-') ?></small></td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <button type="button" class="btn-theme-edit" onclick='bukaModalEdit(<?= json_encode($r) ?>)'>Edit</button>
                                <a href="cetak_riwayat.php?id=<?= $r['id'] ?>" target="_blank" class="btn-theme-cetak">Cetak</a>
                                <a href="riwayat_tanah.php?id=<?= $sppt_id ?>&hapus_id=<?= $r['id'] ?>" class="btn-theme-hapus" onclick="return confirm('Hapus transaksi ini?')">Hapus</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-5">
        <a href="data_sppt.php" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">
            ← Kembali ke Data SPPT
        </a>
        <a href="index.php" class="btn btn-dark rounded-pill px-4 fw-bold">
            <i class="fa fa-home me-1"></i> Dashboard Pajak
        </a>
    </div>

</div>

<!-- MODAL EDIT LENGKAP -->
<div class="modal fade" id="modalEditTrx" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form method="POST">
                <input type="hidden" name="mode" value="edit">
                <input type="hidden" name="trx_id" id="edit_trx_id">

                <div class="modal-header bg-dark text-white rounded-top-4">
                    <h5 class="modal-title fw-bold">Edit Transaksi Tanah</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 row g-3">
                    <div class="col-12">
                        <label class="form-label-custom">Nomor Registrasi Surat Mutasi:</label>
                        <input type="text" name="no_registrasi" id="edit_no_registrasi" class="form-control form-control-custom font-monospace fw-bold">
                    </div>

                    <div class="col-md-6 border-end">
                        <label class="form-label-custom">Dari Pemilik:</label>
                        <input type="text" name="nama_asal" id="edit_nama_asal" class="form-control form-control-custom fw-bold bg-light mb-2" readonly>
                        
                        <label class="form-label-custom">NIK KTP Pihak Pertama:</label>
                        <input type="text" name="nik_asal" id="edit_nik_asal" class="form-control form-control-custom font-monospace mb-2">

                        <label class="form-label-custom">Alamat KTP Pihak Pertama:</label>
                        <input type="text" name="alamat_asal" id="edit_alamat_asal" class="form-control form-control-custom">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label-custom">Kepada Pemilik:</label>
                        <input type="text" name="nama_tujuan" id="edit_nama_tujuan" class="form-control form-control-custom fw-bold mb-2" required>

                        <label class="form-label-custom">NIK KTP Pihak Kedua:</label>
                        <input type="text" name="nik_tujuan" id="edit_nik_tujuan" class="form-control form-control-custom font-monospace mb-2">

                        <label class="form-label-custom">Alamat KTP Pihak Kedua:</label>
                        <input type="text" name="alamat_tujuan" id="edit_alamat_tujuan" class="form-control form-control-custom">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-custom">Luas (m²):</label>
                        <input type="number" step="any" name="luas_mutasi" id="edit_luas_mutasi" class="form-control form-control-custom fw-bold" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-custom">Jenis:</label>
                        <select name="jenis_mutasi" id="edit_jenis_mutasi" class="form-select form-control-custom">
                            <option value="JUAL BELI">Jual Beli</option>
                            <option value="HIBAH">Hibah</option>
                            <option value="WARIS">Waris</option>
                            <option value="UBAH NAMA / MUTASI">Ubah Nama / Mutasi</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-custom">Tanggal:</label>
                        <input type="date" name="tanggal_mutasi" id="edit_tanggal_mutasi" class="form-control form-control-custom" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label-custom">Keterangan:</label>
                        <input type="text" name="keterangan" id="edit_keterangan" class="form-control form-control-custom">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="simpan_transaksi" class="btn btn-dark rounded-pill px-4 fw-bold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
mermaid.initialize({ startOnLoad: true, theme: 'neutral', flowchart: { curve: 'monotoneX', rankSpacing: 60, nodeSpacing: 35 } });

function sesuaikanMaksLuas() {
    let sel = document.getElementById('form_nama_asal');
    if (sel && sel.selectedIndex >= 0) {
        let opt = sel.options[sel.selectedIndex];
        let saldo = opt.getAttribute('data-saldo');
        let inp = document.getElementById('form_luas_mutasi');
        if (inp && saldo) {
            inp.max = saldo;
            inp.placeholder = "Maks: " + Number(saldo).toLocaleString('id-ID') + " m²";
        }
    }
}

function isiFormTransaksi(namaPemilik, saldo) {
    let sel = document.getElementById('form_nama_asal');
    if (sel) {
        sel.value = namaPemilik;
        sesuaikanMaksLuas();
    }
    document.getElementById('kotakTransaksiAtas').scrollIntoView({ behavior: 'smooth' });
}

function bukaModalEdit(data) {
    document.getElementById('edit_trx_id').value = data.id;
    document.getElementById('edit_no_registrasi').value = data.no_registrasi || '';
    document.getElementById('edit_nama_asal').value = data.dari_pemilik;
    document.getElementById('edit_nik_asal').value = data.nik_asal || '';
    document.getElementById('edit_alamat_asal').value = data.alamat_asal || '';

    document.getElementById('edit_nama_tujuan').value = data.kepada_pemilik;
    document.getElementById('edit_nik_tujuan').value = data.nik_tujuan || '';
    document.getElementById('edit_alamat_tujuan').value = data.alamat_tujuan || '';

    document.getElementById('edit_luas_mutasi').value = data.luas_mutasi;
    document.getElementById('edit_jenis_mutasi').value = data.jenis_mutasi.toUpperCase();
    document.getElementById('edit_tanggal_mutasi').value = data.tanggal_mutasi;
    document.getElementById('edit_keterangan').value = data.keterangan || '';

    new bootstrap.Modal(document.getElementById('modalEditTrx')).show();
}

function unduhGrafikPNG() {
    html2canvas(document.getElementById('areaGrafik')).then(canvas => {
        let link = document.createElement('a');
        link.download = 'Grafik_Alur_Tanah_NOP_<?= $sppt['nop'] ?>.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    });
}

function cetakGrafik() {
    let win = window.open('', '_blank');
    win.document.write('<html><head><title>Cetak Grafik Alur Tanah</title></head><body style="text-align:center; padding:30px;">' + document.getElementById('areaGrafik').innerHTML + '</body></html>');
    win.document.close();
    setTimeout(() => { win.print(); }, 500);
}

document.addEventListener("DOMContentLoaded", sesuaikanMaksLuas);
</script>

</body>
</html>