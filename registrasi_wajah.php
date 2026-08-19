<?php
/**
 * Registrasi Wajah Karyawan (admin).
 * Webcam -> deteksi wajah (face-api.js) -> descriptor 128 float
 * -> POST ke api/karyawan.php (action: register_face).
 */
require_once __DIR__ . '/includes/auth_check.php';

$page_title  = 'Registrasi Wajah';
$active_menu = 'wajah';
require __DIR__ . '/includes/header.php';
?>

<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Pindai Wajah</h3>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label">Pilih Karyawan</label>
          <select class="form-select" id="select-karyawan">
            <option value="">-- memuat... --</option>
          </select>
        </div>

        <!-- Bingkai webcam + overlay canvas + spinner saat model dimuat -->
        <div class="position-relative d-inline-block">
          <video id="webcam" width="640" height="360" class="rounded bg-dark" autoplay muted playsinline></video>
          <canvas id="overlay" class="position-absolute top-0 start-0"></canvas>
          <div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center rounded bg-dark bg-opacity-75 text-white gap-2" id="loading">
            <div class="spinner-border" role="status"></div>
            <div>Memuat model AI...</div>
          </div>
        </div>
      </div>
      <div class="card-footer">
        <button class="btn btn-secondary" id="btn-cam"><i class="bi bi-camera-video"></i> Nyalakan Kamera</button>
        <button class="btn btn-primary" id="btn-capture" disabled><i class="bi bi-person-bounding-box"></i> Pindai &amp; Simpan Wajah</button>
        <span class="ms-2" id="status"></span>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card">
      <div class="card-header"><h3 class="card-title">Petunjuk</h3></div>
      <div class="card-body">
        <ol class="ps-3 mb-0">
          <li>Pilih karyawan.</li>
          <li>Nyalakan kamera dan hadapkan wajah ke kamera.</li>
          <li>Klik <b>Pindai &amp; Simpan Wajah</b> saat kotak hijau mengelilingi wajah.</li>
          <li>Descriptor (128 vektor) tersimpan ke kolom <code>face_descriptor</code>.</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<?php
// --------------------------------------------------------------------
// INISIALISASI FACE-API.JS (registrasi wajah)
//  1. Muat 3 model dari folder lokal ./models :
//       - tinyFaceDetector   : deteksi lokasi wajah (cepat, real-time)
//       - faceLandmark68Net  : 68 titik landmark (mata, hidung, mulut)
//       - faceRecognitionNet : menghasilkan descriptor 128 float
//  2. detectSingleFace(...).withFaceLandmarks().withFaceDescriptor()
//     menghasilkan objek yang memuat Float32Array(128).
//  3. Float32Array diubah ke Array biasa -> JSON.stringify -> API.
// --------------------------------------------------------------------
$page_scripts   = ['https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js'];
$page_inline_js = <<<'JS'
const MODEL_URL = './models';   // folder berisi weights face-api.js (lokal, offline-friendly)
// inputSize 224 (bukan default 416) = deteksi jauh lebih cepat
const OPSI_DETEKSI = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 });

const video     = document.getElementById('webcam');
const overlay   = document.getElementById('overlay');
const statusEl  = document.getElementById('status');
const loadingEl = document.getElementById('loading');
const btnCam    = document.getElementById('btn-cam');
const btnCap    = document.getElementById('btn-capture');
const selectKry = document.getElementById('select-karyawan');
let camAktif    = false;

// --- Muat model face-api.js dari disk lokal -------------------------
async function muatModel() {
  statusEl.textContent = 'Memuat model AI...';
  await Promise.all([
    faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
    faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
    faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
  ]);
  loadingEl.style.display = 'none';
  statusEl.textContent = 'Model siap.';
}

// --- Dropdown karyawan (prefill dari ?id=) ---------------------------
async function muatKaryawan() {
  const res = await fetch('api/karyawan.php');
  const j   = await res.json();
  const idQ = new URLSearchParams(location.search).get('id');
  selectKry.innerHTML = '<option value="">-- pilih karyawan --</option>' +
    (j.data ?? []).map(k =>
      `<option value="${k.id}" ${String(k.id) === idQ ? 'selected' : ''}>${k.nama} (${k.username})</option>`
    ).join('');
}

// --- Nyalakan webcam -------------------------------------------------
btnCam.addEventListener('click', async () => {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ video: { width: 640 } });
    video.srcObject = stream;
    camAktif = true;
    btnCap.disabled = false;
    statusEl.textContent = 'Kamera aktif. Arahkan wajah ke kamera.';
    deteksiRealtime();
  } catch (e) {
    statusEl.textContent = 'Kamera ditolak / tidak tersedia.';
  }
});

// --- Gambar kotak deteksi secara real-time ---------------------------
async function deteksiRealtime() {
  overlay.width  = video.videoWidth  || 640;
  overlay.height = video.videoHeight || 360;
  const ukuran = { width: overlay.width, height: overlay.height };

  setInterval(async () => {
    if (document.hidden) return;        // hemat CPU saat tab tidak aktif
    const hasil = await faceapi
      .detectAllFaces(video, OPSI_DETEKSI)
      .withFaceLandmarks();
    if (!hasil) return;
    const gambar = faceapi.resizeResults(hasil, ukuran);
    const ctx = overlay.getContext('2d');
    ctx.clearRect(0, 0, overlay.width, overlay.height);
    faceapi.draw.drawDetections(overlay, gambar);
    faceapi.draw.drawFaceLandmarks(overlay, gambar);
  }, 500);
}

// --- Ambil 1 descriptor & kirim ke API -------------------------------
btnCap.addEventListener('click', async () => {
  const userId = selectKry.value;
  if (!userId) { statusEl.textContent = 'Pilih karyawan dulu.'; return; }

  statusEl.textContent = 'Memindai wajah...';
  const deteksi = await faceapi
    .detectSingleFace(video, OPSI_DETEKSI)
    .withFaceLandmarks()
    .withFaceDescriptor();              // -> Float32Array(128)

  if (!deteksi) {
    statusEl.textContent = 'Wajah tidak terdeteksi. Coba posisikan ulang.';
    return;
  }

  // Float32Array harus diubah ke Array biasa agar dapat di-JSON-kan
  const descriptor = Array.from(deteksi.descriptor);

  const res = await fetch('api/karyawan.php', {
    method : 'POST',
    headers: { 'Content-Type': 'application/json' },
    body   : JSON.stringify({
      action    : 'register_face',
      user_id   : parseInt(userId),
      descriptor: descriptor,
    }),
  });
  const j = await res.json();
  statusEl.textContent = j.message || (j.success ? 'Tersimpan.' : 'Gagal.');
  statusEl.className   = 'ms-2 ' + (j.success ? 'text-success' : 'text-danger');
});

muatModel();
muatKaryawan();
JS;
require __DIR__ . '/includes/footer.php';
?>
