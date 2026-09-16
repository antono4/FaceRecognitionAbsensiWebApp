<!-- README ini dihasilkan otomatis oleh .github/workflows/generate-readme.yml -->
<!-- Jangan edit manual: perubahan akan ditimpa pada run berikutnya. -->

<h1 align="center">FaceRecognitionAbsensiWebApp 👋</h1>

<p align="center">
  <a href="https://github.com/antono4/FaceRecognitionAbsensiWebApp"><img alt="GitHub repo" src="https://img.shields.io/badge/GitHub-antono4/FaceRecognitionAbsensiWebApp-blue?logo=github"></a>
  <img alt="Files" src="https://img.shields.io/badge/Files-32-informational">
  <img alt="Last commit" src="https://img.shields.io/github/last-commit/antono4/FaceRecognitionAbsensiWebApp">
</p>

---

## 📖 Tentang

Repository **`FaceRecognitionAbsensiWebApp`** adalah proyek PHP yang dibangun dengan PHP.
GitHub Pages belum diaktifkan untuk repository ini.

## 🗂️ Struktur Proyek

```
FaceRecognitionAbsensiWebApp/
.github/
  workflows/
.gitignore
AGENTS.md
absen.php
api/
  absen.php
  auth.php
  get_faces.php
  karyawan.php
assets/
  img/
config/
  config.php
  database.php
database/
  schema.sql
docker-compose.yml
includes/
  auth_check.php
  footer.php
  header.php
  helpers.php
  sidebar.php
index.php
karyawan.php
login.php
logout.php
models/
  face_landmark_68_model-shard1
  face_landmark_68_model-weights_manifest.json
  face_recognition_model-shard1
  face_recognition_model-shard2
  face_recognition_model-weights_manifest.json
  tiny_face_detector_model-shard1
  tiny_face_detector_model-weights_manifest.json
registrasi_wajah.php
rekap.php
uploads/
  .gitkeep
```

## 🛠️ Teknologi

Berdasarkan ekstensi berkas yang terdeteksi di repository:

- `PHP`

> Total **32 berkas** di repository (di luar `.git`, `node_modules`, `dist`, dan `build`).

## 🚀 Menjalankan Secara Lokal

Butuh PHP dan Composer:

```bash
composer install
php spark serve
# atau
php -S localhost:8000 -t public
```

## 📬 Kontak

- GitHub: [antono4](https://github.com/antono4)

## 📄 Lisensi

Proyek ini dilisensikan di bawah MIT License — lihat berkas [`LICENSE`](./LICENSE).

---

<sub>README ini di-generate otomatis oleh GitHub Actions `.github/workflows/generate-readme.yml`.</sub>
