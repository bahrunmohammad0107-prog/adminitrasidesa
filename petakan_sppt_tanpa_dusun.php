<?php
session_start();
include "../cek_login.php";
include "../koneksi.php";

$desa_id  = intval($_SESSION['desa_id'] ?? 1);
$level    = strtolower(trim($_SESSION['level'] ?? 'admin'));
$dusun_id = intval($_SESSION['dusun_id'] ?? 0);

// Cek apakah sudah login
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    die("Silakan login terlebih dahulu.");
}

function e($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

$pesan = '';
$error = '';

// =========================================================
// 1. PROSES UPDATE DUSUN MASSAL ATAU PER BARIS
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Aksi 1: Update Terpilih (Centang Massal)
    if (isset($_POST['aksi']) && $_POST['aksi'] === 'massal') {
        $pilih_id = $_POST['sppt_id'] ?? [];
        $dusun_tujuan = intval($_POST['dusun_tujuan'] ?? 0);

        // Jika login kadus, paksa ke dusun miliknya
        if ($level === 'kadus' && $dusun_id > 0) {
            $dusun_tujuan = $dusun_id;
        }

        if ($dusun_tujuan > 0 && !empty($pilih_id)) {
            $ids = array_map('intval', $pilih_id);
            $id_list = implode(',', $ids);
            $update = mysqli_query($koneksi, "UPDATE pajak_sppt SET dusun_id = $dusun_tujuan WHERE id IN ($id_list) AND desa_id = $desa_id");
            if ($update) {
                $pesan = count($ids) . " SPPT berhasil dipetakan ke dusun tujuan.";
            } else {
                $error = "Gagal memperbarui: " . mysqli_error($koneksi);
            }
        } else {
            $error = "Pilih minimal satu data SPPT dan tentukan Dusun tujuan.";
        }
    }

    // Aksi 2: Auto-Map Berdasarkan Kata Kunci (Misal: RT tertentu atau Blok)
    if (isset($_POST['aksi']) && $_POST['aksi'] === 'filter_kata') {
        $kata_kunci   = trim($_POST['kata_kunci'] ?? '');
        $dusun_tujuan = intval($_POST['dusun_tujuan_kata'] ?? 0);

        if ($level === 'kadus' && $dusun_id > 0) {
            $dusun_tujuan = $dusun_id;
        }

        if ($kata_kunci !== '' && $dusun_tujuan > 0) {
            $kw_safe = mysqli_real_escape_string($koneksi, $kata_kunci);
            $query_auto = "
                UPDATE pajak_sppt 
                SET dusun_id = $dusun_tujuan 
                WHERE desa_id = $desa_id 
                  AND (dusun_id IS NULL OR dusun_id = 0)
                  AND (
                      alamat_objek_pajak LIKE '%$kw_safe%' 
                      OR alamat_wajib_pajak LIKE '%$kw_safe%' 
                      OR blok_tanah LIKE '%$kw_safe%'
                  )
            ";
            if (mysqli_query($koneksi, $query_auto)) {
                $jml_terkena = mysqli_affected_rows($koneksi);
                $pesan = "$jml_terkena SPPT yang mengandung kata '$kata_kunci' berhasil dipetakan.";
            } else {
                $error = "Gagal memproses kata kunci: " . mysqli_error($koneksi);
            }
        }
    }

    // Aksi 3: Update Simpan Cepat per Baris
    if (isset($_POST['aksi']) && $_POST['aksi'] === 'single_update') {
        $target_id    = intval($_POST['target_id'] ?? 0);
        $target_dusun = intval($_POST['target_dusun'] ?? 0);

        if ($level === 'kadus' && $dusun_id > 0) {
            $target_dusun = $dusun_id;
        }

        if ($target_id > 0 && $target_dusun > 0) {
            mysqli_query($koneksi, "UPDATE pajak_sppt SET dusun_id = $target_dusun WHERE id = $target_id AND desa_id = $desa_id");
            $pesan = "1 SPPT berhasil dipetakan.";
        }
    }
}

// =========================================================
// 2. AMBIL DAFTAR DUSUN
// =========================================================
$q_dusun = mysqli_query($koneksi, "SELECT id, nama_dusun FROM dusun WHERE desa_id = $desa_id ORDER BY nama_dusun ASC");
$daftar_dusun = [];
while ($d = mysqli_fetch_assoc($q_dusun)) {
    $daftar_dusun[] = $d;
}

// =========================================================
// 3. AMBIL DAFTAR SPPT YANG BELUM PUNYA DUSUN
// =========================================================
$q_sisa = mysqli_query(
    $koneksi, 
    "SELECT id, nop, nama_wajib_pajak, alamat_wajib_pajak, alamat_objek_pajak, blok_tanah, luas_tanah, pajak_terhitung 
     FROM pajak_sppt 
     WHERE desa_id = $desa_id AND (dusun_id IS NULL OR dusun_id = 0)
     ORDER BY id ASC"
);

$sppt_belum_terpetakan = [];
if ($q_sisa) {
    while ($row = mysqli_fetch_assoc($q_sisa)) {
        $sppt_belum_terpetakan[] = $row;
    }
}

