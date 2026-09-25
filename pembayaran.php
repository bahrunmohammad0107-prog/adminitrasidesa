<?php
session_start();
include "../cek_login.php";
include "../koneksi.php";

$desa_id  = intval($_SESSION['desa_id'] ?? 1);
$level    = strtolower(trim($_SESSION['level'] ?? 'kadus'));
$dusun_id = intval($_SESSION['dusun_id'] ?? 0);
$id_user  = intval($_SESSION['id_user'] ?? $_SESSION['id'] ?? 0);

$pesan = "";

// 1. PROSES BAYAR / BATAL SATUAN DARI AKSI CEPAT
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id_sppt = intval($_GET['id']);
    $aksi    = $_GET['aksi'];

    if ($aksi === 'bayar') {
        $q_up = mysqli_query($koneksi, "UPDATE pajak_sppt SET status = 'SUDAH BAYAR', tanggal_bayar = NOW() WHERE id = '$id_sppt' AND desa_id = '$desa_id'");
        if ($q_up) $pesan = "✅ SPPT berhasil ditandai LUNAS.";
    } elseif ($aksi === 'batal') {
        $q_up = mysqli_query($koneksi, "UPDATE pajak_sppt SET status = 'BELUM BAYAR', tanggal_bayar = NULL WHERE id = '$id_sppt' AND desa_id = '$desa_id'");
        if ($q_up) $pesan = "ℹ️ Status pembayaran berhasil dibatalkan.";
    }
}

