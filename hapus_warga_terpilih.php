<?php
include 'koneksi.php';

if(isset($_POST['pilih'])){

    foreach($_POST['pilih'] as $id){

        $cek = mysqli_num_rows(
            mysqli_query(
                $conn,
                "SELECT id
                 FROM pembayaran
                 WHERE warga_id='$id'"
            )
        );

        if($cek == 0){

            mysqli_query(
                $conn,
                "DELETE FROM warga
                 WHERE id='$id'"
            );

        }

    }

}

header("Location:data_warga.php");
exit;
?>