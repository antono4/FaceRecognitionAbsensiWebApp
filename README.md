# Absensi Online dengan Face Recognition

Sistem absensi berbasis web: PHP Native + PDO, MySQL/MariaDB, arsitektur REST API (JSON),
UI AdminLTE 4 (Bootstrap 5), dan pengenalan wajah dengan **face-api.js** yang berjalan
sepenuhnya di browser klien.

## Fitur

- **Admin**: login, dashboard statistik, CRUD karyawan, registrasi wajah, rekap absensi.
- **Karyawan (kiosk)**: halaman absen publik — wajah dipindai kamera, dicocokkan dengan
  database wajah, absen masuk/pulang dicatat otomatis beserta foto bukti.
- Status **hadir/terlambat** otomatis berdasarkan batas `JAM_MASUK` (default `08:00`).
- Anti-duplikasi: 1 karyawan = 1 baris absensi per tanggal; request di-skip bila sudah
  absen penuh.

## Struktur Folder

```
.
├── api/
│   ├── auth.php        # login / logout / info session
│   ├── karyawan.php    # CRUD karyawan + register_face
│   ├── get_faces.php   # list id, nama, face_descriptor (publik)
│   └── absen.php       # catat absen masuk/pulang + rekap
├── config/
│   ├── config.php      # konstanta DB, JAM_MASUK, upload
│   └── database.php    # koneksi PDO (anti SQL Injection)
├── database/schema.sql # skema + akun admin default
├── includes/           # layout AdminLTE 4 (header, sidebar, footer, auth_check, helpers)
├── models/             # weights face-api.js (lokal, offline-friendly)
├── uploads/            # foto bukti absensi
├── login.php  index.php  karyawan.php  registrasi_wajah.php  rekap.php  absen.php
└── docker-compose.yml  # opsional, untuk menjalankan cepat
```

## Instalasi

### Opsi A — Docker (tercepat)

```bash
docker compose up -d
# import schema sekali:
docker compose exec -T db mysql -uroot -proot db_absensi < database/schema.sql
# buka http://localhost:8080
```

### Opsi B — XAMPP / LAMP biasa

1. Salin folder proyek ke `htdocs` (atau root web Anda).
2. Buat database dan import schema:
   ```bash
   mysql -u root < database/schema.sql
   ```
3. Sesuaikan kredensial DB di `config/config.php` (atau via environment variable
   `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).
4. Pastikan folder `uploads/` dapat ditulis web server.
5. Buka `http://localhost/absensi/login.php`.

> Model face-api.js sudah disertakan di folder `models/`, sehingga tidak perlu
> koneksi internet untuk pencocokan wajah (CDN hanya untuk CSS/JS AdminLTE &
> face-api.js itu sendiri).

## Akun Default

| Username | Password  | Role  |
|----------|-----------|-------|
| admin    | admin123  | admin |

**Ganti password segera setelah login pertama.**

## Alur Pemakaian

1. Login admin → **Data Karyawan** → tambah karyawan.
2. **Registrasi Wajah** → pilih karyawan → nyalakan kamera → *Pindai & Simpan Wajah*.
   Descriptor 128 float tersimpan sebagai JSON di `users.face_descriptor`.
3. Buka **Halaman Absen** (publik, cocok dipasang di perangkat kiosk/tablet).
   Wajah dikenali bila *distance* FaceMatcher < `0.6` → snapshot & absen tercatat otomatis.

## REST API

| Method | Endpoint              | Auth  | Keterangan |
|--------|-----------------------|-------|------------|
| POST   | `api/auth.php`        | -     | `{action:"login",username,password}` / `{action:"logout"}` |
| GET    | `api/auth.php`        | -     | info session aktif |
| GET    | `api/karyawan.php`    | admin | daftar / `?id=` detail |
| POST   | `api/karyawan.php`    | admin | tambah, atau `{action:"register_face",user_id,descriptor}` |
| PUT    | `api/karyawan.php`    | admin | ubah data |
| DELETE | `api/karyawan.php?id=`| admin | hapus (absensi ikut terhapus, cascade) |
| GET    | `api/get_faces.php`   | publik| descriptor semua karyawan untuk FaceMatcher |
| POST   | `api/absen.php`       | publik| `{user_id, foto(base64 data-URL)}` — masuk/pulang otomatis |
| GET    | `api/absen.php`       | admin | rekap, filter `user_id`, `dari`, `sampai` |

Semua response berformat JSON: `{success, message, data}`.

## Catatan Keamanan

- Semua query memakai PDO *prepared statement* (anti SQL Injection).
- Password di-hash `password_hash()` (bcrypt).
- Endpoint manajemen dilindungi session role admin; `get_faces.php` sengaja publik
  karena halaman kiosk tidak login — bila dipakai produksi, pertimbangkan token kiosk
  atau pembatasan IP.
- CSRF token belum diterapkan; tambahkan sebelum deployment produksi.
