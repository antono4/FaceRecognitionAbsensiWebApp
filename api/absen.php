<?php
/**
 * API: Absensi (masuk / pulang) + rekap
 *  - POST {user_id, foto}  -> publik, dicatat dari halaman absen
 *  - GET                   -> rekap, khusus admin (filter: user_id, dari, sampai)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo    = Database::connect();
$method = $_SERVER['REQUEST_METHOD'];

// ---------------------------------------------------------------- GET
if ($method === 'GET') {
    require_admin();

    $where  = [];
    $values = [];
    if (!empty($_GET['user_id'])) {
        $where[]  = 'a.user_id = ?';
        $values[] = $_GET['user_id'];
    }
    if (!empty($_GET['dari'])) {
        $where[]  = 'a.tanggal >= ?';
        $values[] = $_GET['dari'];
    }
    if (!empty($_GET['sampai'])) {
        $where[]  = 'a.tanggal <= ?';
        $values[] = $_GET['sampai'];
    }
    $sql = 'SELECT a.*, u.nama, u.username
            FROM absensi a JOIN users u ON u.id = a.user_id';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY a.tanggal DESC, a.jam_masuk DESC LIMIT 500';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
    json_response(['success' => true, 'data' => $stmt->fetchAll()]);
}

// --------------------------------------------------------------- POST
if ($method !== 'POST') {
    json_response(['success' => false, 'message' => 'Metode tidak didukung.'], 405);
}

$body   = get_json_body();
$userId = $body['user_id'] ?? null;
$foto   = $body['foto'] ?? null;

if (!$userId) {
    json_response(['success' => false, 'message' => 'user_id wajib diisi.'], 422);
}

// Validasi karyawan + nama untuk disertakan di response
$cek = $pdo->prepare('SELECT id, nama FROM users WHERE id = ?');
$cek->execute([$userId]);
$user = $cek->fetch();
if (!$user) {
    json_response(['success' => false, 'message' => 'Karyawan tidak ditemukan.'], 404);
}

$tanggal  = date('Y-m-d');
$jamNow   = date('H:i:s');
$jamBatas = JAM_MASUK . ':00';

// Cek absensi hari ini
$cekAbs = $pdo->prepare('SELECT * FROM absensi WHERE user_id = ? AND tanggal = ?');
$cekAbs->execute([$userId, $tanggal]);
$absen = $cekAbs->fetch();

$fotoName = save_snapshot(is_string($foto) ? $foto : null);

if (!$absen) {
    // ------------------------- ABSEN MASUK -------------------------
    $status = ($jamNow > $jamBatas) ? 'terlambat' : 'hadir';
    $stmt = $pdo->prepare(
        'INSERT INTO absensi (user_id, tanggal, jam_masuk, status, foto_bukti)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $tanggal, $jamNow, $status, $fotoName]);

    json_response([
        'success' => true,
        'message' => 'Absen masuk tercatat.',
        'data'    => [
            'tipe'   => 'masuk',
            'nama'   => $user['nama'],
            'jam'    => $jamNow,
            'status' => $status,
        ],
    ]);
}

if (empty($absen['jam_pulang'])) {
    // ------------------------- ABSEN PULANG ------------------------
    $stmt = $pdo->prepare(
        'UPDATE absensi SET jam_pulang = ?, foto_bukti = COALESCE(?, foto_bukti)
         WHERE id = ?'
    );
    $stmt->execute([$jamNow, $fotoName, $absen['id']]);

    json_response([
        'success' => true,
        'message' => 'Absen pulang tercatat.',
        'data'    => [
            'tipe'   => 'pulang',
            'nama'   => $user['nama'],
            'jam'    => $jamNow,
            'status' => $absen['status'],
        ],
    ]);
}

// Sudah absen masuk & pulang hari ini
json_response([
    'success' => false,
    'message' => $user['nama'] . ' sudah absen masuk dan pulang hari ini.',
    'data'    => ['tipe' => 'penuh'],
], 409);
