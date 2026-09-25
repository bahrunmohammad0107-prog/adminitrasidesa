<?php
session_start();
include "../cek_login.php";
include "../koneksi.php";

$id_sppt = intval($_GET['id'] ?? 0);

// Ambil data SPPT berdasarkan ID
$q = mysqli_query($koneksi, "SELECT * FROM pajak_sppt WHERE id = '$id_sppt' LIMIT 1");
$data = mysqli_fetch_assoc($q);

if (!$data) {
    die("Data pembayaran SPPT tidak ditemukan.");
}

// Ambil profil desa
$kop = mysqli_query($koneksi, "SELECT * FROM profil_desa LIMIT 1");
$k = mysqli_fetch_assoc($kop);
$nama_desa = $k['nama_desa'] ?? 'DESA';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk Pembayaran PBB - <?= htmlspecialchars($data['nop']) ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 12px; color: #000; margin: 0; padding: 20px; background: #f8fafc; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; }
        
        .action-buttons { margin-bottom: 20px; display: flex; gap: 10px; }
        .btn { padding: 8px 16px; font-size: 13px; font-weight: bold; border: none; border-radius: 6px; cursor: pointer; color: #fff; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .btn-print { background-color: #0f172a; }
        .btn-download { background-color: #0d9488; }
        .btn-back { background-color: #64748b; }
        .btn:hover { opacity: 0.9; }

        .struk-container { width: 100%; max-width: 320px; background: #fff; border: 1px dashed #000; padding: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .header { text-align: center; margin-bottom: 10px; font-weight: bold; font-size: 13px; }
        .divider { border-bottom: 1px dashed #000; margin: 8px 0; }
        .info { margin: 5px 0; line-height: 1.4; }
        .total { font-weight: bold; font-size: 13px; margin: 10px 0; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 5px 0; text-align: right; }
        .footer { text-align: center; margin-top: 15px; font-size: 10px; }

        /* Sembunyikan tombol saat dicetak */
        @media print {
            body { background: #fff; padding: 0; display: block; }
            .action-buttons { display: none; }
            .struk-container { border: none; box-shadow: none; padding: 0; max-width: 100%; }
        }
    </style>
</head>
<body>

    <!-- Tombol Aksi (Tidak ikut tercetak) -->
    <div class="action-buttons">
        <button onclick="window.print()" class="btn btn-print">
            🖨️ Cetak Struk
        </button>
        <button onclick="downloadStruk()" class="btn btn-download">
            💾 Unduh (PDF/Print)
        </button>
        <a href="pembayaran.php" class="btn btn-back">
            ⬅️ Kembali
        </a>
    </div>

    <!-- Kotak Struk -->
    <div class="struk-container" id="strukArea">
        <div class="header">
            PEMERINTAH DESA <?= strtoupper($nama_desa) ?><br>
            BUKTI PEMBAYARAN PBB
        </div>
        <div class="divider"></div>
        <div class="info">
            Tgl Bayar : <?= !empty($data['tanggal_bayar']) ? date('d/m/Y H:i', strtotime($data['tanggal_bayar'])) : date('d/m/Y H:i') ?><br>
            NOP       : <?= htmlspecialchars($data['nop']) ?><br>
            Wajib Pajak: <?= htmlspecialchars($data['nama_wajib_pajak']) ?><br>
            Blok Objek: <?= htmlspecialchars($data['alamat_objek_pajak'] ?: ($data['blok_tanah'] ?? '-')) ?>
        </div>
        <div class="divider"></div>
        <div class="info">
            Nominal Tagihan : Rp <?= number_format($data['pajak_terhitung'], 0, ',', '.') ?><br>
            Status Pembayaran: <strong style="color: green;"><?= htmlspecialchars($data['status']) ?></strong>
        </div>
        <div class="total">
            TOTAL: Rp <?= number_format($data['pajak_terhitung'], 0, ',', '.') ?>
        </div>
        <div class="divider"></div>
        <div class="footer">
            Terima Kasih Atas Partisipasi Anda<br>
            Membangun Desa.<br>
            <i>Simpan struk ini sebagai bukti lunas yang sah.</i>
        </div>
    </div>

    <script>
        function downloadStruk() {
            // Membuka dialog print di mana pengguna bisa memilih "Save as PDF"
            alert("Untuk mengunduh, pada jendela printer yang muncul, silakan ubah tujuan (Destination) menjadi 'Save as PDF'.");
            window.print();
        }
    </script>
</body>
</html>