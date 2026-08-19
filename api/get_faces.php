<?php
/**
 * API: Daftar wajah untuk pencocokan di sisi klien (face-api.js).
 * Endpoint publik — halaman absensi (kiosk) tidak butuh login.
 * Mengembalikan descriptor sebagai array JSON siap pakai.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'message' => 'Hanya GET.'], 405);
}

$pdo  = Database::connect();
$stmt = $pdo->query(
    'SELECT id, nama, face_descriptor
     FROM users
     WHERE face_descriptor IS NOT NULL AND face_descriptor <> ""'
);

$data = [];
foreach ($stmt->fetchAll() as $row) {
    $descriptor = json_decode($row['face_descriptor'], true);
    if (!is_array($descriptor)) {
        continue; // abaikan data korup agar face-api.js tidak gagal
    }
    $data[] = [
        'id'              => (int)$row['id'],
        'nama'            => $row['nama'],
        'face_descriptor' => $descriptor, // array 128 float
    ];
}

json_response(['success' => true, 'count' => count($data), 'data' => $data]);
