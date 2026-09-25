<?php

session_start();

include "../cek_login.php";
include "../koneksi.php";

$desa_id    = intval($_SESSION['desa_id'] ?? 1);
$id         = intval($_GET['id'] ?? 0);
$error      = trim($_GET['error'] ?? '');
$dari_pilih = trim($_GET['dari'] ?? '');

if ($id <= 0) {
    die("ID SPPT tidak ditemukan.");
}

function e($text)
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

function luas($nilai)
{
    return number_format(floatval($nilai), 2, ',', '.');
}

// 1. Ambil Data SPPT
$stmt = mysqli_prepare($koneksi, "SELECT * FROM pajak_sppt WHERE id = ? AND desa_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "ii", $id, $desa_id);
mysqli_stmt_execute($stmt);
$sppt = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$sppt) {
    die("Data SPPT tidak ditemukan.");
}

$nop        = $sppt['nop'] ?? '';
$nama_sppt  = $sppt['nama_wajib_pajak'] ?? $sppt['nama_wp'] ?? '';
$luas_sppt  = floatval($sppt['luas_tanah'] ?? 0);

// 2. Ambil Seluruh Pemilik Aktif yang Masih Punya Sisa Tanah
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

$daftar_penjual = [];
$saldo_terpilih = 0;
$nik_terpilih   = '';
$alamat_terpilih = '';

while ($row = mysqli_fetch_assoc($result)) {
    $daftar_penjual[] = $row;
    if (strcasecmp(trim($row['nama_pemilik']), trim($dari_pilih)) === 0) {
        $saldo_terpilih  = floatval($row['luas_dimiliki']);
        $nik_terpilih    = $row['nik'];
        $alamat_terpilih = $row['alamat'];
    }
}
mysqli_stmt_close($stmt);

