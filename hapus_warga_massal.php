<?php
include 'koneksi.php';

/* =========================================
   HAPUS DATA WARGA MASSAL
========================================= */

if(isset($_POST['hapus_massal'])){

    if(isset($_POST['pilih']) && count($_POST['pilih']) > 0){

        $berhasil = 0;
        $gagal = 0;

        foreach($_POST['pilih'] as $id){

            $id = (int)$id;

            // Cek apakah warga sudah memiliki transaksi pembayaran
            $cek = mysqli_query($koneksi,"
                SELECT COUNT(*) AS jml
                FROM pembayaran
                WHERE warga_id='$id'
            ");

            $data = mysqli_fetch_assoc($cek);

            if($data['jml'] > 0){

                $gagal++;

            }else{

                if(mysqli_query($koneksi,"
                    DELETE FROM warga
                    WHERE id='$id'
                ")){
                    $berhasil++;
                }

            }

        }

        if($gagal > 0){

            header("Location: data_warga.php?type=warning&pesan=$berhasil data berhasil dihapus, $gagal data tidak dapat dihapus karena sudah memiliki transaksi.");

        }else{

            header("Location: data_warga.php?type=success&pesan=$berhasil data warga berhasil dihapus.");

        }

        exit;

    }else{

        header("Location: data_warga.php?type=warning&pesan=Silakan pilih data yang akan dihapus.");
        exit;

    }

}

header("Location: data_warga.php");
exit;