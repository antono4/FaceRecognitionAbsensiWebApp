# Catatan Repository (AGENTS.md)

Proyek: **Absensi Online Face Recognition** — PHP Native + PDO, MySQL/MariaDB, AdminLTE 4,
face-api.js client-side.

## Arsitektur

- Backend murni API JSON di folder `api/` (auth, karyawan, get_faces, absen).
- Frontend halaman PHP memakai layout AdminLTE 4 di `includes/` + Fetch API.
- Descriptor wajah (128 float) disimpan sebagai JSON string di `users.face_descriptor`.
- Model weights face-api.js ada lokal di `models/` (jangan dihapus).
- Foto bukti absensi di `uploads/` (jangan di-commit; ada .gitignore).

## Konvensi

- Response API selalu `{success, message, data}` via `json_response()` di `includes/helpers.php`.
- Endpoint admin wajib memanggil `require_admin()`; halaman admin wajib `auth_check.php`.
- Ambang kecocokan wajah: distance < 0.6 (konstanta `THRESHOLD` di `absen.php`).
- Batas jam masuk: konstanta `JAM_MASUK` di `config/config.php` (env-override).

## Uji Cepat (Docker)

```bash
docker compose up -d
docker compose exec -T db mysql -uroot -proot db_absensi < database/schema.sql
# http://localhost:8080  (login: admin / admin123)
```
