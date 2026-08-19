<?php
/**
 * Rekap Absensi — filter tanggal, ringkasan, tabel + export CSV.
 * Data dari api/absen.php.
 */
require_once __DIR__ . '/includes/auth_check.php';

$page_title  = 'Rekap Absensi';
$active_menu = 'rekap';
require __DIR__ . '/includes/header.php';
?>

<div class="card card-outline card-primary">
  <div class="card-header">
    <h3 class="card-title"><i class="bi bi-funnel"></i> Filter Rekap</h3>
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
        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Tampilkan</button>
        <button class="btn btn-success" type="button" id="btn-csv"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</button>
      </div>
    </div>
  </form>
</div>

<!-- Ringkasan -->
<div class="row" id="ringkasan" style="display:none">
  <div class="col-md-4">
    <div class="info-box">
      <span class="info-box-icon text-bg-primary"><i class="bi bi-list-check"></i></span>
      <div class="info-box-content">
        <span class="info-box-text">Total Record</span>
        <span class="info-box-number" id="sum-total">0</span>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="info-box">
      <span class="info-box-icon text-bg-success"><i class="bi bi-check-circle"></i></span>
      <div class="info-box-content">
        <span class="info-box-text">Hadir</span>
        <span class="info-box-number" id="sum-hadir">0</span>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="info-box">
      <span class="info-box-icon text-bg-warning"><i class="bi bi-alarm"></i></span>
      <div class="info-box-content">
        <span class="info-box-text">Terlambat</span>
        <span class="info-box-number" id="sum-terlambat">0</span>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header"><h3 class="card-title"><i class="bi bi-clipboard-data"></i> Hasil</h3></div>
  <div class="card-body p-0 table-responsive">
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
let dataRekap = [];

function muat(dari = '', sampai = '') {
  let qs = '';
  if (dari)   qs += '&dari=' + dari;
  if (sampai) qs += '&sampai=' + sampai;
  fetch('api/absen.php?x=1' + qs).then(r => r.json()).then(j => {
    dataRekap = j.data ?? [];
    render();
  });
}

function render() {
  const data = dataRekap;
  document.getElementById('ringkasan').style.display = data.length ? '' : 'none';
  document.getElementById('sum-total').textContent     = data.length;
  document.getElementById('sum-hadir').textContent     = data.filter(a => a.status === 'hadir').length;
  document.getElementById('sum-terlambat').textContent = data.filter(a => a.status === 'terlambat').length;

  tbody.innerHTML = data.length ? data.map(a => `
    <tr>
      <td>${a.tanggal}</td>
      <td><i class="bi bi-person-circle me-1"></i>${a.nama}</td>
      <td>${a.jam_masuk ? `<span class="badge text-bg-success">${a.jam_masuk}</span>` : '-'}</td>
      <td>${a.jam_pulang ? `<span class="badge text-bg-info">${a.jam_pulang}</span>` : '<span class="text-muted">-</span>'}</td>
      <td><span class="badge ${a.status === 'terlambat' ? 'text-bg-warning' : 'text-bg-success'}">${a.status}</span></td>
      <td>${a.foto_bukti ? `<a class="btn btn-sm btn-outline-secondary" href="uploads/${a.foto_bukti}" target="_blank"><i class="bi bi-image"></i></a>` : '-'}</td>
    </tr>`).join('') : '<tr><td colspan="6" class="text-center text-muted py-3">Tidak ada data.</td></tr>';
}

document.getElementById('form-filter').addEventListener('submit', e => {
  e.preventDefault();
  muat(document.getElementById('f-dari').value, document.getElementById('f-sampai').value);
});

// Export CSV hasil yang sedang tampil
document.getElementById('btn-csv').addEventListener('click', () => {
  if (!dataRekap.length) { alert('Tidak ada data untuk diekspor.'); return; }
  const baris = [
    ['Tanggal','Nama','Jam Masuk','Jam Pulang','Status'],
    ...dataRekap.map(a => [a.tanggal, a.nama, a.jam_masuk ?? '', a.jam_pulang ?? '', a.status]),
  ];
  const csv  = baris.map(b => b.map(v => `"${String(v).replaceAll('"','""')}"`).join(',')).join('\n');
  const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8' });
  const a    = document.createElement('a');
  a.href     = URL.createObjectURL(blob);
  a.download = `rekap-absensi-${new Date().toISOString().slice(0,10)}.csv`;
  a.click();
  URL.revokeObjectURL(a.href);
});

muat();
JS;
require __DIR__ . '/includes/footer.php';
?>
