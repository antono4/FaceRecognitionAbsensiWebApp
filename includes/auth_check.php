<?php
/**
 * Guard halaman admin: paksa login dan (opsional) role admin.
 * Disertakan sebelum render halaman.
 */
session_start();

$require_role = $require_role ?? 'admin';

if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
if ($require_role === 'admin' && $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak. Halaman ini khusus admin.');
}
