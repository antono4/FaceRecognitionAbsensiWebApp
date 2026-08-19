<?php
/**
 * Dashboard — ringkasan statistik + tabel absensi hari ini.
 * Data diambil dari api/karyawan.php & api/absen.php via Fetch.
 */
require_once __DIR__ . '/includes/auth_check.php';

$page_title  = 'Dashboard';
$active_menu = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<!-- Info boxes -->
<div class="row">
  <div class="col-12 col-sm-6 col-md-3">
    <div class="small-box text-bg-primary">
      <div class="inner">
        <h3 id="stat-karyawan">0</h3>
        <p>Total Karyawan</p>
      </div>
      <div class="icon"><i class="bi bi-people-fill"></i></div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-md-3">
    <div class="small-box text-bg-success">
      <div class="inner">
        <h3 id="stat-hadir">0</h3>
        <p>Hadir Hari Ini</p>
      </div>
      <div class="icon"><i class="bi bi-person-check-fill"></i></div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-md-3">
    <div class="small-box text-bg-warning">
      <div class="inner">
        <h3 id="stat-terlambat">0</h3>
        <p>Terlambat Hari Ini</p>
      </div>
      <div class="icon"><i class="bi bi-alarm-fill"></i></div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-md-3">
    <div class="small-box text-bg-danger">
      <div class="inner">
        <h3 id="stat-tanpa-wajah">0</h3>
        <p>Belum Registrasi Wajah</p>
      </div>
      <div class="icon"><i class="bi bi-person-slash"></i></div>
    </div>
  </div>
</div>

<!-- Tabel absensi hari ini -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Absensi Hari Ini (<span id="tanggal-hari-ini"></span>)</h3>
  </div>
  <div class="card-body p-0">
    <table class="table table-striped mb-0">
      <thead>
        <tr>
          <th>Nama</th>
          <th>Jam Masuk</th>
          <th>Jam Pulang</th>
          <th>Status</th>
          <th>Foto</th>
        </tr>
      </thead>
      <tbody id="tbl-absen"></tbody>
    </table>
  </div>
</div>

<?php
$page_inline_js = <<<'JS'
const hariIni = new Date().toISOString().slice(0, 10);
document.getElementById('tanggal-hari-ini').textContent = hariIni;

async function muatStatistik() {
  const karyawan = (await (await fetch('api/karyawan.php')).json()).data ?? [];
  const absen    = (await (await fetch('api/absen.php?dari=' + hariIni + '&sampai=' + hariIni)).json()).data ?? [];

  document.getElementById('stat-karyawan').textContent   = karyawan.length;
  document.getElementById('stat-tanpa-wajah').textContent =
    karyawan.filter(k => k.role === 'karyawan' && !k.has_face).length;
  document.getElementById('stat-hadir').textContent      = absen.length;
  document.getElementById('stat-terlambat').textContent  =
    absen.filter(a => a.status === 'terlambat').length;

  const tbody = document.getElementById('tbl-absen');
  tbody.innerHTML = absen.length
    ? absen.map(a => `
        <tr>
          <td>${a.nama}</td>
          <td>${a.jam_masuk ?? '-'}</td>
          <td>${a.jam_pulang ?? '-'}</td>
          <td><span class="badge ${a.status === 'terlambat' ? 'text-bg-warning' : 'text-bg-success'}">${a.status}</span></td>
          <td>${a.foto_bukti ? `<a href="uploads/${a.foto_bukti}" target="_blank">Lihat</a>` : '-'}</td>
        </tr>`).join('')
    : '<tr><td colspan="5" class="text-center">Belum ada absensi hari ini.</td></tr>';
}
muatStatistik();
JS;
require __DIR__ . '/includes/footer.php';
?>
