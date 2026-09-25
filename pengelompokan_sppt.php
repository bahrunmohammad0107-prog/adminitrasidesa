<?php
session_start();
include "../cek_login.php";
include "../koneksi.php";

$desa_id  = intval($_SESSION['desa_id'] ?? 1);
$level    = strtolower(trim($_SESSION['level'] ?? 'kadus'));
$dusun_id = intval($_SESSION['dusun_id'] ?? 0);

$pesan = "";

// PROSES SIMPAN KELOMPOK BARU
if (isset($_POST['simpan_kelompok'])) {
    $pemegang_baru   = strtoupper(trim($_POST['nama_pemegang'] ?? ''));
    $keterangan_baru = trim($_POST['keterangan_pemegang'] ?? '');
    $pilih_sppt      = $_POST['sppt_id'] ?? [];

    if ($pemegang_baru === '') {
        $pesan = "⚠️ Nama Pemegang / Penanggung Jawab wajib diisi!";
    } elseif (empty($pilih_sppt)) {
        $pesan = "⚠️ Pilih minimal satu SPPT untuk dikelompokkan!";
    } else {
        $sukses = 0;
        $pemegang_safe   = mysqli_real_escape_string($koneksi, $pemegang_baru);
        $keterangan_safe = mysqli_real_escape_string($koneksi, $keterangan_baru);

        foreach ($pilih_sppt as $sppt_id) {
            $sppt_id = intval($sppt_id);
            $q_up = mysqli_query($koneksi, "UPDATE pajak_sppt SET 
                pemegang_sppt = '$pemegang_safe',
                keterangan_pemegang = '$keterangan_safe'
                WHERE id = '$sppt_id' AND desa_id = '$desa_id'");
            if ($q_up) $sukses++;
        }
        $pesan = "✅ Berhasil mengelompokkan $sukses SPPT di bawah: <b>$pemegang_baru</b>";
    }
}

// PROSES EDIT NAMA / ALAMAT PEMEGANG MASSAL
if (isset($_POST['update_kelompok'])) {
    $pemegang_lama   = mysqli_real_escape_string($koneksi, trim($_POST['pemegang_lama'] ?? ''));
    $pemegang_update = strtoupper(trim($_POST['pemegang_baru'] ?? ''));
    $keterangan_up   = trim($_POST['keterangan_baru'] ?? '');

    if ($pemegang_update !== '' && $pemegang_lama !== '') {
        $pemegang_up_safe = mysqli_real_escape_string($koneksi, $pemegang_update);
        $keterangan_up_safe = mysqli_real_escape_string($koneksi, $keterangan_up);

        $q_up_all = mysqli_query($koneksi, "UPDATE pajak_sppt SET 
            pemegang_sppt = '$pemegang_up_safe',
            keterangan_pemegang = '$keterangan_up_safe'
            WHERE pemegang_sppt = '$pemegang_lama' AND desa_id = '$desa_id'");

        if ($q_up_all) {
            $pesan = "✅ Data Pemegang <b>$pemegang_update</b> berhasil diperbarui!";
        }
    }
}

// PROSES LEPAS KELOMPOK
if (isset($_GET['reset_id'])) {
    $id_reset = intval($_GET['reset_id']);
    mysqli_query($koneksi, "UPDATE pajak_sppt SET pemegang_sppt = NULL, keterangan_pemegang = NULL WHERE id = '$id_reset' AND desa_id = '$desa_id'");
    header("Location: pengelompokan_sppt.php?pesan=reset_sukses");
    exit;
}

$where = "WHERE pajak_sppt.desa_id = '$desa_id'";
if ($level === 'kadus' && $dusun_id > 0) {
    $where .= " AND pajak_sppt.dusun_id = '$dusun_id'";
}

// URUTAN SESUAI UNGGAHAN EXCEL (no_urut)
$data_sppt = mysqli_query(
    $koneksi,
    "SELECT pajak_sppt.*, dusun.nama_dusun 
     FROM pajak_sppt 
     LEFT JOIN dusun ON pajak_sppt.dusun_id = dusun.id 
     $where 
     ORDER BY pajak_sppt.no_urut ASC, pajak_sppt.id ASC"
);

// DATA MAP UNTUK MODAL TINJAU & EDIT
$kelompok_map = [];
$q_all = mysqli_query($koneksi, "SELECT id, nop, nama_wajib_pajak, pemegang_sppt, keterangan_pemegang, blok_tanah, pajak_terhitung, status FROM pajak_sppt WHERE desa_id = '$desa_id' AND pemegang_sppt IS NOT NULL AND pemegang_sppt != ''");
if ($q_all) {
    while ($row = mysqli_fetch_assoc($q_all)) {
        $p = strtoupper(trim($row['pemegang_sppt']));
        if (!isset($kelompok_map[$p])) {
            $kelompok_map[$p] = [
                'pemegang'   => $p,
                'keterangan' => $row['keterangan_pemegang'] ?? '',
                'total'      => 0,
                'items'      => []
            ];
        }
        $kelompok_map[$p]['total'] += floatval($row['pajak_terhitung']);
        $kelompok_map[$p]['items'][] = [
            'id'     => $row['id'],
            'nop'    => $row['nop'],
            'nama'   => strtoupper($row['nama_wajib_pajak']),
            'blok'   => $row['blok_tanah'],
            'pajak'  => number_format($row['pajak_terhitung'], 0, ',', '.'),
            'status' => $row['status']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengelompokan SPPT & Pemegang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; font-family: 'Segoe UI', sans-serif; color: #1e293b; }
        .card-custom { border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,.05); background: white; }
        .table-box { max-height: 65vh; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 16px; }
        thead th { position: sticky; top: 0; background: #1e3a8a; color: white; z-index: 5; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Modern Action Button Styling */
        .btn-action-group { display: inline-flex; align-items: center; gap: 6px; }
        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
        }
        .btn-action:hover { transform: translateY(-2px); box-shadow: 0 5px 12px rgba(0,0,0,0.12); }
        .btn-action:active { transform: translateY(0); }

        .btn-tinjau-modern { background: linear-gradient(135deg, #0284c7, #0369a1); color: white; }
        .btn-tinjau-modern:hover { color: white; background: linear-gradient(135deg, #0369a1, #075985); }

        .btn-edit-modern { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        .btn-edit-modern:hover { color: white; background: linear-gradient(135deg, #d97706, #b45309); }

        .btn-lepas-modern { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        .btn-lepas-modern:hover { background: #ef4444; color: white; border-color: #ef4444; }

        .badge-pemegang {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 12px;
            display: inline-block;
        }
        .lokasi-text { color: #64748b; font-size: 12px; font-weight: 500; }
    </style>
</head>
<body class="p-4">

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold mb-1 text-dark">👥 Pengelompokan SPPT & Lokasi Rumah</h3>
            <small class="text-muted">Kelola SPPT yang ditagihkan kepada satu penanggung jawab beserta ancer-ancer lokasinya.</small>
        </div>
        <div class="d-flex gap-2">
            <a href="laporan_kelompok_sppt.php" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">
                <i class="fa fa-print me-1"></i> Laporan Kelompok
            </a>
            <a href="index.php" class="btn btn-dark rounded-pill px-4 shadow-sm fw-bold">
                <i class="fa fa-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <?php if ($pesan != ""): ?>
        <div class="alert alert-info border-0 shadow-sm rounded-4 mb-4"><?= $pesan ?></div>
    <?php endif; ?>

    <!-- Form Input Kelompok -->
    <form method="POST">
        <div class="card card-custom mb-4">
            <div class="card-body p-4">
                <div class="row align-items-end g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-secondary small">NAMA PENANGGUNG JAWAB (PEMEGANG):</label>
                        <input type="text" name="nama_pemegang" class="form-control form-control-lg fs-6 rounded-3" placeholder="Contoh: DONI" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold text-secondary small">ALAMAT RUMAH / ANCER-ANCER:</label>
                        <input type="text" name="keterangan_pemegang" class="form-control form-control-lg fs-6 rounded-3" placeholder="Contoh: RT 02 RW 01 (Sebelah Masjid / Rumah Tingkat Abu-abu)">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" name="simpan_kelompok" class="btn btn-success btn-lg w-100 fw-bold rounded-3 shadow-sm">
                            <i class="fa fa-link me-1"></i> Gabungkan Tercentang
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel SPPT -->
        <div class="card card-custom">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h5 class="fw-bold mb-0">Daftar Lembar SPPT</h5>
                    <div>
                        <input type="text" id="filterTabel" onkeyup="filterData()" class="form-control rounded-pill px-3" placeholder="🔍 Cari Nama / NOP / Blok / Pemegang..." style="width: 350px;">
                    </div>
                </div>

                <div class="table-box">
                    <table class="table table-hover align-middle mb-0" id="tabelSppt">
                        <thead>
                            <tr>
                                <th style="width: 40px;" class="text-center"><input type="checkbox" id="checkAll" onclick="toggleCheck(this)"></th>
                                <th style="width: 50px;" class="text-center">No</th>
                                <th>NOP</th>
                                <th>Nama di SPPT</th>
                                <th>Alamat Objek / Blok</th>
                                <th>Pajak</th>
                                <th>Status</th>
                                <th>Penanggung Jawab & Lokasi</th>
                                <th class="text-center" style="width: 220px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            while ($r = mysqli_fetch_assoc($data_sppt)): 
                                $pemegang_key = strtoupper(trim($r['pemegang_sppt'] ?? ''));
                            ?>
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" name="sppt_id[]" value="<?= $r['id'] ?>" class="sppt-check">
                                </td>
                                <td class="text-center text-muted fw-bold"><?= $no++ ?></td>
                                <td class="fw-bold text-primary"><?= htmlspecialchars($r['nop']) ?></td>
                                <td class="fw-bold"><?= strtoupper(htmlspecialchars($r['nama_wajib_pajak'])) ?></td>
                                <td><small><?= htmlspecialchars($r['blok_tanah'] ?: $r['alamat_objek_pajak']) ?></small></td>
                                <td class="fw-bold">Rp <?= number_format($r['pajak_terhitung'], 0, ',', '.') ?></td>
                                <td>
                                    <?php if ($r['status'] == 'SUDAH BAYAR'): ?>
                                        <span class="badge bg-success">LUNAS</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">BELUM</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($r['pemegang_sppt'])): ?>
                                        <span class="badge-pemegang">👤 <?= strtoupper(htmlspecialchars($r['pemegang_sppt'])) ?></span>
                                        <?php if (!empty($r['keterangan_pemegang'])): ?>
                                            <div class="lokasi-text mt-1">
                                                <i class="fa fa-map-marker-alt text-danger me-1"></i><?= htmlspecialchars($r['keterangan_pemegang']) ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">- (Sesuai WP Asli)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($r['pemegang_sppt'])): ?>
                                        <div class="btn-action-group">
                                            <!-- Tombol Tinjau -->
                                            <button type="button" class="btn-action btn-tinjau-modern" onclick="bukaModalTinjau('<?= htmlspecialchars($pemegang_key, ENT_QUOTES) ?>')">
                                                <i class="fa fa-eye"></i> Tinjau
                                            </button>
                                            
                                            <!-- Tombol Edit -->
                                            <button type="button" class="btn-action btn-edit-modern" onclick="bukaModalEdit('<?= htmlspecialchars($pemegang_key, ENT_QUOTES) ?>')">
                                                <i class="fa fa-pen"></i> Edit
                                            </button>

                                            <!-- Tombol Lepas -->
                                            <a href="pengelompokan_sppt.php?reset_id=<?= $r['id'] ?>" class="btn-action btn-lepas-modern" onclick="return confirm('Lepaskan SPPT ini dari kelompok?')">
                                                <i class="fa fa-unlink"></i> Lepas
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- =========================================================
     MODAL POPUP TINJAU KELOMPOK
========================================================= -->
<div class="modal fade" id="modalTinjau" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-primary text-white rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fa fa-layer-group me-2"></i>Rincian SPPT Kelompok</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="p-3 bg-light rounded-3 mb-3 border">
                    <div class="fw-bold fs-5 text-primary" id="modalPemegang">-</div>
                    <div class="text-muted small mt-1" id="modalAncerAncer">-</div>
                </div>
                <h6 class="fw-bold mb-2">Daftar Lembar SPPT yang Dipegang:</h6>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-secondary">
                            <tr>
                                <th>No</th>
                                <th>NOP</th>
                                <th>Nama di SPPT</th>
                                <th>Blok Objek</th>
                                <th>Tagihan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="modalTabelBody"></tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="4" class="text-end">TOTAL TAGIHAN KOLEKTIF:</td>
                                <td colspan="2" class="text-primary fs-6" id="modalTotalTagihan">Rp 0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================
     MODAL POPUP EDIT ALAMAT / NAMA PEMEGANG
========================================================= -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form method="POST">
                <div class="modal-header bg-warning text-dark rounded-top-4">
                    <h5 class="modal-title fw-bold"><i class="fa fa-pen me-2"></i>Edit Informasi Pemegang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="pemegang_lama" id="editPemegangLama">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">NAMA PENANGGUNG JAWAB (PEMEGANG):</label>
                        <input type="text" name="pemegang_baru" id="editPemegangBaru" class="form-control rounded-3" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">ALAMAT RUMAH / ANCER-ANCER:</label>
                        <textarea name="keterangan_baru" id="editKeteranganBaru" class="form-control rounded-3" rows="3" placeholder="Contoh: RT 02 RW 01 (Sebelah Masjid)"></textarea>
                    </div>
                    
                    <small class="text-muted d-block">
                        ℹ️ Perubahan alamat/nama ini otomatis diperbarui pada <b>seluruh SPPT</b> yang dipegang oleh orang ini.
                    </small>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="update_kelompok" class="btn btn-warning rounded-pill px-4 fw-bold">
                        <i class="fa fa-save me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const dataKelompok = <?= json_encode($kelompok_map); ?>;

// FUNGSI POPUP TINJAU
function bukaModalTinjau(pemegang) {
    const data = dataKelompok[pemegang];
    if (!data) return;

    document.getElementById('modalPemegang').innerText = "👤 Penanggung Jawab: " + data.pemegang;
    document.getElementById('modalAncerAncer').innerHTML = "📍 <b>Lokasi / Ancer-ancer:</b> " + (data.keterangan || "Belum ada keterangan lokasi");

    const tbody = document.getElementById('modalTabelBody');
    tbody.innerHTML = "";

    data.items.forEach((item, index) => {
        const statusBadge = item.status === 'SUDAH BAYAR' 
            ? '<span class="badge bg-success">LUNAS</span>' 
            : '<span class="badge bg-danger">BELUM</span>';

        const row = `<tr>
            <td class="text-center">${index + 1}</td>
            <td class="fw-bold text-primary">${item.nop}</td>
            <td class="fw-bold">${item.nama}</td>
            <td><small>${item.blok || '-'}</small></td>
            <td class="fw-bold">Rp ${item.pajak}</td>
            <td>${statusBadge}</td>
        </tr>`;
        tbody.innerHTML += row;
    });

    document.getElementById('modalTotalTagihan').innerText = "Rp " + new Intl.NumberFormat('id-ID').format(data.total);

    new bootstrap.Modal(document.getElementById('modalTinjau')).show();
}

// FUNGSI POPUP EDIT
function bukaModalEdit(pemegang) {
    const data = dataKelompok[pemegang];
    if (!data) return;

    document.getElementById('editPemegangLama').value = data.pemegang;
    document.getElementById('editPemegangBaru').value = data.pemegang;
    document.getElementById('editKeteranganBaru').value = data.keterangan || "";

    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

// CENTANG SEMUA
function toggleCheck(source) {
    let checkboxes = document.querySelectorAll('.sppt-check');
    for (let i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = source.checked;
    }
}

// FILTER INSTAN
function filterData() {
    let input = document.getElementById("filterTabel").value.toUpperCase();
    let rows = document.querySelectorAll("#tabelSppt tbody tr");
    rows.forEach(row => {
        let text = row.innerText.toUpperCase();
        row.style.display = text.indexOf(input) > -1 ? "" : "none";
    });
}
</script>

</body>
</html>