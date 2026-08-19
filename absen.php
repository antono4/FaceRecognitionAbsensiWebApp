<?php
/**
 * Halaman Absensi (karyawan / kiosk) — publik, tanpa login.
 * Alur:
 *   kamera -> face-api.js deteksi wajah -> descriptor terbaru
 *   -> cocokkan dgn descriptor dari api/get_faces.php
 *   -> distance < 0.6 => kenal -> snapshot -> POST api/absen.php.
 */
session_start();
$page_title  = 'Absensi Face Recognition';
$active_menu = 'absen';
$minimal     = true;                 // layout tanpa sidebar (mode kiosk)
require __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Pindai Wajah Anda</h3>
        <span class="badge text-bg-secondary" id="jam-live"></span>
      </div>
      <div class="card-body text-center">

        <!-- Webcam + overlay kotak deteksi -->
        <div class="position-relative d-inline-block">
          <video id="webcam" width="640" height="360" class="rounded bg-dark" autoplay muted playsinline></video>
          <canvas id="overlay" class="position-absolute top-0 start-0"></canvas>
        </div>

        <div class="mt-3">
          <div id="status" class="alert alert-info">Memuat model pengenalan wajah...</div>
          <button class="btn btn-primary" id="btn-start" disabled><i class="bi bi-play-circle"></i> Mulai Absen</button>
        </div>

      </div>
    </div>

    <!-- Hasil absensi terakhir -->
    <div class="card d-none" id="card-hasil">
      <div class="card-body d-flex align-items-center gap-3">
        <img id="hasil-foto" class="rounded border" width="160" alt="Foto bukti">
        <div>
          <h4 class="mb-1" id="hasil-nama"></h4>
          <p class="mb-0" id="hasil-info"></p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Canvas tersembunyi untuk snapshot foto bukti -->
<canvas id="snapshot" width="640" height="360" class="d-none"></canvas>

<?php
// --------------------------------------------------------------------
// INISIALISASI FACE-API.JS (pencocokan wajah real-time)
//
//  1. MODEL (dimuat sekali dari folder lokal ./models):
//       - tinyFaceDetector   : menemukan kotak wajah di frame video
//       - faceLandmark68Net  : 68 titik wajah -> alignment
//       - faceRecognitionNet : mengubah wajah -> Float32Array(128)
//
//  2. DATABASE WAJAH: api/get_faces.php mengembalikan descriptor
//     seluruh karyawan. Setiap nama dibungkus menjadi
//     faceapi.LabeledFaceDescriptors(nama, [descriptor]).
//
//  3. FaceMatcher membandingkan descriptor dari kamera dengan seluruh
//     label memakai jarak Euclidean. Ambang baku (threshold) 0.6:
//       distance < 0.6  -> wajah dikenali
//       distance >= 0.6 -> "unknown"
//
//  4. Bila cocok: snapshot frame video -> POST api/absen.php
//     (server menentukan absen masuk/pulang otomatis). Per karyawan
//     diberi cooldown agar tidak menembak API berulang-ulang.
// --------------------------------------------------------------------
$page_scripts   = ['https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js'];
$page_inline_js = <<<'JS'
const MODEL_URL = './models';   // folder weights face-api.js lokal
const THRESHOLD = 0.6;          // ambang baku kecocokan (distance)
const COOLDOWN  = 30000;        // jeda per karyawan (ms) antar request

const video     = document.getElementById('webcam');
const overlay   = document.getElementById('overlay');
const snapshot  = document.getElementById('snapshot');
const statusEl  = document.getElementById('status');
const btnStart  = document.getElementById('btn-start');
const cardHasil = document.getElementById('card-hasil');

let matcher  = null;            // FaceMatcher berisi wajah seluruh karyawan
let loopAktif = false;
const cooldown = new Map();     // user_id -> timestamp kirim terakhir
const idCache  = new Map();     // label(nama) -> user_id

// ---- Jam digital di header card ------------------------------------
setInterval(() => {
  document.getElementById('jam-live').textContent =
    new Date().toLocaleString('id-ID', { dateStyle: 'full', timeStyle: 'medium' });
}, 1000);

// ---- 1) Muat model AI ----------------------------------------------
async function muatModel() {
  statusEl.className = 'alert alert-info';
  statusEl.textContent = 'Memuat model pengenalan wajah...';
  await Promise.all([
    faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
    faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
    faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
  ]);
}

// ---- 2) Muat descriptor karyawan dari API --------------------------
async function muatDescriptor() {
  const res = await fetch('api/get_faces.php');
  const j   = await res.json();
  const data = j.data ?? [];
  if (!data.length) {
    statusEl.className = 'alert alert-warning';
    statusEl.textContent = 'Belum ada wajah terdaftar. Admin harus registrasi wajah dulu.';
    return false;
  }
  // Bungkus setiap descriptor menjadi LabeledFaceDescriptors
  const labeled = data.map(k => {
    idCache.set(k.nama, k.id);
    return new faceapi.LabeledFaceDescriptors(k.nama, [new Float32Array(k.face_descriptor)]);
  });
  // FaceMatcher: threshold 0.6 = baku industri untuk face-api.js
  matcher = new faceapi.FaceMatcher(labeled, THRESHOLD);
  return true;
}

