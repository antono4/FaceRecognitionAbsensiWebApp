<?php
/**
 * Halaman Absensi (karyawan / kiosk) — publik, tanpa login.
 * Alur:
 *   pilih mode (Otomatis / Masuk / Pulang) -> kamera -> face-api.js
 *   -> descriptor -> cocokkan dgn api/get_faces.php
 *   -> distance < 0.6 => kenal -> snapshot -> POST api/absen.php.
 */
session_start();
$page_title  = 'Absensi Face Recognition';
$active_menu = 'absen';
$minimal     = true;                 // layout tanpa sidebar (mode kiosk)
require __DIR__ . '/includes/header.php';
?>

<style>
/* Gaya khusus halaman kiosk */
.kiosk-frame { position: relative; display: inline-block; }
.kiosk-frame video { border-radius: .5rem; background: #212529; }
.kiosk-frame canvas { position: absolute; inset: 0; }
.mode-btn-group .btn { min-width: 130px; }
#log-aktivitas { max-height: 260px; overflow-y: auto; }
.spinner-overlay {
  position: absolute; inset: 0; display: flex; flex-direction: column;
  align-items: center; justify-content: center; gap: .75rem;
  background: rgba(33,37,41,.85); border-radius: .5rem; color: #fff; z-index: 5;
}
</style>

<div class="row justify-content-center g-4">
  <div class="col-lg-8">
    <div class="card card-outline card-primary">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="bi bi-person-badge-fill"></i> Pindai Wajah Anda</h3>
        <span class="badge text-bg-dark" id="jam-live"></span>
      </div>
      <div class="card-body text-center">

        <!-- Pilihan mode absen -->
        <div class="btn-group mode-btn-group mb-3" role="group" aria-label="Mode absen">
          <input type="radio" class="btn-check" name="mode" id="mode-auto" value="auto" checked>
          <label class="btn btn-outline-secondary" for="mode-auto"><i class="bi bi-magic"></i> Otomatis</label>
          <input type="radio" class="btn-check" name="mode" id="mode-masuk" value="masuk">
          <label class="btn btn-outline-success" for="mode-masuk"><i class="bi bi-box-arrow-in-right"></i> Absen Masuk</label>
          <input type="radio" class="btn-check" name="mode" id="mode-pulang" value="pulang">
          <label class="btn btn-outline-warning" for="mode-pulang"><i class="bi bi-box-arrow-right"></i> Absen Pulang</label>
        </div>

        <!-- Webcam + overlay kotak deteksi + spinner saat model dimuat -->
        <div class="kiosk-frame">
          <video id="webcam" width="640" height="360" autoplay muted playsinline></video>
          <canvas id="overlay"></canvas>
          <div class="spinner-overlay" id="loading">
            <div class="spinner-border" role="status"></div>
            <div id="loading-text">Memuat model pengenalan wajah...</div>
          </div>
        </div>

        <div class="mt-3">
          <div id="status" class="alert alert-info mb-2">Menyiapkan sistem...</div>
          <button class="btn btn-primary btn-lg" id="btn-start" disabled>
            <i class="bi bi-play-circle"></i> Mulai Absen
          </button>
        </div>

      </div>
    </div>

    <!-- Hasil absensi terakhir -->
    <div class="card card-outline card-success d-none" id="card-hasil">
      <div class="card-body d-flex align-items-center gap-3">
        <img id="hasil-foto" class="rounded border" width="160" alt="Foto bukti">
        <div>
          <h4 class="mb-1" id="hasil-nama"></h4>
          <p class="mb-0" id="hasil-info"></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Panel samping: log aktivitas sesi ini -->
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header"><h3 class="card-title"><i class="bi bi-clock-history"></i> Aktivitas Terakhir</h3></div>
      <div class="card-body p-0">
        <ul class="list-group list-group-flush" id="log-aktivitas">
          <li class="list-group-item text-muted" id="log-kosong">Belum ada aktivitas.</li>
        </ul>
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
//  2. PERFORMA (agar tidak lambat):
//       - TinyFaceDetectorOptions({ inputSize: 224 }): gambar diperkecil
//         sebelum diproses -> jauh lebih cepat dari default 416.
//       - Loop 800ms: cukup responsif namun ringan CPU.
//       - Deteksi dihentikan sementara saat tab tidak terlihat
//         (document.hidden) dan saat request absensi sedang diproses.
//
//  3. DATABASE WAJAH: api/get_faces.php -> LabeledFaceDescriptors,
//     FaceMatcher memakai jarak Euclidean; distance < 0.6 = dikenali.
//
//  4. Bila cocok: snapshot frame -> POST api/absen.php dengan mode
//     yang dipilih (otomatis/masuk/pulang). Cooldown per karyawan
//     mencegah request berulang.
// --------------------------------------------------------------------
$page_scripts   = ['https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js'];
$page_inline_js = <<<'JS'
const MODEL_URL   = './models';   // folder weights face-api.js lokal
const THRESHOLD   = 0.6;          // ambang baku kecocokan (distance)
const COOLDOWN    = 30000;        // jeda per karyawan (ms) antar request
const INTERVAL_MS = 800;          // interval loop deteksi

// inputSize 224 (bukan default 416) = deteksi jauh lebih cepat
const OPSI_DETEKSI = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 });