if (empty($daftar_penjual)) {
    $dari_pilih     = $nama_sppt;
    $saldo_terpilih = $luas_sppt;
} elseif ($saldo_terpilih <= 0 && !empty($daftar_penjual)) {
    $dari_pilih      = $daftar_penjual[0]['nama_pemilik'];
    $saldo_terpilih  = floatval($daftar_penjual[0]['luas_dimiliki']);
    $nik_terpilih    = $daftar_penjual[0]['nik'];
    $alamat_terpilih = $daftar_penjual[0]['alamat'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Tanah Milik <?= e(strtoupper($dari_pilih)) ?> - <?= e($nop) ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Segoe UI", Arial, sans-serif; background: #eef3f7; color: #0f172a; }
        .container { width: 95%; max-width: 850px; margin: 25px auto 60px; }

        .header { background: linear-gradient(135deg, #047857, #059669); color: white; border-radius: 16px; padding: 22px 28px; margin-bottom: 20px; box-shadow: 0 10px 25px rgba(0,0,0,.08); }
        .header h1 { margin: 0; font-size: 24px; font-weight: 900; }
        .header p { margin: 5px 0 0; font-size: 13px; opacity: .9; }

        .info-pemilik-box {
            background: #ffffff;
            border: 2px solid #86efac;
            padding: 20px 24px;
            border-radius: 16px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(16,185,129,0.1);
        }
        .pemilik-label { font-size: 12px; color: #166534; font-weight: 800; text-transform: uppercase; }
        .pemilik-val { font-size: 24px; font-weight: 900; color: #065f46; margin-top: 4px; }
        .luas-val { font-size: 24px; font-weight: 900; color: #2563eb; margin-top: 4px; }

        .card { background: white; border: 1px solid #dfe7ef; border-radius: 16px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,.04); overflow: hidden; }
        .card-header { padding: 15px 22px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: 15px; font-weight: 800; color: #1e293b; }
        .card-body { padding: 22px; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
        .form-full { grid-column: span 2; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        label { font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; }

        input, select, textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5,150,105,0.15);
        }

        .alert-danger {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-left: 6px solid #dc2626;
            color: #991b1b;
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 13px;
        }

        .action-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 12px;
            margin-top: 15px;
        }

        .btn-submit {
            background: linear-gradient(135deg, #059669, #10b981);
            color: white;
            border: none;
            padding: 15px 24px;
            border-radius: 12px;
            font-weight: 900;
            font-size: 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(5, 150, 105, 0.25);
            transition: all 0.2s ease;
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, #047857, #059669);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(5, 150, 105, 0.35);
        }

        .btn-batal-keren {
            background: #ffffff;
            color: #475569;
            border: 1.5px solid #cbd5e1;
            padding: 15px 20px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 14px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: all 0.2s ease;
        }
        .btn-batal-keren:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
            color: #0f172a;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
        }

        @media(max-width: 600px) {
            .action-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="container">

    <div class="header">
        <h1>➕ Transaksi Pengalihan Tanah</h1>
        <p>NOP: <b><?= e($nop) ?></b></p>
    </div>

    <?php if ($error !== ''): ?>
    <div class="alert-danger">
        ⚠️ <b>Gagal Menyimpan:</b> <?= e($error) ?>
    </div>
    <?php endif; ?>

    <div class="info-pemilik-box">
        <div>
            <div class="pemilik-label">PEMILIK TANAH</div>
            <div class="pemilik-val">👤 <?= e(strtoupper($dari_pilih)) ?></div>
        </div>
        <div style="text-align:right;">
            <div class="pemilik-label">LUAS PEMILIK</div>
            <div class="luas-val"><?= luas($saldo_terpilih) ?> m²</div>
        </div>
    </div>

    <form action="proses_tambah_riwayat.php" method="POST">
        <input type="hidden" name="sppt_id" value="<?= $id ?>">
        <input type="hidden" name="dari_pemilik" value="<?= e($dari_pilih) ?>">
        <input type="hidden" name="nik_asal" value="<?= e($nik_terpilih) ?>">
        <input type="hidden" name="alamat_asal" value="<?= e($alamat_terpilih) ?>">

        <!-- 1. DETAIL TRANSAKSI -->
        <div class="card">
            <div class="card-header">📋 1. Detail Jenis & Tanggal Transaksi</div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Jenis Peralihan</label>
                        <select name="jenis_mutasi" required>
                            <option value="JUAL BELI">JUAL BELI</option>
                            <option value="HIBAH">HIBAH</option>
                            <option value="WARIS">WARIS</option>
                            <option value="PEMBAGIAN">PEMBAGIAN HAK</option>
                            <option value="PELEPASAN HAK">PELEPASAN HAK</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Transaksi</label>
                        <input type="date" name="tanggal_mutasi" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group form-full">
                        <label>Luas Tanah yang Dialihkan (m²)</label>
                        <input type="number" step="0.01" min="0.01" max="<?= $saldo_terpilih ?>" name="luas_mutasi" placeholder="Maksimal <?= luas($saldo_terpilih) ?> m²" required>
                        <small style="color:#059669; font-weight:700; margin-top:4px;">* Maksimal luas yang dapat dialihkan: <b><?= luas($saldo_terpilih) ?> m²</b></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. PEMILIK BARU (PENERIMA) -->
        <div class="card">
            <div class="card-header">👥 2. Pemilik Baru (Pihak Penerima)</div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group form-full">
                        <label>Nama Pemilik Baru / Pembeli</label>
                        <input type="text" name="kepada_pemilik" placeholder="Contoh: DANI, INA, dll." required style="text-transform:uppercase;">
                    </div>
                    <div class="form-group">
                        <label>NIK Pemilik Baru</label>
                        <input type="text" name="nik_baru" placeholder="NIK (opsional)">
                    </div>
                    <div class="form-group">
                        <label>Alamat Pemilik Baru</label>
                        <input type="text" name="alamat_baru" placeholder="Alamat domisili">
                    </div>
                    <div class="form-group form-full">
                        <label>Keterangan Tambahan</label>
                        <textarea name="keterangan" rows="2" placeholder="Catatan transaksi (opsional)"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- TOMBOL AKSI SIMPAN DAN KEMBALI -->
        <div class="action-container">
            <button type="submit" class="btn-submit">
                <span>💾</span> Simpan Transaksi Milik <?= e(strtoupper($dari_pilih)) ?>
            </button>
            <a href="riwayat_tanah.php?id=<?= $id ?>" class="btn-batal-keren">
                <span>↩️</span> Kembali
            </a>
        </div>
    </form>

</div>

</body>
</html>