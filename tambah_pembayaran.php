<?php
include 'koneksi.php';
include 'layout.php';

$warga = mysqli_query($conn,"SELECT * FROM warga ORDER BY nama ASC");
$jenis = mysqli_query($conn,"SELECT * FROM jenis_iuran ORDER BY nama_iuran ASC");
?>

<div class="container mt-3">

<div class="card shadow-lg border-0">

    <div class="card-header bg-primary text-white">
        <h4 class="mb-0">💰 Tambah Pembayaran Iuran</h4>
    </div>

    <div class="card-body">

        <form action="proses_pembayaran.php" method="POST">

            <!-- WARGA -->
            <div class="mb-3">
                <label class="form-label">Pilih Warga</label>
                <select name="warga_id" class="form-select" required>
                    <option value="">-- Pilih Warga --</option>
                    <?php while($w=mysqli_fetch_assoc($warga)){ ?>
                        <option value="<?= $w['id']; ?>">
                            <?= $w['nama']; ?> - <?= $w['nik']; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <!-- JENIS IURAN -->
            <div class="mb-3">
                <label class="form-label">Jenis Iuran</label>
                <select name="jenis_iuran_id" class="form-select" required>
                    <option value="">-- Pilih Jenis Iuran --</option>
                    <?php while($j=mysqli_fetch_assoc($jenis)){ ?>
                        <option value="<?= $j['id']; ?>">
                            <?= $j['nama_iuran']; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <!-- JUMLAH -->
            <div class="mb-3">
                <label class="form-label">Jumlah Bayar</label>
                <input type="number" name="jumlah_bayar" class="form-control" placeholder="Masukkan nominal..." required>
            </div>

            <!-- TANGGAL -->
            <div class="mb-3">
                <label class="form-label">Tanggal</label>
                <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d'); ?>" required>
            </div>

            <!-- BUTTON -->
            <div class="d-flex justify-content-between">

                <a href="pembayaran.php" class="btn btn-secondary">
                    ⬅ Kembali
                </a>

                <button type="submit" class="btn btn-success">
                    💾 Simpan Pembayaran
                </button>

            </div>

        </form>

    </div>
</div>

</div>

<?php include 'footer.php'; ?>