const video     = document.getElementById('webcam');
const overlay   = document.getElementById('overlay');
const snapshot  = document.getElementById('snapshot');
const statusEl  = document.getElementById('status');
const btnStart  = document.getElementById('btn-start');
const cardHasil = document.getElementById('card-hasil');
const loadingEl = document.getElementById('loading');

let matcher   = null;           // FaceMatcher berisi wajah seluruh karyawan
let loopAktif = false;
let memproses = false;          // sedang kirim absensi -> skip frame
const cooldown = new Map();     // user_id -> timestamp kirim terakhir
const idCache  = new Map();     // label(nama) -> user_id

const modeDipilih = () => document.querySelector('input[name="mode"]:checked').value;

// ---- Jam digital di header card ------------------------------------
setInterval(() => {
  document.getElementById('jam-live').textContent =
    new Date().toLocaleString('id-ID', { dateStyle: 'full', timeStyle: 'medium' });
}, 1000);

// ---- 1) Muat model AI ----------------------------------------------
async function muatModel() {
  await Promise.all([
    faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
    faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
    faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
  ]);
}

// ---- 2) Muat descriptor karyawan dari API --------------------------
async function muatDescriptor() {
  document.getElementById('loading-text').textContent = 'Memuat data wajah karyawan...';
  const res = await fetch('api/get_faces.php');
  const j   = await res.json();
  const data = j.data ?? [];
  if (!data.length) {
    setStatus('warning', 'Belum ada wajah terdaftar. Admin harus registrasi wajah dulu.');
    return false;
  }
  const labeled = data.map(k => {
    idCache.set(k.nama, k.id);
    return new faceapi.LabeledFaceDescriptors(k.nama, [new Float32Array(k.face_descriptor)]);
  });
  matcher = new faceapi.FaceMatcher(labeled, THRESHOLD);
  return true;
}

function setStatus(kelas, teks) {
  statusEl.className = `alert alert-${kelas} mb-2`;
  statusEl.textContent = teks;
}

// ---- 3) Nyalakan kamera & mulai loop deteksi -----------------------
btnStart.addEventListener('click', async () => {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ video: { width: 640 } });
    video.srcObject = stream;
    loopAktif = true;
    btnStart.disabled = true;
    setStatus('success', 'Kamera aktif. Hadapkan wajah ke kamera.');
    loopDeteksi();
  } catch (e) {
    setStatus('danger', 'Akses kamera ditolak.');
  }
});

