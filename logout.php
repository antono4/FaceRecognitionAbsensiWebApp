<?php
/** Hapus session lalu kembali ke halaman login. */
session_start();
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
