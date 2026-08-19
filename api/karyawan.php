<?php
/**
 * API: CRUD Karyawan (khusus admin)
 *  - GET (id?)                      -> daftar / detail karyawan
 *  - POST {nama, username, ...}     -> tambah karyawan
 *  - POST {action:"register_face"}  -> simpan face descriptor
 *  - PUT  {id, ...}                 -> ubah karyawan
 *  - DELETE (id)                    -> hapus karyawan
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

require_admin();

$pdo    = Database::connect();
$method = $_SERVER['REQUEST_METHOD'];
$body   = get_json_body();

// ---------------------------------------------------------------- GET
if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare(
            'SELECT id, nama, username, role, face_descriptor, created_at
             FROM users WHERE id = ?'
        );
        $stmt->execute([$_GET['id']]);
        $row = $stmt->fetch() ?: null;
        if ($row) {
            $row['has_face'] = !empty($row['face_descriptor']);
            unset($row['face_descriptor']);
        }
        json_response(['success' => true, 'data' => $row]);
    }

    $stmt = $pdo->query(
        'SELECT id, nama, username, role,
                (face_descriptor IS NOT NULL AND face_descriptor <> "") AS has_face,
                created_at
         FROM users ORDER BY id ASC'
    );
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        $r['id']       = (int)$r['id'];
        $r['has_face'] = (bool)$r['has_face'];
    }
    json_response(['success' => true, 'data' => $rows]);
}

// ------------------------------------------------------------ POST
if ($method === 'POST') {
    // --- Sub-aksi: registrasi wajah --------------------------------
    if (($body['action'] ?? '') === 'register_face') {
        $userId     = $body['user_id'] ?? null;
        $descriptor = $body['descriptor'] ?? null;

        if (!$userId || !is_array($descriptor)) {
            json_response(['success' => false, 'message' => 'user_id & descriptor wajib diisi.'], 422);
        }
        // Validasi: descriptor wajah face-api.js = array 128 angka float
        if (count($descriptor) !== 128) {
            json_response(['success' => false, 'message' => 'Descriptor wajah harus 128 dimensi.'], 422);
        }
        foreach ($descriptor as $v) {
            if (!is_numeric($v)) {
                json_response(['success' => false, 'message' => 'Descriptor berisi nilai non-numerik.'], 422);
            }
        }

        $stmt = $pdo->prepare('UPDATE users SET face_descriptor = ? WHERE id = ?');
        $stmt->execute([json_encode($descriptor), $userId]);
        if ($stmt->rowCount() === 0) {
            $cek = $pdo->prepare('SELECT 1 FROM users WHERE id = ?');
            $cek->execute([$userId]);
            if (!$cek->fetch()) {
                json_response(['success' => false, 'message' => 'Karyawan tidak ditemukan.'], 404);
            }
        }
        json_response(['success' => true, 'message' => 'Wajah berhasil didaftarkan.']);
    }

    // --- Tambah karyawan baru --------------------------------------
    $nama     = trim($body['nama'] ?? '');
    $username = trim($body['username'] ?? '');
    $password = $body['password'] ?? '';
    $role     = $body['role'] ?? 'karyawan';

    if ($nama === '' || $username === '' || $password === '') {
        json_response(['success' => false, 'message' => 'Nama, username, password wajib diisi.'], 422);
    }
    if (!in_array($role, ['admin', 'karyawan'], true)) {
        json_response(['success' => false, 'message' => 'Role tidak valid.'], 422);
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO users (nama, username, password, role) VALUES (?, ?, ?, ?)');
        $stmt->execute([$nama, $username, password_hash($password, PASSWORD_DEFAULT), $role]);
        json_response(['success' => true, 'message' => 'Karyawan ditambahkan.', 'data' => ['id' => (int)$pdo->lastInsertId()]]);
    } catch (PDOException $e) {
        $msg = str_contains($e->getMessage(), 'Duplicate')
            ? 'Username sudah dipakai.'
            : 'Gagal menyimpan data.';
        json_response(['success' => false, 'message' => $msg], 409);
    }
}

// ------------------------------------------------------------- PUT
if ($method === 'PUT') {
    $id       = $body['id'] ?? null;
    $nama     = trim($body['nama'] ?? '');
    $username = trim($body['username'] ?? '');
    $role     = $body['role'] ?? null;
    $password = $body['password'] ?? null;

    if (!$id) {
        json_response(['success' => false, 'message' => 'ID wajib diisi.'], 422);
    }

    $fields = [];
    $values = [];
    if ($nama !== '')  { $fields[] = 'nama = ?';     $values[] = $nama; }
    if ($username !== '') { $fields[] = 'username = ?'; $values[] = $username; }
    if ($role && in_array($role, ['admin', 'karyawan'], true)) {
        $fields[] = 'role = ?'; $values[] = $role;
    }
    if ($password) {
        $fields[] = 'password = ?';
        $values[] = password_hash($password, PASSWORD_DEFAULT);
    }
    if (!$fields) {
        json_response(['success' => false, 'message' => 'Tidak ada data untuk diubah.'], 422);
    }

    $values[] = $id;
    $stmt = $pdo->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?');
    $stmt->execute($values);
    json_response(['success' => true, 'message' => 'Data karyawan diperbarui.']);
}

// ---------------------------------------------------------- DELETE
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? ($body['id'] ?? null);
    if (!$id) {
        json_response(['success' => false, 'message' => 'ID wajib diisi.'], 422);
    }
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->rowCount() === 0) {
        json_response(['success' => false, 'message' => 'Karyawan tidak ditemukan.'], 404);
    }
    json_response(['success' => true, 'message' => 'Karyawan dihapus.']);
}

json_response(['success' => false, 'message' => 'Metode tidak didukung.'], 405);
