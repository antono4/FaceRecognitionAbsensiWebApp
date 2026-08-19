<?php
/**
 * Helper bersama untuk seluruh endpoint API.
 */

/** Kirim response JSON dan hentikan eksekusi. */
function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Baca body JSON dari request (POST/PUT dengan fetch). */
function get_json_body(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }
    $body = json_decode($raw, true);
    return is_array($body) ? $body : [];
}

/** Pastikan requester adalah admin yang sudah login. */
function require_admin(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
        json_response(['success' => false, 'message' => 'Akses ditolak. Hanya admin.'], 403);
    }
}

/**
 * Simpan foto bukti dari string data-URL base64 (canvas snapshot).
 * Mengembalikan nama file, atau null bila input kosong.
 */
function save_snapshot(?string $dataUrl): ?string
{
    if (!$dataUrl) {
        return null;
    }
    if (!preg_match('/^data:image\/(jpeg|jpg|png);base64,(.+)$/', $dataUrl, $m)) {
        json_response(['success' => false, 'message' => 'Format foto tidak valid.'], 422);
    }
    $binary = base64_decode($m[2], true);
    if ($binary === false || strlen($binary) > UPLOAD_MAX_BYTES) {
        json_response(['success' => false, 'message' => 'Foto gagal diproses / terlalu besar.'], 422);
    }
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0775, true);
    }
    $ext  = ($m[1] === 'png') ? 'png' : 'jpg';
    $name = 'abs_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    file_put_contents(UPLOAD_DIR . '/' . $name, $binary);
    return $name;
}
