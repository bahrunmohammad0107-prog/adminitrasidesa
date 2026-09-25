<?php
session_start();
include "../cek_login.php";
include "../koneksi.php";

$desa_id  = intval($_SESSION['desa_id'] ?? 1);
$level    = strtolower(trim($_SESSION['level'] ?? 'admin'));
$id_user  = intval($_SESSION['id_user'] ?? $_SESSION['id'] ?? 0);
$dusun_id = intval($_SESSION['dusun_id'] ?? 0);

// Ambil dusun_id jika user adalah kadus
if ($level === 'kadus' && $dusun_id <= 0 && $id_user > 0) {
    $qu = mysqli_query($koneksi, "SELECT dusun_id FROM users WHERE id = '$id_user' LIMIT 1");
    if ($qu && $ru = mysqli_fetch_assoc($qu)) {
        $dusun_id = intval($ru['dusun_id']);
        $_SESSION['dusun_id'] = $dusun_id;
    }
}

$pesan = "";

// PROSES EDIT DATA SPPT DASAR
if (isset($_POST['simpan_edit'])) {
    $e_id         = intval($_POST['sppt_id']);
    $e_nop        = mysqli_real_escape_string($koneksi, trim($_POST['nop']));
    $e_nama_wp    = mysqli_real_escape_string($koneksi, strtoupper(trim($_POST['nama_wajib_pajak'])));
    $e_pemegang   = mysqli_real_escape_string($koneksi, strtoupper(trim($_POST['pemegang_sppt'])));
    $e_alamat_wp  = mysqli_real_escape_string($koneksi, trim($_POST['alamat_wajib_pajak']));
    $e_alamat_op  = mysqli_real_escape_string($koneksi, trim($_POST['alamat_objek_pajak']));
    $e_blok       = mysqli_real_escape_string($koneksi, trim($_POST['blok_tanah']));
    $e_luas_tanah = floatval(str_replace(array('.', ','), array('', '.'), $_POST['luas_tanah']));
    $e_pajak      = floatval(str_replace(array('.', ','), array('', '.'), $_POST['pajak_terhitung']));

    $whr_edit = "WHERE id = '$e_id' AND desa_id = '$desa_id'";
    if ($level === 'kadus' && $dusun_id > 0) {
        $whr_edit .= " AND dusun_id = '$dusun_id'";
    }

    $q_edit = mysqli_query($koneksi, "UPDATE pajak_sppt SET 
        nop = '$e_nop',
        nama_wajib_pajak = '$e_nama_wp',
        pemegang_sppt = '$e_pemegang',
        alamat_wajib_pajak = '$e_alamat_wp',
        alamat_objek_pajak = '$e_alamat_op',
        blok_tanah = '$e_blok',
        luas_tanah = '$e_luas_tanah',
        pajak_terhitung = '$e_pajak'
        $whr_edit
    ");

    if ($q_edit) {
        $pesan = "✅ Data SPPT <b>$e_nama_wp</b> berhasil diperbarui!";
    }
}

// QUERY DATA SPPT (KADUS vs ADMIN)
$where = "WHERE pajak_sppt.desa_id = '$desa_id'";
if ($level === 'kadus' && $dusun_id > 0) {
    $where .= " AND pajak_sppt.dusun_id = '$dusun_id'";
}

$cari = trim($_GET['cari'] ?? '');
if ($cari !== '') {
    $cari_safe = mysqli_real_escape_string($koneksi, $cari);
    $where .= " AND (
        pajak_sppt.nop LIKE '%$cari_safe%' OR 
        pajak_sppt.nama_wajib_pajak LIKE '%$cari_safe%' OR 
        pajak_sppt.pemegang_sppt LIKE '%$cari_safe%' OR 
        pajak_sppt.alamat_wajib_pajak LIKE '%$cari_safe%' OR 
        pajak_sppt.alamat_objek_pajak LIKE '%$cari_safe%' OR 
        pajak_sppt.blok_tanah LIKE '%$cari_safe%'
    )";
}