async function loopDeteksi() {
  overlay.width  = video.videoWidth  || 640;
  overlay.height = video.videoHeight || 360;
  const ukuran = { width: overlay.width, height: overlay.height };

  setInterval(async () => {
    // Skip saat tab tidak aktif / request sedang berjalan -> hemat CPU
    if (!loopAktif || !matcher || memproses || document.hidden) return;

    const hasil = await faceapi
      .detectAllFaces(video, OPSI_DETEKSI)
      .withFaceLandmarks()
      .withFaceDescriptors();
    if (!hasil) return;

    const digambar = faceapi.resizeResults(hasil, ukuran);
    const ctx = overlay.getContext('2d');
    ctx.clearRect(0, 0, overlay.width, overlay.height);

    for (const deteksi of digambar) {
      const cocok = matcher.findBestMatch(deteksi.descriptor);
      const label = cocok.distance < THRESHOLD ? cocok.label : 'unknown';

      const box   = deteksi.detection.box;
      const warna = label === 'unknown' ? 'red' : 'lime';
      new faceapi.draw.DrawBox(box, { label, boxColor: warna }).draw(overlay);

      if (label !== 'unknown') {
        prosesAbsen(idCache.get(label), label, cocok.distance);
      }
    }
  }, INTERVAL_MS);
}

// ---- 4) Snapshot + kirim ke API ------------------------------------
async function prosesAbsen(userId, nama, distance) {
  const terakhir = cooldown.get(userId) ?? 0;
  if (Date.now() - terakhir < COOLDOWN) return;   // masih jeda
  cooldown.set(userId, Date.now());
  memproses = true;

  const ctx = snapshot.getContext('2d');
  ctx.drawImage(video, 0, 0, snapshot.width, snapshot.height);
  const foto = snapshot.toDataURL('image/jpeg', 0.7);

  const mode = modeDipilih();
  const labelMode = { auto: 'otomatis', masuk: 'masuk', pulang: 'pulang' }[mode];
  setStatus('info', `Mengenali ${nama} (distance ${distance.toFixed(3)}). Mengirim absen ${labelMode}...`);

  try {
    const res = await fetch('api/absen.php', {
      method : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body   : JSON.stringify({ user_id: userId, foto, tipe: mode }),
    });
    const j = await res.json();
    tampilkanHasil(j, foto);
  } catch (e) {
    setStatus('danger', 'Gagal menghubungi server.');
  } finally {
    memproses = false;
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
    setStatus('success', j.message);
    tambahLog(j.data.nama, `Absen ${j.data.tipe} ${j.data.jam}`, true);
  } else {
    document.getElementById('hasil-nama').textContent = 'Perhatian';
    document.getElementById('hasil-info').textContent = j.message;
    setStatus('warning', j.message);
    tambahLog(j.data?.nama ?? '-', j.message, false);
  }
}

function tambahLog(nama, teks, sukses) {
  document.getElementById('log-kosong')?.remove();
  const li = document.createElement('li');
  li.className = 'list-group-item d-flex justify-content-between align-items-start';
  li.innerHTML = `
    <div>
      <strong>${nama}</strong><br>
      <small>${teks}</small>
    </div>
    <span class="badge ${sukses ? 'text-bg-success' : 'text-bg-secondary'}">
      <i class="bi ${sukses ? 'bi-check-circle' : 'bi-exclamation-circle'}"></i>
    </span>`;
  document.getElementById('log-aktivitas').prepend(li);
}

// ---- Boot: model dulu, lalu descriptor ------------------------------
(async () => {
  try {
    await muatModel();
    const siap = await muatDescriptor();
    loadingEl.style.display = 'none';
    if (siap) {
      setStatus('primary', 'Sistem siap. Pilih mode lalu klik "Mulai Absen".');
      btnStart.disabled = false;
    }
  } catch (e) {
    setStatus('danger', 'Gagal memuat model. Pastikan folder ./models tersedia.');
    document.getElementById('loading-text').textContent = 'Gagal memuat model.';
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