$total_belum = count($sppt_belum_terpetakan);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pemetaan SPPT Tanpa Dusun</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 25px; font-family: 'Segoe UI', Arial, sans-serif; background: #f1f5f9; color: #0f172a; }
        .container { max-width: 1400px; margin: auto; }
        
        .header {
            background: linear-gradient(135deg, #1e293b, #334155);
            color: white;
            padding: 25px 30px;
            border-radius: 18px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .header h1 { margin: 0; font-size: 24px; font-weight: 900; }
        .header p { margin: 5px 0 0; font-size: 13px; opacity: 0.85; }
        .badge-sisa {
            background: #ef4444;
            color: white;
            padding: 10px 18px;
            border-radius: 50px;
            font-weight: 800;
            font-size: 14px;
        }

        .alert-success { background: #dcfce7; border-left: 5px solid #16a34a; color: #166534; padding: 14px 18px; border-radius: 12px; margin-bottom: 18px; font-weight: 700; font-size: 14px; }
        .alert-danger { background: #fee2e2; border-left: 5px solid #dc2626; color: #991b1b; padding: 14px 18px; border-radius: 12px; margin-bottom: 18px; font-weight: 700; font-size: 14px; }

        .tools-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-bottom: 20px;
        }
        .tool-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        }
        .tool-title { font-size: 15px; font-weight: 800; color: #1e3a8a; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        
        .form-row { display: flex; gap: 10px; flex-wrap: wrap; }
        input, select {
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-family: inherit;
            font-size: 13px;
            flex: 1;
            min-width: 150px;
        }
        button {
            padding: 10px 18px;
            border-radius: 10px;
            border: none;
            font-weight: 800;
            font-size: 13px;
            cursor: pointer;
            transition: .2s;
        }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-success { background: #16a34a; color: white; }
        .btn-success:hover { background: #15803d; }
        .btn-dark { background: #0f172a; color: white; text-decoration: none; padding: 10px 18px; border-radius: 10px; font-weight: 800; font-size: 13px; display: inline-block; }

        .table-box {
            background: white;
            border-radius: 18px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        }
        table { width: 100%; border-collapse: collapse; min-width: 1000px; }
        th { background: #f8fafc; color: #475569; padding: 12px 14px; text-align: left; font-size: 12px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; font-size: 13px; vertical-align: middle; }
        tbody tr:hover { background: #f8fafc; }

        .nop-badge { color: #2563eb; font-weight: 800; font-family: monospace; font-size: 13px; }
        .name-bold { font-weight: 800; color: #0f172a; }
        .addr-sub { font-size: 11.5px; color: #64748b; margin-top: 2px; }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #166534;
        }
        .empty-state h3 { font-size: 20px; margin-bottom: 6px; }

        @media (max-width: 900px) {
            .tools-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="container">

    <!-- HEADER -->
    <div class="header">
        <div>
            <h1>📍 Pemetaan SPPT Belum Memiliki Dusun</h1>
            <p>Login Sebagai: <b><?= strtoupper($level) ?></b> | Tentukan dusun untuk data SPPT yang belum terpetakan.</p>
        </div>
        <div>
            <span class="badge-sisa">
                <?= $total_belum ?> SPPT Belum Dipetakan
            </span>
        </div>
    </div>

    <!-- NOTIFIKASI -->
    <?php if ($pesan !== ''): ?>
        <div class="alert-success">✓ <?= e($pesan) ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alert-danger">⚠️ <?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($total_belum > 0): ?>
    <!-- TOOLBAR ALAT PEMETAAN CEPAT -->
    <div class="tools-grid">
        
        <!-- ALAT 1: AUTO MAP BERDASARKAN KATA KUNCI (RT/RW/BLOK) -->
        <div class="tool-card">
            <div class="tool-title">⚡ 1. Auto-Map Massal via Kata Kunci (RT / Blok)</div>
            <form method="POST">
                <input type="hidden" name="aksi" value="filter_kata">
                <div class="form-row">
                    <input type="text" name="kata_kunci" placeholder="Ketik misal: RT 001, BEBEKAN, RENGGO" required>
                    <select name="dusun_tujuan_kata" required>
                        <option value="">-- Pilih Dusun Tujuan --</option>
                        <?php foreach ($daftar_dusun as $ds): ?>
                            <option value="<?= $ds['id'] ?>" <?= ($dusun_id == $ds['id']) ? 'selected' : '' ?>>
                                Dusun <?= e($ds['nama_dusun']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-primary">Terapkan</button>
                </div>
                <small style="color:#64748b; display:block; margin-top:6px;">Semua data yang alamat/blok-nya mengandung kata tersebut langsung masuk ke dusun yang dipilih.</small>
            </form>
        </div>

        <!-- ALAT 2: AKSI CENTANG MASSAL -->
        <div class="tool-card">
            <div class="tool-title">☑️ 2. Petakan SPPT yang Dicentang di Tabel</div>
            <div class="form-row">
                <select id="selectDusunMassal" required>
                    <option value="">-- Pilih Dusun Tujuan --</option>
                    <?php foreach ($daftar_dusun as $ds): ?>
                        <option value="<?= $ds['id'] ?>" <?= ($dusun_id == $ds['id']) ? 'selected' : '' ?>>
                            Dusun <?= e($ds['nama_dusun']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" onclick="submitCentangMassal()" class="btn-success">Simpan Centangan</button>
            </div>
            <small style="color:#64748b; display:block; margin-top:6px;">Centang beberapa data di tabel bawah, lalu klik tombol ini untuk memindahkannya.</small>
        </div>

    </div>
    <?php endif; ?>

    <!-- TABEL DAFTAR SPPT -->
    <div class="table-box">
        <?php if ($total_belum === 0): ?>
            <div class="empty-state">
                <div style="font-size:45px; margin-bottom:10px;">🎉</div>
                <h3>Luar Biasa! Semua SPPT Sudah Terpetakan 100%</h3>
                <p style="color:#475569; font-size:13px;">Tidak ada lagi data SPPT tanpa dusun. Setiap Kadus sekarang sudah dapat melihat data wilayahnya masing-masing.</p>
                <br>
                <a href="data_sppt.php" class="btn-dark">➡️ Kembali ke Data SPPT</a>
            </div>
        <?php else: ?>
            <form id="formMassal" method="POST">
                <input type="hidden" name="aksi" value="massal">
                <input type="hidden" name="dusun_tujuan" id="inputDusunTujuan">

                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align:center;">
                                    <input type="checkbox" id="checkAll" onclick="toggleSemua(this)">
                                </th>
                                <th style="width: 50px;">No</th>
                                <th>NOP & Wajib Pajak</th>
                                <th>Alamat Objek / Letak Tanah</th>
                                <th>Alamat WP</th>
                                <th>Blok</th>
                                <th style="width: 250px;">Pilih Dusun Langsung</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sppt_belum_terpetakan as $idx => $row): ?>
                            <tr>
                                <td style="text-align:center;">
                                    <input type="checkbox" name="sppt_id[]" value="<?= $row['id'] ?>" class="check-item">
                                </td>
                                <td><?= $idx + 1 ?></td>
                                <td>
                                    <span class="nop-badge"><?= e($row['nop']) ?></span>
                                    <div class="name-bold"><?= e(strtoupper($row['nama_wajib_pajak'])) ?></div>
                                    <div class="addr-sub">Luas: <?= number_format($row['luas_tanah'], 0, ',', '.') ?> m² | Pajak: Rp <?= number_format($row['pajak_terhitung'], 0, ',', '.') ?></div>
                                </td>
                                <td><?= e($row['alamat_objek_pajak'] ?: '-') ?></td>
                                <td><?= e($row['alamat_wajib_pajak'] ?: '-') ?></td>
                                <td><b><?= e($row['blok_tanah'] ?: '-') ?></b></td>
                                <td>
                                    <!-- FORM SIMPAN LANGSUNG PER BARIS -->
                                    <select onchange="updateSingle(<?= $row['id'] ?>, this.value)" style="width:100%;">
                                        <option value="">-- Tetapkan Dusun --</option>
                                        <?php foreach ($daftar_dusun as $ds): ?>
                                            <option value="<?= $ds['id'] ?>" <?= ($dusun_id == $ds['id']) ? 'selected' : '' ?>>
                                                Dusun <?= e($ds['nama_dusun']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>

            <div style="margin-top:20px; display:flex; justify-content:space-between; align-items:center;">
                <a href="data_sppt.php" class="btn-dark">⬅ Kembali ke Data SPPT</a>
                <a href="sinkron_dusun.php" class="btn-primary" style="text-decoration:none;">🔄 Jalankan Ulang Sinkronisasi</a>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- FORM SINGLE UPDATE HIDDEN -->
<form id="formSingle" method="POST" style="display:none;">
    <input type="hidden" name="aksi" value="single_update">
    <input type="hidden" name="target_id" id="singleTargetId">
    <input type="hidden" name="target_dusun" id="singleTargetDusun">
</form>

<script>
function toggleSemua(master) {
    const items = document.querySelectorAll('.check-item');
    items.forEach(el => el.checked = master.checked);
}

function submitCentangMassal() {
    const selectedDusun = document.getElementById('selectDusunMassal').value;
    if (!selectedDusun) {
        alert("Silakan pilih dusun tujuan pada kotak Alat 2 terlebih dahulu.");
        return;
    }
    
    const checkedItems = document.querySelectorAll('.check-item:checked');
    if (checkedItems.length === 0) {
        alert("Pilih minimal satu data SPPT dengan mencentang kotak pada tabel.");
        return;
    }

    document.getElementById('inputDusunTujuan').value = selectedDusun;
    document.getElementById('formMassal').submit();
}

function updateSingle(spptId, dusunId) {
    if (!dusunId) return;
    document.getElementById('singleTargetId').value = spptId;
    document.getElementById('singleTargetDusun').value = dusunId;
    document.getElementById('formSingle').submit();
}
</script>

</body>
</html>