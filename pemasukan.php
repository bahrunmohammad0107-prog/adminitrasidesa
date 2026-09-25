<?php
include 'koneksi.php';
include 'layout.php';

$bulan = date('m');
$tahun = date('Y');

/* FILTER JENIS IURAN */
$iuran_id = isset($_GET['iuran']) ? intval($_GET['iuran']) : 0;

$where = "";
if($iuran_id > 0){
    $where = " WHERE p.iuran_id='$iuran_id' ";
}

/* TOTAL PEMASUKAN */
$total_q = mysqli_query($conn,"
SELECT COALESCE(SUM(p.jumlah_bayar),0) as total
FROM pembayaran p
$where
");
$total = mysqli_fetch_assoc($total_q)['total'];

/* PEMASUKAN BULAN INI */
$where_bulan = $where;

if($iuran_id > 0){
    $where_bulan .= " AND MONTH(p.tanggal)='$bulan' AND YEAR(p.tanggal)='$tahun'";
}else{
    $where_bulan = " WHERE MONTH(p.tanggal)='$bulan' AND YEAR(p.tanggal)='$tahun'";
}

$total_bulan_q = mysqli_query($conn,"
SELECT COALESCE(SUM(p.jumlah_bayar),0) as total
FROM pembayaran p
$where_bulan
");
$total_bulan = mysqli_fetch_assoc($total_bulan_q)['total'];

/* DATA PEMBAYARAN */
$data = mysqli_query($conn,"
SELECT p.*, w.nama, w.nik, j.nama_iuran
FROM pembayaran p
LEFT JOIN warga w ON w.id = p.warga_id
LEFT JOIN jenis_iuran j ON j.id = p.iuran_id
$where
ORDER BY p.tanggal DESC
");
?>

<style>
.kartu{
    border-radius:12px;
    color:white;
    padding:18px;
    box-shadow:0 2px 10px rgba(0,0,0,.15);
}

.table th{
    text-align:center;
}

.judul-halaman{
    font-weight:bold;
}

@media print{
    .no-print{
        display:none;
    }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-3">

    <h3 class="judul-halaman">Laporan Pemasukan</h3>

    <div>

        <a href="cetak_pemasukan.php?iuran=<?= $iuran_id; ?>"
           class="btn btn-danger">
            <i class="fa fa-print"></i>
            Cetak PDF
        </a>

        <a href="index.php"
           class="btn btn-secondary">
            Kembali
        </a>

    </div>

</div>

<!-- FILTER -->
<div class="card mb-3">
    <div class="card-body">

        <form method="GET">

            <div class="row">

                <div class="col-md-8">

                    <label>Jenis Iuran</label>

                    <select name="iuran" class="form-control">

                        <option value="">Semua Jenis Iuran</option>

                        <?php
                        $iuran = mysqli_query($conn,"
                        SELECT *
                        FROM jenis_iuran
                        ORDER BY nama_iuran ASC
                        ");

                        while($j=mysqli_fetch_assoc($iuran)){
                        ?>

                        <option value="<?= $j['id']; ?>"
                        <?= ($iuran_id==$j['id'])?'selected':''; ?>>
                            <?= $j['nama_iuran']; ?>
                        </option>

                        <?php } ?>

                    </select>

                </div>

                <div class="col-md-4">

                    <label>&nbsp;</label><br>

                    <button class="btn btn-primary">
                        Tampilkan
                    </button>

                    <a href="pemasukan.php"
                       class="btn btn-secondary">
                        Reset
                    </a>

                </div>

            </div>

        </form>

    </div>
</div>

<!-- RINGKASAN -->
<div class="row mb-3">

    <div class="col-md-6">

        <div class="kartu bg-dark">

            <h5>Total Pemasukan</h5>

            <h3>
                Rp <?= number_format($total,0,',','.'); ?>
            </h3>

        </div>

    </div>

    <div class="col-md-6">

        <div class="kartu bg-success">

            <h5>Pemasukan Bulan Ini</h5>

            <h3>
                Rp <?= number_format($total_bulan,0,',','.'); ?>
            </h3>

        </div>

    </div>

</div>

<!-- DATA -->
<div class="card">

    <div class="card-header bg-primary text-white">
        Data Pemasukan
    </div>

    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-bordered table-striped table-hover">

                <thead class="table-primary">

                    <tr>
                        <th width="5%">No</th>
                        <th>NIK</th>
                        <th>Nama</th>
                        <th>Jenis Iuran</th>
                        <th>Tanggal</th>
                        <th>Jumlah Bayar</th>
                        <th width="15%">Aksi</th>
                    </tr>

                </thead>

                <tbody>

                <?php
                $no=1;

                while($d=mysqli_fetch_assoc($data)){
                ?>

                <tr>

                    <td><?= $no++; ?></td>

                    <td><?= $d['nik']; ?></td>

                    <td><?= $d['nama']; ?></td>

                    <td>
                        <span class="badge bg-info">
                            <?= $d['nama_iuran']; ?>
                        </span>
                    </td>

                    <td>
                        <?= date('d-m-Y', strtotime($d['tanggal'])); ?>
                    </td>

                    <td>
                        Rp <?= number_format($d['jumlah_bayar'],0,',','.'); ?>
                    </td>

                    <td>

                        <a href="edit_pembayaran.php?id=<?= $d['id']; ?>"
                           class="btn btn-warning btn-sm">
                            Edit
                        </a>

                        <a href="hapus_pembayaran.php?id=<?= $d['id']; ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Yakin hapus pembayaran ini?')">
                            Hapus
                        </a>

                    </td>

                </tr>

                <?php } ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<?php include 'footer.php'; ?>