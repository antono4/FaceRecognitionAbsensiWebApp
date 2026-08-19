<?php
/**
 * Konfigurasi global aplikasi Absensi Online.
 * Semua nilai dapat dioverride lewat environment variable
 * (berguna saat deployment Docker / produksi).
 */

// --- Koneksi database -------------------------------------------------
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'db_absensi');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

// --- Pengaturan absensi -----------------------------------------------
// Batas jam masuk; lewat dari jam ini status = 'terlambat'
define('JAM_MASUK', getenv('JAM_MASUK') ?: '08:00');

// --- Upload foto bukti ------------------------------------------------
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('UPLOAD_MAX_BYTES', 5 * 1024 * 1024); // 5 MB
