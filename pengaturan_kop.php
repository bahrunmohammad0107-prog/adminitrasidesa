<?php
session_start();
include "../cek_login.php";
include "../koneksi.php";

$level = strtolower(trim($_SESSION['level'] ?? 'admin'));
if ($level !== 'admin') {
    die("<div style='padding:40px; font-family:sans-serif; text-align:center;'><h3>Akses Ditolak!</h3><p>Hanya Admin Desa yang berhak mengatur Kop Surat dan Profil Desa.</p><a href='data_sppt.php'>Kembali ke Data SPPT</a></div>");
}

$desa_id = intval($_SESSION['desa_id'] ?? 1);
$pesan = "";

// 1. OTOMATIS BUAT TABEL JIKA BELUM ADA DI DATABASE
mysqli_query($koneksi, "
    CREATE TABLE IF NOT EXISTS profil_desa (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        nama_desa VARCHAR(100) NOT NULL DEFAULT 'AMBALKLIWONAN',
        kecamatan VARCHAR(100) NOT NULL DEFAULT 'AMBAL',
        kabupaten VARCHAR(100) NOT NULL DEFAULT 'KEBUMEN',
        provinsi VARCHAR(100) NOT NULL DEFAULT 'JAWA TENGAH',
        alamat_kantor TEXT NULL,
        kode_pos VARCHAR(10) NULL DEFAULT '54392',
        no_telp VARCHAR(30) NULL,
        email VARCHAR(100) NULL,
        nama_kades VARCHAR(100) NULL,
        logo VARCHAR(255) NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Pastikan ada 1 baris data awal
$cek_data = mysqli_query($koneksi, "SELECT * FROM profil_desa WHERE id = '$desa_id' LIMIT 1");
if (!$cek_data || mysqli_num_rows($cek_data) == 0) {
    mysqli_query($koneksi, "INSERT INTO profil_desa (id, nama_desa, kecamatan, kabupaten, provinsi, alamat_kantor, kode_pos, no_telp, email, nama_kades, logo) 
        VALUES ('$desa_id', 'AMBALKLIWONAN', 'AMBAL', 'KEBUMEN', 'JAWA TENGAH', 'Jl. Raya Desa Ambalkliwonan, Kecamatan Ambal, Kabupaten Kebumen', '54392', '08123456789', 'pemdes.ambalkliwonan@gmail.com', '', 'logo.png')");
    $cek_data = mysqli_query($koneksi, "SELECT * FROM profil_desa WHERE id = '$desa_id' LIMIT 1");
}
$desa = mysqli_fetch_assoc($cek_data) ?: [];

// 2. PROSES SIMPAN PENGATURAN KOP
if (isset($_POST['simpan_kop'])) {
    $nama_desa     = mysqli_real_escape_string($koneksi, strtoupper(trim($_POST['nama_desa'] ?? '')));
    $kecamatan     = mysqli_real_escape_string($koneksi, strtoupper(trim($_POST['kecamatan'] ?? '')));
    $kabupaten     = mysqli_real_escape_string($koneksi, strtoupper(trim($_POST['kabupaten'] ?? '')));
    $provinsi      = mysqli_real_escape_string($koneksi, strtoupper(trim($_POST['provinsi'] ?? '')));
    $alamat_kantor = mysqli_real_escape_string($koneksi, trim($_POST['alamat_kantor'] ?? ''));
    $kode_pos      = mysqli_real_escape_string($koneksi, trim($_POST['kode_pos'] ?? ''));
    $no_telp       = mysqli_real_escape_string($koneksi, trim($_POST['no_telp'] ?? ''));
    $email         = mysqli_real_escape_string($koneksi, trim($_POST['email'] ?? ''));
    $nama_kades    = mysqli_real_escape_string($koneksi, strtoupper(trim($_POST['nama_kades'] ?? '')));
    $nama_logo     = $desa['logo'] ?? 'logo.png';

    // Proses Unggah Gambar Logo
    if (isset($_FILES['file_logo']) && $_FILES['file_logo']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['file_logo']['tmp_name'];
        $file_name = $_FILES['file_logo']['name'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $ext_valid = array('png', 'jpg', 'jpeg');

        if (in_array($file_ext, $ext_valid)) {
            $folder_tujuan = "../assets/";
            if (!is_dir($folder_tujuan)) {
                mkdir($folder_tujuan, 0777, true);
            }
            $nama_logo_baru = "logo_desa_" . $desa_id . "_" . time() . "." . $file_ext;
            if (move_uploaded_file($file_tmp, $folder_tujuan . $nama_logo_baru)) {
                $nama_logo = $nama_logo_baru;
            }
        } else {
            $pesan = "⚠️ Format logo harus .PNG, .JPG, atau .JPEG!";
        }
    }

    $q_upd = mysqli_query($koneksi, "UPDATE profil_desa SET 
        nama_desa = '$nama_desa',
        kecamatan = '$kecamatan',
        kabupaten = '$kabupaten',
        provinsi = '$provinsi',
        alamat_kantor = '$alamat_kantor',
        kode_pos = '$kode_pos',
        no_telp = '$no_telp',
        email = '$email',
        nama_kades = '$nama_kades',
        logo = '$nama_logo'
        WHERE id = '$desa_id'
    ");

    if ($q_upd) {
        $pesan = "✅ Profil dan Kop Surat Desa berhasil diperbarui!";
        $q_refresh = mysqli_query($koneksi, "SELECT * FROM profil_desa WHERE id = '$desa_id' LIMIT 1");
        $desa = mysqli_fetch_assoc($q_refresh) ?: [];
    }
}

// Inisialisasi data aman untuk HTML (Mencegah PHP 8.1+ Null Deprecation)
$v_nama_desa     = $desa['nama_desa'] ?? 'AMBALKLIWONAN';
$v_kecamatan     = $desa['kecamatan'] ?? 'AMBAL';
$v_kabupaten     = $desa['kabupaten'] ?? 'KEBUMEN';
$v_provinsi      = $desa['provinsi'] ?? 'JAWA TENGAH';
$v_alamat_kantor = $desa['alamat_kantor'] ?? 'Jl. Raya Desa Ambalkliwonan, Kecamatan Ambal, Kabupaten Kebumen';
$v_kode_pos      = $desa['kode_pos'] ?? '54392';
$v_no_telp       = $desa['no_telp'] ?? '';
$v_email         = $desa['email'] ?? '';
$v_nama_kades    = $desa['nama_kades'] ?? '';
$v_logo          = $desa['logo'] ?? 'logo.png';

$logo_path = (!empty($v_logo) && file_exists("../assets/" . $v_logo)) ? "../assets/" . $v_logo : "logo.png";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengaturan Kop Surat & Profil Desa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8fafc; font-family: 'Plus Jakarta Sans', sans-serif; color: #0f172a; }
        .main-card { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05); }
        .header-box { background: linear-gradient(135deg, #0f172a, #1e293b); color: white; border-radius: 16px 16px 0 0; padding: 22px 28px; }
        .form-label-custom { font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px; }
        .form-control-custom { font-size: 13px; border-radius: 9px; border: 1px solid #cbd5e1; padding: 9px 13px; font-weight: 500; }
        .form-control-custom:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12); }
        
        .kop-preview-box {
            background: #ffffff;
            border: 2px dashed #94a3b8;
            border-radius: 12px;
            padding: 25px 30px;
            margin-top: 15px;
            font-family: "Times New Roman", Times, serif;
        }
        .kop-preview-header {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            border-bottom: 3px double #000;
            padding-bottom: 12px;
        }
        .kop-preview-logo {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            max-width: 70px;
            max-height: 70px;
            object-fit: contain;
        }
        .kop-preview-text {
            text-align: center;
            width: 100%;
            padding: 0 80px;
        }
    </style>
</head>
<body class="p-4">

<div class="container" style="max-width: 1000px;">
    
    <div class="main-card mb-4">
        <div class="header-box d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4 class="fw-bold mb-1"><i class="fa fa-stamp me-2 text-warning"></i>PENGATURAN KOP SURAT & PROFIL DESA</h4>
                <small class="opacity-75">Sesuaikan identitas instansi desa untuk seluruh lembar cetak dokumen resmi sistem.</small>
            </div>
            <a href="index.php" class="btn btn-outline-light btn-sm rounded-pill px-3 fw-bold">
                <i class="fa fa-arrow-left me-1"></i> Kembali ke Dashboard
            </a>
        </div>

        <div class="p-4">
            <?php if ($pesan != ""): ?>
                <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4 fw-bold"><?= $pesan ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="row g-3">
                
                <!-- BAGIAN 1: LOGO DESA -->
                <div class="col-12">
                    <div class="p-3 bg-light rounded-3 border d-flex align-items-center gap-4 flex-wrap">
                        <div class="text-center" style="width: 100px;">
                            <img src="<?= $logo_path ?>" alt="Logo Desa" id="img_preview" class="img-thumbnail rounded-3" style="max-height: 85px; max-width: 85px; object-fit: contain;" onerror="this.src='logo.png'; this.onerror=null;">
                            <small class="d-block text-muted mt-1 fw-bold" style="font-size: 10px;">LOGO SAAT INI</small>
                        </div>
                        <div class="flex-grow-1">
                            <label class="form-label-custom"><i class="fa fa-image me-1 text-primary"></i> Unggah Logo Instansi Baru (Format .PNG / .JPG):</label>
                            <input type="file" name="file_logo" class="form-control form-control-custom" accept="image/png, image/jpeg" onchange="previewGambar(this)">
                            <small class="text-muted" style="font-size: 11.5px;">Gunakan gambar berlatar belakang transparan (.png) untuk hasil cetak terbaik.</small>
                        </div>
                    </div>
                </div>

                <!-- BAGIAN 2: IDENTITAS DESA -->
                <div class="col-md-6">
                    <label class="form-label-custom">Nama Desa:</label>
                    <input type="text" name="nama_desa" class="form-control form-control-custom fw-bold" value="<?= htmlspecialchars((string)$v_nama_desa) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label-custom">Kecamatan:</label>
                    <input type="text" name="kecamatan" class="form-control form-control-custom fw-bold" value="<?= htmlspecialchars((string)$v_kecamatan) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label-custom">Kabupaten:</label>
                    <input type="text" name="kabupaten" class="form-control form-control-custom fw-bold" value="<?= htmlspecialchars((string)$v_kabupaten) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label-custom">Provinsi:</label>
                    <input type="text" name="provinsi" class="form-control form-control-custom fw-bold" value="<?= htmlspecialchars((string)$v_provinsi) ?>" required>
                </div>

                <div class="col-md-8">
                    <label class="form-label-custom">Alamat Kantor Desa:</label>
                    <input type="text" name="alamat_kantor" class="form-control form-control-custom" value="<?= htmlspecialchars((string)$v_alamat_kantor) ?>" placeholder="Jl. Raya Desa No. ..." required>
                </div>

                <div class="col-md-4">
                    <label class="form-label-custom">Kode Pos:</label>
                    <input type="text" name="kode_pos" class="form-control form-control-custom font-monospace" value="<?= htmlspecialchars((string)$v_kode_pos) ?>" placeholder="54392">
                </div>

                <div class="col-md-6">
                    <label class="form-label-custom">Nomor Telepon / WhatsApp Kantor:</label>
                    <input type="text" name="no_telp" class="form-control form-control-custom" value="<?= htmlspecialchars((string)$v_no_telp) ?>" placeholder="08xxxxxxxxxx / (0287) xxxxxx">
                </div>

                <div class="col-md-6">
                    <label class="form-label-custom">Email Resmi Desa:</label>
                    <input type="email" name="email" class="form-control form-control-custom" value="<?= htmlspecialchars((string)$v_email) ?>" placeholder="pemdes.desa@gmail.com">
                </div>

                <div class="col-12">
                    <label class="form-label-custom">Nama Lengkap Kepala Desa (Untuk Pengesahan Tanda Tangan):</label>
                    <input type="text" name="nama_kades" class="form-control form-control-custom fw-bold" value="<?= htmlspecialchars((string)$v_nama_kades) ?>" placeholder="Contoh: H. AHMAD FAUZI, S.IP (Bisa dikosongkan)">
                </div>

                <div class="col-12 text-end pt-3">
                    <button type="submit" name="simpan_kop" class="btn btn-primary rounded-pill px-5 fw-bold py-2 shadow-sm">
                        <i class="fa fa-save me-1"></i> Simpan Pengaturan Kop
                    </button>
                </div>
            </form>

            <!-- LIVE PREVIEW KOP SURAT -->
            <div class="mt-4 pt-3 border-top">
                <h6 class="fw-bold text-secondary mb-2"><i class="fa fa-eye me-1"></i> Pratinjau Tampilan Kop Surat Resmi:</h6>
                <div class="kop-preview-box">
                    <div class="kop-preview-header">
                        <img src="<?= $logo_path ?>" alt="Logo" class="kop-preview-logo" onerror="this.src='logo.png'; this.onerror=null;">
                        <div class="kop-preview-text">
                            <div style="font-size: 15px; font-weight: bold; text-transform: uppercase;">PEMERINTAH KABUPATEN <?= htmlspecialchars((string)$v_kabupaten) ?></div>
                            <div style="font-size: 15px; font-weight: bold; text-transform: uppercase;">KECAMATAN <?= htmlspecialchars((string)$v_kecamatan) ?></div>
                            <div style="font-size: 18px; font-weight: bold; text-transform: uppercase; margin: 2px 0;">KANTOR KEPALA DESA <?= htmlspecialchars((string)$v_nama_desa) ?></div>
                            <div style="font-size: 11px; font-style: italic;">
                                <?= htmlspecialchars((string)$v_alamat_kantor) ?> - Kode Pos <?= htmlspecialchars((string)$v_kode_pos) ?>
                                <?php if (!empty($v_no_telp)): ?> | Telp: <?= htmlspecialchars((string)$v_no_telp) ?><?php endif; ?>
                                <?php if (!empty($v_email)): ?> | Email: <?= htmlspecialchars((string)$v_email) ?><?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

<script>
function previewGambar(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('img_preview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

</body>
</html>