// 2. PROSES UPDATE DARI MODAL EDIT DATA & STATUS PEMBAYARAN
if (isset($_POST['simpan_edit'])) {
    $id_sppt        = intval($_POST['edit_id']);
    $nama_wp        = mysqli_real_escape_string($koneksi, strtoupper(trim($_POST['edit_nama_wp'] ?? '')));
    $pemegang       = mysqli_real_escape_string($koneksi, strtoupper(trim($_POST['edit_pemegang'] ?? '')));
    $keterangan     = mysqli_real_escape_string($koneksi, trim($_POST['edit_keterangan'] ?? ''));
    $blok           = mysqli_real_escape_string($koneksi, trim($_POST['edit_blok'] ?? ''));
    $pajak          = floatval(str_replace(['Rp', '.', ' '], '', $_POST['edit_pajak'] ?? 0));
    $status_bayar   = trim($_POST['edit_status'] ?? 'BELUM BAYAR');

    if ($status_bayar === 'SUDAH BAYAR') {
        $sql_status = "status = 'SUDAH BAYAR', tanggal_bayar = IF(tanggal_bayar IS NULL, NOW(), tanggal_bayar)";
    } else {
        $sql_status = "status = 'BELUM BAYAR', tanggal_bayar = NULL";
    }

    $q_edit = mysqli_query($koneksi, "UPDATE pajak_sppt SET 
        nama_wajib_pajak    = '$nama_wp',
        pemegang_sppt       = '$pemegang',
        keterangan_pemegang = '$keterangan',
        blok_tanah          = '$blok',
        pajak_terhitung     = '$pajak',
        $sql_status
        WHERE id = '$id_sppt' AND desa_id = '$desa_id'");

    if ($q_edit) {
        $pesan = "✅ Data SPPT & status pembayaran berhasil diperbarui!";
    }
}

// 3. FILTER DAN PENCARIAN
$cari          = trim($_GET['cari'] ?? '');
$filter_status = trim($_GET['status'] ?? 'semua');

$where = "WHERE pajak_sppt.desa_id = '$desa_id'";
if ($level === 'kadus' && $dusun_id > 0) {
    $where .= " AND pajak_sppt.dusun_id = '$dusun_id'";
}

if ($filter_status === 'lunas') {
    $where .= " AND pajak_sppt.status = 'SUDAH BAYAR'";
} elseif ($filter_status === 'belum') {
    $where .= " AND (pajak_sppt.status = 'BELUM BAYAR' OR pajak_sppt.status IS NULL OR pajak_sppt.status = '')";
}

if ($cari !== '') {
    $cari_safe = mysqli_real_escape_string($koneksi, $cari);
    $where .= " AND (pajak_sppt.nop LIKE '%$cari_safe%' OR pajak_sppt.nama_wajib_pajak LIKE '%$cari_safe%' OR pajak_sppt.pemegang_sppt LIKE '%$cari_safe%' OR pajak_sppt.blok_tanah LIKE '%$cari_safe%')";
}

// RINGKASAN
$q_ringkasan = mysqli_query($koneksi, "
    SELECT 
        COUNT(id) AS total_lembar,
        SUM(pajak_terhitung) AS total_nominal,
        SUM(CASE WHEN status = 'SUDAH BAYAR' THEN pajak_terhitung ELSE 0 END) AS nominal_lunas,
        SUM(CASE WHEN status = 'BELUM BAYAR' OR status IS NULL OR status = '' THEN pajak_terhitung ELSE 0 END) AS nominal_belum
    FROM pajak_sppt
    $where
");
$r_ringkasan = mysqli_fetch_assoc($q_ringkasan);

// QUERY DATA UTAMA
$data_sppt = mysqli_query($koneksi, "
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
    <title>Kelola Pembayaran SPPT PBB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; font-family: 'Segoe UI', sans-serif; color: #0f172a; }
        .card-custom { border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,.06); background: white; }
        .table-box { max-height: 65vh; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 16px; }
        thead th { position: sticky; top: 0; background: #1e3a8a; color: white; z-index: 5; font-size: 13px; text-transform: uppercase; white-space: nowrap; }
        
        .badge-lunas { background: #dcfce7; color: #15803d; font-weight: 700; border-radius: 20px; padding: 5px 12px; }
        .badge-belum { background: #fee2e2; color: #b91c1c; font-weight: 700; border-radius: 20px; padding: 5px 12px; }
        
        /* Tombol Aksi */
        .btn-action-wrap { display: flex; align-items: center; justify-content: center; gap: 4px; flex-wrap: nowrap; }
        .btn-act {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
            border: none;
            transition: 0.2s;
            white-space: nowrap;
        }
        .btn-act:hover { transform: translateY(-2px); opacity: 0.95; }
        .btn-act-bayar  { background: #16a34a; color: white; }
        .btn-act-batal  { background: #ef4444; color: white; }
        .btn-act-struk  { background: #7c3aed; color: white; }
        .btn-act-tinjau { background: #0284c7; color: white; }
        .btn-act-edit   { background: #f59e0b; color: white; }
    </style>
</head>
<body class="p-4">

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold mb-1 text-dark">💳 Kelola Pembayaran Pajak SPPT PBB</h3>
            <small class="text-muted">Cek status pembayaran warga, lunasi perorangan/kolektif, cetak bukti struk, dan edit data.</small>
        </div>
        <div class="d-flex gap-2">
            <a href="cetak_pembayaran.php?status=<?= urlencode($filter_status) ?>&cari=<?= urlencode($cari) ?>" target="_blank" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm">
                <i class="fa fa-print me-1"></i> Cetak Daftar (Sesuai Filter)
            </a>
            <a href="index.php" class="btn btn-dark rounded-pill px-4 fw-bold shadow-sm">
                <i class="fa fa-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <?php if ($pesan != ""): ?>
        <div class="alert alert-info border-0 shadow-sm rounded-4 mb-4"><?= $pesan ?></div>
    <?php endif; ?>

    <!-- Filter & Pencarian -->
    <div class="card card-custom mb-4">
        <div class="card-body p-4">
            <form method="GET" class="row align-items-end g-3">
                <div class="col-md-5">
                    <label class="form-label fw-bold small text-secondary">CARI DATA:</label>
                    <input type="text" name="cari" class="form-control form-control-lg fs-6 rounded-3" placeholder="Ketik NOP / Nama WP / Nama Pemegang / Blok..." value="<?= htmlspecialchars($cari) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-secondary">STATUS BAYAR:</label>
                    <select name="status" class="form-select form-select-lg fs-6 rounded-3">
                        <option value="semua" <?= ($filter_status === 'semua') ? 'selected' : '' ?>>Semua Status</option>
                        <option value="lunas" <?= ($filter_status === 'lunas') ? 'selected' : '' ?>>Sudah Bayar (Lunas)</option>
                        <option value="belum" <?= ($filter_status === 'belum') ? 'selected' : '' ?>>Belum Bayar</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold rounded-3">
                        <i class="fa fa-search me-1"></i> Terapkan Filter
                    </button>
                    <?php if ($cari !== '' || $filter_status !== 'semua'): ?>
                        <a href="pembayaran.php" class="btn btn-secondary btn-lg rounded-3"><i class="fa fa-times"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Data -->
    <div class="card card-custom">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-0">Daftar Tagihan SPPT</h5>
                    <small class="text-muted">
                        Total: <b><?= number_format($r_ringkasan['total_lembar'] ?? 0, 0, ',', '.') ?> SPPT</b> | 
                        Lunas: <b class="text-success">Rp <?= number_format($r_ringkasan['nominal_lunas'] ?? 0, 0, ',', '.') ?></b> | 
                        Belum: <b class="text-danger">Rp <?= number_format($r_ringkasan['nominal_belum'] ?? 0, 0, ',', '.') ?></b>
                    </small>
                </div>
                <div>
                    <input type="text" id="filterTabel" onkeyup="filterData()" class="form-control rounded-pill px-3" placeholder="🔍 Saring cepat di tabel...">
                </div>
            </div>

            <div class="table-box">
                <table class="table table-hover align-middle mb-0" id="tabelBayar">
                    <thead>
                        <tr>
                            <th style="width: 40px;" class="text-center">No</th>
                            <th>NOP</th>
                            <th>Nama di SPPT (WP)</th>
                            <th>Penanggung Jawab</th>
                            <th>Blok Objek</th>
                            <th>Pajak</th>
                            <th class="text-center">Status</th>
                            <th>Tgl Bayar</th>
                            <th class="text-center" style="width: 250px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if (mysqli_num_rows($data_sppt) == 0):
                        ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted fw-bold">Data SPPT tidak ditemukan.</td>
                        </tr>
                        <?php 
                        else:
                            while ($r = mysqli_fetch_assoc($data_sppt)): 
                                $jsonData = htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr>
                            <td class="text-center text-muted fw-bold"><?= $no++ ?></td>
                            <td class="fw-bold text-primary"><?= htmlspecialchars($r['nop']) ?></td>
                            <td class="fw-bold"><?= strtoupper(htmlspecialchars($r['nama_wajib_pajak'])) ?></td>
                            <td>
                                <?php if (!empty($r['pemegang_sppt'])): ?>
                                    <span class="badge bg-primary">👤 <?= strtoupper(htmlspecialchars($r['pemegang_sppt'])) ?></span>
                                    <?php if (!empty($r['keterangan_pemegang'])): ?>
                                        <br><small class="text-muted"><i class="fa fa-map-marker-alt text-danger"></i> <?= htmlspecialchars($r['keterangan_pemegang']) ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted small">- (WP Asli)</span>
                                <?php endif; ?>
                            </td>
                            <td><small><?= htmlspecialchars($r['blok_tanah'] ?: $r['alamat_objek_pajak']) ?></small></td>
                            <td class="fw-bold">Rp <?= number_format($r['pajak_terhitung'], 0, ',', '.') ?></td>
                            <td class="text-center">
                                <?php if ($r['status'] === 'SUDAH BAYAR'): ?>
                                    <span class="badge-lunas">LUNAS</span>
                                <?php else: ?>
                                    <span class="badge-belum">BELUM</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?= !empty($r['tanggal_bayar']) ? date('d/m/Y H:i', strtotime($r['tanggal_bayar'])) : '-' ?>
                                </small>
                            </td>
                            <td class="text-center">
                                <div class="btn-action-wrap">
                                    <!-- 1. Tombol Bayar / Batal -->
                                    <?php if ($r['status'] === 'SUDAH BAYAR'): ?>
                                        <a href="pembayaran.php?aksi=batal&id=<?= $r['id'] ?>&status=<?= urlencode($filter_status) ?>&cari=<?= urlencode($cari) ?>" class="btn-act btn-act-batal" onclick="return confirm('Batalkan pelunasan?')">
                                            <i class="fa fa-undo"></i> Batal
                                        </a>
                                        <!-- Tombol Struk -->
                                        <a href="cetak_struk.php?id=<?= $r['id'] ?>" target="_blank" class="btn-act btn-act-struk" title="Cetak Struk Bukti Bayar">
                                            <i class="fa fa-receipt"></i> Struk
                                        </a>
                                    <?php else: ?>
                                        <a href="pembayaran.php?aksi=bayar&id=<?= $r['id'] ?>&status=<?= urlencode($filter_status) ?>&cari=<?= urlencode($cari) ?>" class="btn-act btn-act-bayar" onclick="return confirm('Tandai LUNAS?')">
                                            <i class="fa fa-check"></i> Bayar
                                        </a>
                                    <?php endif; ?>

                                    <!-- 2. Tombol Tinjau -->
                                    <button type="button" class="btn-act btn-act-tinjau" onclick="bukaModalTinjau(<?= $jsonData ?>)">
                                        <i class="fa fa-eye"></i> Tinjau
                                    </button>

                                    <!-- 3. Tombol Edit -->
                                    <button type="button" class="btn-act btn-act-edit" onclick="bukaModalEdit(<?= $jsonData ?>)">
                                        <i class="fa fa-pen"></i> Edit
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        endif; 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================
     MODAL POPUP TINJAU DETAIL SPPT
========================================================= -->
<div class="modal fade" id="modalTinjau" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-primary text-white rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fa fa-file-invoice me-2"></i>Rincian SPPT & Pembayaran</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <table class="table table-sm table-borderless">
                    <tr><td class="text-muted" style="width: 140px;">NOP:</td><td class="fw-bold text-primary" id="t_nop">-</td></tr>
                    <tr><td class="text-muted">Nama di SPPT:</td><td class="fw-bold" id="t_nama">-</td></tr>
                    <tr><td class="text-muted">Pemegang / Bayar:</td><td class="fw-bold text-dark" id="t_pemegang">-</td></tr>
                    <tr><td class="text-muted">Alamat Rumah:</td><td id="t_alamat_pemegang">-</td></tr>
                    <tr><td class="text-muted">Blok / Objek:</td><td id="t_blok">-</td></tr>
                    <tr><td class="text-muted">Luas Tanah:</td><td id="t_luas">-</td></tr>
                    <tr><td class="text-muted">Nominal Pajak:</td><td class="fw-bold fs-5 text-success" id="t_pajak">-</td></tr>
                    <tr><td class="text-muted">Status:</td><td id="t_status">-</td></tr>
                    <tr><td class="text-muted">Tanggal Bayar:</td><td id="t_tgl_bayar">-</td></tr>
                </table>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================
     MODAL POPUP EDIT DATA & STATUS PEMBAYARAN
========================================================= -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form method="POST">
                <input type="hidden" name="edit_id" id="e_id">
                <div class="modal-header bg-warning text-dark rounded-top-4">
                    <h5 class="modal-title fw-bold"><i class="fa fa-pen-to-square me-2"></i>Edit Data & Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">NAMA DI SPPT (WAJIB PAJAK):</label>
                        <input type="text" name="edit_nama_wp" id="e_nama_wp" class="form-control rounded-3" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">PENANGGUNG JAWAB (PEMEGANG):</label>
                        <input type="text" name="edit_pemegang" id="e_pemegang" class="form-control rounded-3" placeholder="Kosongkan jika sesuai WP Asli">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">ALAMAT RUMAH / ANCER-ANCER:</label>
                        <input type="text" name="edit_keterangan" id="e_keterangan" class="form-control rounded-3" placeholder="Contoh: RT 02 RW 01 (Sebelah Masjid)">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-secondary">BLOK OBJEK:</label>
                            <input type="text" name="edit_blok" id="e_blok" class="form-control rounded-3">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-secondary">PAJAK TERHITUNG (RP):</label>
                            <input type="number" name="edit_pajak" id="e_pajak" class="form-control rounded-3 fw-bold" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">STATUS PEMBAYARAN:</label>
                        <select name="edit_status" id="e_status" class="form-select rounded-3 fw-bold">
                            <option value="BELUM BAYAR">🔴 BELUM BAYAR (Hapus Status Bayar)</option>
                            <option value="SUDAH BAYAR">🟢 SUDAH BAYAR (Lunas)</option>
                        </select>
                        <small class="text-muted mt-1 d-block">
                            *Jika diubah menjadi <b>BELUM BAYAR</b>, tanggal pembayaran otomatis dikosongkan.
                        </small>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="simpan_edit" class="btn btn-warning rounded-pill px-4 fw-bold">
                        <i class="fa fa-save me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function bukaModalTinjau(d) {
    document.getElementById('t_nop').innerText = d.nop || '-';
    document.getElementById('t_nama').innerText = (d.nama_wajib_pajak || '-').toUpperCase();
    document.getElementById('t_pemegang').innerText = d.pemegang_sppt ? d.pemegang_sppt.toUpperCase() : '- (WP Asli)';
    document.getElementById('t_alamat_pemegang').innerText = d.keterangan_pemegang || '-';
    document.getElementById('t_blok').innerText = d.blok_tanah || d.alamat_objek_pajak || '-';
    document.getElementById('t_luas').innerText = (d.luas_tanah || 0) + ' m²';
    document.getElementById('t_pajak').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(d.pajak_terhitung || 0);
    
    if (d.status === 'SUDAH BAYAR') {
        document.getElementById('t_status').innerHTML = '<span class="badge bg-success">LUNAS</span>';
        document.getElementById('t_tgl_bayar').innerText = d.tanggal_bayar || '-';
    } else {
        document.getElementById('t_status').innerHTML = '<span class="badge bg-danger">BELUM BAYAR</span>';
        document.getElementById('t_tgl_bayar').innerText = '-';
    }

    new bootstrap.Modal(document.getElementById('modalTinjau')).show();
}

function bukaModalEdit(d) {
    document.getElementById('e_id').value = d.id;
    document.getElementById('e_nama_wp').value = d.nama_wajib_pajak || '';
    document.getElementById('e_pemegang').value = d.pemegang_sppt || '';
    document.getElementById('e_keterangan').value = d.keterangan_pemegang || '';
    document.getElementById('e_blok').value = d.blok_tanah || '';
    document.getElementById('e_pajak').value = d.pajak_terhitung || 0;
    document.getElementById('e_status').value = (d.status === 'SUDAH BAYAR') ? 'SUDAH BAYAR' : 'BELUM BAYAR';

    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

function filterData() {
    let input = document.getElementById("filterTabel").value.toUpperCase();
    let rows = document.querySelectorAll("#tabelBayar tbody tr");
    rows.forEach(row => {
        let text = row.innerText.toUpperCase();
        row.style.display = text.indexOf(input) > -1 ? "" : "none";
    });
}
</script>

</body>
</html>