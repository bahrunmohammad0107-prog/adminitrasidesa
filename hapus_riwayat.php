<?php

session_start();

include "../cek_login.php";
include "../koneksi.php";

$desa_id = intval($_SESSION['desa_id'] ?? 1);
$id      = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    die("ID transaksi tidak valid.");
}

// 1. Ambil data transaksi yang akan dihapus
$stmt = mysqli_prepare($koneksi, "SELECT * FROM riwayat_tanah WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$transaksi = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$transaksi) {
    die("Data transaksi tidak ditemukan.");
}

$sppt_id        = intval($transaksi['sppt_id']);
$nop            = $transaksi['nop'] ?? '';
$kepada_pemilik = trim($transaksi['kepada_pemilik'] ?? '');

// 2. Ambil data SPPT
$stmt = mysqli_prepare($koneksi, "SELECT * FROM pajak_sppt WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $sppt_id);
mysqli_stmt_execute($stmt);
$sppt = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$nama_wp_sppt = $sppt['nama_wajib_pajak'] ?? $sppt['nama_wp'] ?? '';
$luas_sppt    = floatval($sppt['luas_tanah'] ?? 0);

mysqli_begin_transaction($koneksi);

try {
    // 3. Validasi Proteksi: Cegah hapus jika orang ini sudah menjual tanahnya ke orang lain
    $stmt = mysqli_prepare($koneksi, "
        SELECT COUNT(*) as total_cabang 
        FROM riwayat_tanah 
        WHERE ((nop = ? AND nop != '') OR sppt_id = ?) 
          AND LOWER(TRIM(dari_pemilik)) = LOWER(TRIM(?)) 
          AND id != ?
    ");
    mysqli_stmt_bind_param($stmt, "sisi", $nop, $sppt_id, $kepada_pemilik, $id);
    mysqli_stmt_execute($stmt);
    $cek_cabang = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (intval($cek_cabang['total_cabang'] ?? 0) > 0) {
        throw new Exception("Tidak bisa dihapus! " . strtoupper($kepada_pemilik) . " sudah memiliki transaksi penjualan lanjutan ke orang lain.");
    }

    // 4. Hapus baris transaksi dari tabel riwayat_tanah
    $stmt = mysqli_prepare($koneksi, "DELETE FROM riwayat_tanah WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // 5. REKALKULASI TOTAL: Reset dan hitung ulang saldo kepemilikan_tanah dari awal
    // Hapus seluruh kepemilikan di SPPT/NOP ini
    $stmt = mysqli_prepare($koneksi, "DELETE FROM kepemilikan_tanah WHERE (nop = ? AND nop != '') OR sppt_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $nop, $sppt_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Ambil sisa transaksi yang masih ada
    $stmt = mysqli_prepare($koneksi, "
        SELECT * FROM riwayat_tanah 
        WHERE (nop = ? AND nop != '') OR sppt_id = ? 
        ORDER BY tanggal_mutasi ASC, id ASC
    ");
    mysqli_stmt_bind_param($stmt, "si", $nop, $sppt_id);
    mysqli_stmt_execute($stmt);
    $res_riwayat = mysqli_stmt_get_result($stmt);

    $sisa_riwayat = [];
    while ($r = mysqli_fetch_assoc($res_riwayat)) {
        $sisa_riwayat[] = $r;
    }
    mysqli_stmt_close($stmt);

    // Array penampung saldo riil
    $saldo_pemilik = [];

    if (empty($sisa_riwayat)) {
        // Jika semua transaksi terhapus, kembalikan 100% ke nama SPPT
        $saldo_pemilik[strtoupper(trim($nama_wp_sppt))] = $luas_sppt;
    } else {
        // Pemilik awal pertama kali mendapat luas penuh SPPT
        $pemilik_pertama = strtoupper(trim($sisa_riwayat[0]['dari_pemilik']));
        $saldo_pemilik[$pemilik_pertama] = $luas_sppt;

        // Jalankan mutasi satu per satu
        foreach ($sisa_riwayat as $trx) {
            $dari   = strtoupper(trim($trx['dari_pemilik']));
            $kepada = strtoupper(trim($trx['kepada_pemilik']));
            $luas   = floatval($trx['luas_mutasi']);

            if (!isset($saldo_pemilik[$dari])) {
                $saldo_pemilik[$dari] = 0;
            }
            if (!isset($saldo_pemilik[$kepada])) {
                $saldo_pemilik[$kepada] = 0;
            }

            $saldo_pemilik[$dari]   = max(0.0, $saldo_pemilik[$dari] - $luas);
            $saldo_pemilik[$kepada] = $saldo_pemilik[$kepada] + $luas;
        }
    }

    // 6. Tulis kembali saldo yang sudah akurat ke database
    foreach ($saldo_pemilik as $nama => $luas_akhir) {
        if ($luas_akhir > 0) {
            // Ambil atau daftarkan id pemilik
            $stmt = mysqli_prepare($koneksi, "SELECT id FROM pemilik_tanah WHERE LOWER(TRIM(nama_pemilik)) = LOWER(TRIM(?)) LIMIT 1");
            mysqli_stmt_bind_param($stmt, "s", $nama);
            mysqli_stmt_execute($stmt);
            $p_res = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            if ($p_res) {
                $pemilik_id = intval($p_res['id']);
            } else {
                $stmt = mysqli_prepare($koneksi, "INSERT INTO pemilik_tanah (nama_pemilik) VALUES (?)");
                mysqli_stmt_bind_param($stmt, "s", $nama);
                mysqli_stmt_execute($stmt);
                $pemilik_id = intval(mysqli_insert_id($koneksi));
                mysqli_stmt_close($stmt);
            }

            // Simpan kepemilikan aktif
            $status_aktif = 'AKTIF';
            $stmt = mysqli_prepare($koneksi, "
                INSERT INTO kepemilikan_tanah (sppt_id, nop, pemilik_id, luas_dimiliki, status) 
                VALUES (?, ?, ?, ?, ?)
            ");
            mysqli_stmt_bind_param($stmt, "isids", $sppt_id, $nop, $pemilik_id, $luas_akhir, $status_aktif);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    mysqli_commit($koneksi);

    header("Location: riwayat_tanah.php?id=" . $sppt_id . "&success=" . urlencode("Transaksi berhasil dihapus. Seluruh saldo sisa tanah telah dihitung ulang secara otomatis."));
    exit;

} catch (Throwable $e) {
    mysqli_rollback($koneksi);
    echo "<script>
        alert('Gagal Hapus: " . addslashes($e->getMessage()) . "');
        window.location.href = 'riwayat_tanah.php?id=" . $sppt_id . "';
    </script>";
    exit;
}