// ---- 3) Nyalakan kamera & mulai loop deteksi -----------------------
btnStart.addEventListener('click', async () => {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ video: { width: 640 } });
    video.srcObject = stream;
    loopAktif = true;
    btnStart.disabled = true;
    statusEl.className = 'alert alert-success';
    statusEl.textContent = 'Kamera aktif. Hadapkan wajah ke kamera.';
    loopDeteksi();
  } catch (e) {
    statusEl.className = 'alert alert-danger';
    statusEl.textContent = 'Akses kamera ditolak.';
  }
});

async function loopDeteksi() {
  overlay.width  = video.videoWidth  || 640;
  overlay.height = video.videoHeight || 360;
  const ukuran = { width: overlay.width, height: overlay.height };

  setInterval(async () => {
    if (!loopAktif || !matcher) return;

    // Deteksi semua wajah + landmark + descriptor 128 dimensi
    const hasil = await faceapi
      .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions())
      .withFaceLandmarks()
      .withFaceDescriptors();
    if (!hasil) return;

    const digambar = faceapi.resizeResults(hasil, ukuran);
    const ctx = overlay.getContext('2d');
    ctx.clearRect(0, 0, overlay.width, overlay.height);

    for (const deteksi of digambar) {
      // Cocokkan descriptor kamera dengan database wajah
      const cocok = matcher.findBestMatch(deteksi.descriptor);
      const label = cocok.distance < THRESHOLD ? cocok.label : 'unknown';

      const box   = deteksi.detection.box;
      const warna = label === 'unknown' ? 'red' : 'lime';
      new faceapi.draw.DrawBox(box, { label, boxColor: warna }).draw(overlay);

      if (label !== 'unknown') {
        prosesAbsen(idCache.get(label), label, cocok.distance);
      }
    }
  }, 700); // interval 700ms: cukup responsif, ringan di CPU
}

// ---- 4) Snapshot + kirim ke API ------------------------------------
async function prosesAbsen(userId, nama, distance) {
  const terakhir = cooldown.get(userId) ?? 0;
  if (Date.now() - terakhir < COOLDOWN) return;   // masih jeda
  cooldown.set(userId, Date.now());

  // Snapshot frame kamera -> data URL JPEG
  const ctx = snapshot.getContext('2d');
  ctx.drawImage(video, 0, 0, snapshot.width, snapshot.height);
  const foto = snapshot.toDataURL('image/jpeg', 0.7);

  statusEl.className = 'alert alert-info';
  statusEl.textContent = `Mengenali ${nama} (distance ${distance.toFixed(3)}). Mencatat absensi...`;

  try {
    const res = await fetch('api/absen.php', {
      method : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body   : JSON.stringify({ user_id: userId, foto }),
    });
    const j = await res.json();
    tampilkanHasil(j, foto);
  } catch (e) {
    statusEl.className = 'alert alert-danger';
    statusEl.textContent = 'Gagal menghubungi server.';
  }
}

function tampilkanHasil(j, foto) {
  cardHasil.classList.remove('d-none');
  document.getElementById('hasil-foto').src = foto;
  if (j.success) {
    document.getElementById('hasil-nama').textContent = j.data.nama;
    document.getElementById('hasil-info').innerHTML =
      `Absen <b>${j.data.tipe}</b> pukul <b>${j.data.jam}</b> &mdash; ` +
      `<span class="badge ${j.data.status === 'terlambat' ? 'text-bg-warning' : 'text-bg-success'}">${j.data.status}</span>`;
    statusEl.className = 'alert alert-success';
  } else {
    document.getElementById('hasil-nama').textContent = 'Perhatian';
    document.getElementById('hasil-info').textContent = j.message;
    statusEl.className = 'alert alert-warning';
  }
  statusEl.textContent = j.message;
}

// ---- Boot: model dulu, lalu descriptor ------------------------------
(async () => {
  try {
    await muatModel();
    const siap = await muatDescriptor();
    if (siap) {
      statusEl.className = 'alert alert-primary';
      statusEl.textContent = 'Sistem siap. Klik "Mulai Absen".';
      btnStart.disabled = false;
    }
  } catch (e) {
    statusEl.className = 'alert alert-danger';
    statusEl.textContent = 'Gagal memuat model. Pastikan folder ./models tersedia.';
  }
})();

// Tombol fullscreen di navbar minimal
document.getElementById('btn-fullscreen')?.addEventListener('click', () => {
  document.fullscreenElement
    ? document.exitFullscreen()
    : document.documentElement.requestFullscreen();
});
JS;
require __DIR__ . '/includes/footer.php';
?>