$q_count = mysqli_query($koneksi, "SELECT COUNT(*) AS total, SUM(pajak_terhitung) as total_pajak FROM pajak_sppt $where");
$r_count = mysqli_fetch_assoc($q_count);
$total_data  = intval($r_count['total'] ?? 0);
$total_pajak = floatval($r_count['total_pajak'] ?? 0);

$q_data = mysqli_query($koneksi, "
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data SPPT PBB Desa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; font-family: 'Segoe UI', sans-serif; color: #1e293b; }
        .main-card { border-radius: 18px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,.06); background: white; margin-bottom: 24px; }
        .header-box { background: linear-gradient(135deg, #1e3a8a, #2563eb); color: white; border-radius: 18px 18px 0 0; padding: 22px 28px; }
        
        .table-custom-wrapper { max-height: 68vh; overflow-y: auto; }
        .table-custom { width: 100%; border-collapse: collapse; font-size: 12px; white-space: nowrap; }
        .table-custom thead th { position: sticky; top: 0; background: #0f3b7d !important; color: white !important; font-weight: 700; padding: 12px 14px; z-index: 5; font-size: 11px; text-transform: uppercase; }
        .table-custom tbody td { padding: 10px 12px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }

        .btn-act-edit      { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; border-radius: 6px; padding: 4px 10px; font-size: 11px; font-weight: 700; text-decoration: none; }
        .btn-act-tinjau    { background: #f8fafc; color: #475569; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 10px; font-size: 11px; font-weight: 700; text-decoration: none; }
        .btn-act-transaksi { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; border-radius: 6px; padding: 4px 12px; font-size: 11.5px; font-weight: 800; text-decoration: none; }
    </style>
</head>
<body class="p-4">

<div class="container-fluid">
    <div class="main-card">
        <div class="header-box d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h3 class="fw-bold mb-1"><i class="fa fa-database me-2"></i>DATA MASTER SPPT PBB DESA</h3>
                <small class="opacity-75">
                    Login: <b class="badge bg-white text-primary text-uppercase px-2 py-1"><?= $level ?></b>
                    <?php if ($level === 'kadus'): ?>
                        &bull; Wilayah: <b>DUSUN <?= $dusun_id ?></b>
                    <?php else: ?>
                        &bull; Wilayah: <b>SEMUA DUSUN (Kompilasi Seluruh Kadus)</b>
                    <?php endif; ?>
                </small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="import_sppt.php" class="btn btn-success rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa fa-file-excel me-1"></i> Unggah Excel SPPT
                </a>
                <span class="badge bg-light text-primary fs-6 px-3 py-2 rounded-pill fw-bold">
                    TOTAL: <?= number_format($total_data, 0, ',', '.') ?> SPPT
                </span>
            </div>
        </div>

        <div class="p-4">
            <?php if ($pesan != ""): ?>
                <div class="alert alert-info border-0 shadow-sm rounded-4 mb-4"><?= $pesan ?></div>
            <?php endif; ?>

            <form method="GET" class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="input-group" style="max-width: 480px;">
                    <input type="text" name="cari" class="form-control rounded-start-pill ps-4" placeholder="Cari NOP / Nama WP / Dusun..." value="<?= htmlspecialchars($cari) ?>">
                    <button type="submit" class="btn btn-primary rounded-end-pill px-4 fw-bold"><i class="fa fa-search me-1"></i> CARI</button>
                    <?php if ($cari !== ''): ?>
                        <a href="data_sppt.php" class="btn btn-outline-secondary rounded-pill ms-2"><i class="fa fa-times"></i></a>
                    <?php endif; ?>
                </div>

                <div class="text-end">
                    <small class="text-muted fw-bold d-block">TOTAL TARGET PAJAK:</small>
                    <span class="fs-5 fw-bold text-success">Rp <?= number_format($total_pajak, 0, ',', '.') ?></span>
                </div>
            </form>

            <div class="table-custom-wrapper border rounded-3">
                <table class="table-custom table-hover">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 40px;">NO</th>
                            <th>NOP</th>
                            <th>NAMA WAJIB PAJAK</th>
                            <th>PEMEGANG SPPT</th>
                            <th>DUSUN</th>
                            <th>BLOK</th>
                            <th>LUAS BUMI</th>
                            <th>PAJAK (RP)</th>
                            <th class="text-center" style="width: 250px;">AKSI PERALIHAN & TRANSAKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if ($total_data == 0): 
                        ?>
                        <tr><td colspan="9" class="text-center py-5 text-muted fw-bold">Belum ada data SPPT yang tercatat.</td></tr>
                        <?php 
                        else: 
                            while ($r = mysqli_fetch_assoc($q_data)): 
                        ?>
                        <tr>
                            <td class="text-center text-muted fw-bold"><?= $no++ ?></td>
                            <td class="fw-bold text-primary font-monospace"><?= htmlspecialchars($r['nop']) ?></td>
                            <td class="fw-bold"><?= strtoupper(htmlspecialchars($r['nama_wajib_pajak'])) ?></td>
                            <td>
                                <?php if (!empty($r['pemegang_sppt'])): ?>
                                    <span class="badge bg-primary">👤 <?= strtoupper(htmlspecialchars($r['pemegang_sppt'])) ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">- (WP Asli)</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($r['nama_dusun'] ?: '-') ?></span></td>
                            <td><?= htmlspecialchars($r['blok_tanah'] ?: '-') ?></td>
                            <td><?= number_format($r['luas_tanah'], 0, ',', '.') ?> m²</td>
                            <td class="fw-bold">Rp <?= number_format($r['pajak_terhitung'], 0, ',', '.') ?></td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <!-- 1. EDIT -->
                                    <button type="button" class="btn-act-edit" onclick='bukaModalEdit(<?= json_encode($r) ?>)' title="Edit Data">
                                        <i class="fa fa-pen"></i> Edit
                                    </button>

                                    <!-- 2. TINJAU -->
                                    <button type="button" class="btn-act-tinjau" onclick='bukaModalTinjau(<?= json_encode($r) ?>)' title="Tinjau Detail">
                                        <i class="fa fa-eye"></i> Tinjau
                                    </button>

                                    <!-- 3. TRANSAKSI POKOK (JUAL BELI / HIBAH / WARIS / SILSILAH) -->
                                    <a href="riwayat_tanah.php?id=<?= $r['id'] ?>" class="btn-act-transaksi" title="Buka Transaksi Jual Beli / Hibah / Waris / Alur Silsilah Tanah">
                                        <i class="fa fa-handshake me-1"></i> Transaksi
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div>
        <a href="index.php" class="btn btn-dark rounded-pill px-4 fw-bold shadow-sm">
            <i class="fa fa-arrow-left me-1"></i> Dashboard Pajak
        </a>
    </div>
</div>

<!-- MODAL EDIT DATA SPPT -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form method="POST">
                <input type="hidden" name="sppt_id" id="edit_id">
                <div class="modal-header bg-primary text-white rounded-top-4">
                    <h5 class="modal-title fw-bold"><i class="fa fa-pen-to-square me-2"></i>Edit Data Dasar SPPT</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">NOMOR OBJEK PAJAK (NOP):</label>
                        <input type="text" name="nop" id="edit_nop" class="form-control rounded-3 font-monospace" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">NAMA WAJIB PAJAK:</label>
                        <input type="text" name="nama_wajib_pajak" id="edit_nama_wp" class="form-control rounded-3 fw-bold" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">PEMEGANG / PENANGGUNG JAWAB:</label>
                        <input type="text" name="pemegang_sppt" id="edit_pemegang" class="form-control rounded-3">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">BLOK TANAH:</label>
                        <input type="text" name="blok_tanah" id="edit_blok" class="form-control rounded-3">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">LUAS TANAH (M²):</label>
                        <input type="number" step="any" name="luas_tanah" id="edit_luas" class="form-control rounded-3" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">PAJAK TERHITUNG (RP):</label>
                        <input type="number" step="any" name="pajak_terhitung" id="edit_pajak" class="form-control rounded-3 fw-bold" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">ALAMAT WAJIB PAJAK:</label>
                        <textarea name="alamat_wajib_pajak" id="edit_alamat_wp" class="form-control rounded-3" rows="2"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">ALAMAT OBJEK PAJAK:</label>
                        <textarea name="alamat_objek_pajak" id="edit_alamat_op" class="form-control rounded-3" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="simpan_edit" class="btn btn-primary rounded-pill px-4 fw-bold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL TINJAU DETAIL -->
<div class="modal fade" id="modalTinjau" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-dark text-white rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fa fa-file-lines me-2"></i>Rincian Lengkap SPPT</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted" style="width: 140px;">NOP</td><td class="fw-bold font-monospace" id="t_nop">-</td></tr>
                    <tr><td class="text-muted">Nama WP Asli</td><td class="fw-bold" id="t_nama_wp">-</td></tr>
                    <tr><td class="text-muted">Pemegang SPPT</td><td class="fw-bold text-primary" id="t_pemegang">-</td></tr>
                    <tr><td class="text-muted">Dusun / Blok</td><td class="fw-bold" id="t_dusun_blok">-</td></tr>
                    <tr><td class="text-muted">Alamat WP</td><td id="t_alamat_wp">-</td></tr>
                    <tr><td class="text-muted">Alamat Objek</td><td id="t_alamat_op">-</td></tr>
                    <tr><td class="text-muted">Luas Tanah</td><td class="fw-bold text-success" id="t_luas">-</td></tr>
                    <tr><td class="text-muted">Tagihan Pajak</td><td class="fw-bold fs-5 text-primary" id="t_pajak">-</td></tr>
                </table>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function bukaModalEdit(r) {
    document.getElementById('edit_id').value = r.id;
    document.getElementById('edit_nop').value = r.nop;
    document.getElementById('edit_nama_wp').value = r.nama_wajib_pajak;
    document.getElementById('edit_pemegang').value = r.pemegang_sppt || '';
    document.getElementById('edit_blok').value = r.blok_tanah || '';
    document.getElementById('edit_luas').value = r.luas_tanah;
    document.getElementById('edit_pajak').value = r.pajak_terhitung;
    document.getElementById('edit_alamat_wp').value = r.alamat_wajib_pajak || '';
    document.getElementById('edit_alamat_op').value = r.alamat_objek_pajak || '';
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

function bukaModalTinjau(r) {
    document.getElementById('t_nop').innerText = r.nop;
    document.getElementById('t_nama_wp').innerText = r.nama_wajib_pajak;
    document.getElementById('t_pemegang').innerText = r.pemegang_sppt ? r.pemegang_sppt : '(WP Asli)';
    document.getElementById('t_dusun_blok').innerText = (r.nama_dusun || '-') + ' / ' + (r.blok_tanah || '-');
    document.getElementById('t_alamat_wp').innerText = r.alamat_wajib_pajak || '-';
    document.getElementById('t_alamat_op').innerText = r.alamat_objek_pajak || '-';
    document.getElementById('t_luas').innerText = Number(r.luas_tanah).toLocaleString('id-ID') + ' m²';
    document.getElementById('t_pajak').innerText = 'Rp ' + Number(r.pajak_terhitung).toLocaleString('id-ID');
    new bootstrap.Modal(document.getElementById('modalTinjau')).show();
}
</script>

</body>
</html>