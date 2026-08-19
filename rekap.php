<?php
/**
 * Rekap Absensi — filter tanggal & tabel hasil dari api/absen.php.
 */
require_once __DIR__ . '/includes/auth_check.php';

$page_title  = 'Rekap Absensi';
$active_menu = 'rekap';
require __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Filter Rekap</h3>
  </div>
  <form class="card-body" id="form-filter">
    <div class="row g-3 align-items-end">
      <div class="col-md-4">
        <label class="form-label">Dari Tanggal</label>
        <input type="date" class="form-control" id="f-dari">
      </div>
      <div class="col-md-4">
        <label class="form-label">Sampai Tanggal</label>
        <input type="date" class="form-control" id="f-sampai">
      </div>
      <div class="col-md-4">
        <button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Terapkan</button>
      </div>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-header"><h3 class="card-title">Hasil</h3></div>
  <div class="card-body p-0">
    <table class="table table-striped table-hover mb-0">
      <thead>
        <tr>
          <th>Tanggal</th>
          <th>Nama</th>
          <th>Jam Masuk</th>
          <th>Jam Pulang</th>
          <th>Status</th>
          <th>Foto</th>
        </tr>
      </thead>
      <tbody id="tbl-rekap"></tbody>
    </table>
  </div>
</div>

<?php
$page_inline_js = <<<'JS'
const tbody = document.getElementById('tbl-rekap');

function muat(dari = '', sampai = '') {
  let qs = '';
  if (dari)   qs += '&dari=' + dari;
  if (sampai) qs += '&sampai=' + sampai;
  fetch('api/absen.php?x=1' + qs).then(r => r.json()).then(j => {
    const data = j.data ?? [];
    tbody.innerHTML = data.length ? data.map(a => `
      <tr>
        <td>${a.tanggal}</td>
        <td>${a.nama}</td>
        <td>${a.jam_masuk ?? '-'}</td>
        <td>${a.jam_pulang ?? '-'}</td>
        <td><span class="badge ${a.status === 'terlambat' ? 'text-bg-warning' : 'text-bg-success'}">${a.status}</span></td>
        <td>${a.foto_bukti ? `<a href="uploads/${a.foto_bukti}" target="_blank">Lihat</a>` : '-'}</td>
      </tr>`).join('') : '<tr><td colspan="6" class="text-center">Tidak ada data.</td></tr>';
  });
}

document.getElementById('form-filter').addEventListener('submit', e => {
  e.preventDefault();
  muat(document.getElementById('f-dari').value, document.getElementById('f-sampai').value);
});

muat();
JS;
require __DIR__ . '/includes/footer.php';
?>
