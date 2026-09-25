<?php

if(session_status() == PHP_SESSION_NONE){
    session_start();
}

/* ===========================
   CEK LOGIN
=========================== */

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: login.php");
    exit;
}

/* ===========================
   AUTO LOGOUT (30 MENIT)
=========================== */

$timeout = 1800;

if (isset($_SESSION['LAST_ACTIVITY'])) {

    if ((time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {

        session_unset();
        session_destroy();

        header("Location: login.php?expired=1");
        exit;
    }
}

$_SESSION['LAST_ACTIVITY'] = time();
?>