<?php
/**
 * API: Autentikasi
 *  - POST {action: "login",  username, password}  -> buat session
 *  - POST {action: "logout"}                      -> hapus session
 *  - GET                                          -> info session aktif
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

session_start();
$pdo     = Database::connect();
$method  = $_SERVER['REQUEST_METHOD'];
$body    = get_json_body();
$action  = $body['action'] ?? ($_GET['action'] ?? '');

if ($method === 'GET') {
    // Cek status session — dipakai frontend untuk guard halaman.
    $user = $_SESSION['user'] ?? null;
    json_response(['success' => true, 'data' => $user]);
}

if ($method === 'POST' && $action === 'login') {
    $username = trim($body['username'] ?? '');
    $password = $body['password'] ?? '';
    if ($username === '' || $password === '') {
        json_response(['success' => false, 'message' => 'Username dan password wajib diisi.'], 422);
    }

    $stmt = $pdo->prepare('SELECT id, nama, username, password, role FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        json_response(['success' => false, 'message' => 'Username atau password salah.'], 401);
    }

    $safeUser = [
        'id'       => (int)$user['id'],
        'nama'     => $user['nama'],
        'username' => $user['username'],
        'role'     => $user['role'],
    ];
    session_regenerate_id(true);
    $_SESSION['user'] = $safeUser;

    json_response(['success' => true, 'message' => 'Login berhasil.', 'data' => $safeUser]);
}

if ($method === 'POST' && $action === 'logout') {
    $_SESSION = [];
    session_destroy();
    json_response(['success' => true, 'message' => 'Logout berhasil.']);
}

json_response(['success' => false, 'message' => 'Metode/aksi tidak dikenal.'], 405);
