<?php
/**
 * CRUD Data Karyawan — tabel AdminLTE 4 + modal Bootstrap 5.
 * Semua operasi lewat Fetch API -> api/karyawan.php.
 */
require_once __DIR__ . '/includes/auth_check.php';

$page_title  = 'Data Karyawan';
$active_menu = 'karyawan';
require __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h3 class="card-title mb-0"><i class="bi bi-people-fill"></i> Daftar Karyawan</h3>
    <div class="d-flex gap-2">
      <input type="search" class="form-control form-control-sm" id="cari" placeholder="Cari nama / username..." style="width:220px">
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-form" onclick="bukaTambah()">
        <i class="bi bi-plus-lg"></i> Tambah Karyawan
      </button>
    </div>
  </div>
  <div class="card-body p-0 table-responsive">
    <table class="table table-striped table-hover mb-0">
      <thead>
        <tr>
          <th>#</th>
          <th>Nama</th>
          <th>Username</th>
          <th>Role</th>
          <th>Wajah</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody id="tbl-karyawan"></tbody>
    </table>
  </div>
</div>

<!-- Modal tambah / edit karyawan -->
<div class="modal fade" id="modal-form" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" id="form-karyawan">
      <div class="modal-header">
        <h5 class="modal-title" id="modal-judul">Tambah Karyawan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="f-id">
        <div class="mb-3">
          <label class="form-label">Nama</label>
          <input type="text" class="form-control" id="f-nama" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" class="form-control" id="f-username" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Role</label>
          <select class="form-select" id="f-role">
            <option value="karyawan">karyawan</option>
            <option value="admin">admin</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Password <small class="text-muted">(kosongkan saat edit bila tidak diubah)</small></label>
          <input type="password" class="form-control" id="f-password">
        </div>
        <div class="alert alert-danger d-none" id="modal-alert"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<?php
$page_inline_js = <<<'JS'
const tbody      = document.getElementById('tbl-karyawan');
const modal      = new bootstrap.Modal(document.getElementById('modal-form'));
const modalAlert = document.getElementById('modal-alert');
let daftar       = [];

function renderTabel(list) {
  tbody.innerHTML = list.length ? list.map((k, i) => `
    <tr>
      <td>${i + 1}</td>
      <td>${k.nama}</td>
      <td>${k.username}</td>
      <td><span class="badge ${k.role === 'admin' ? 'text-bg-primary' : 'text-bg-secondary'}">${k.role}</span></td>
      <td>${k.has_face
        ? '<span class="badge text-bg-success">Terdaftar</span>'
        : '<span class="badge text-bg-danger">Belum</span>'}</td>
      <td class="text-nowrap">
        <button class="btn btn-warning btn-sm" onclick="bukaEdit(${k.id})"><i class="bi bi-pencil"></i></button>
        <button class="btn btn-danger btn-sm" onclick="hapus(${k.id})"><i class="bi bi-trash"></i></button>
        <a class="btn btn-info btn-sm" href="registrasi_wajah.php?id=${k.id}" title="Registrasi wajah"><i class="bi bi-camera"></i></a>
      </td>
    </tr>`).join('') : '<tr><td colspan="6" class="text-center text-muted py-3">Belum ada karyawan.</td></tr>';
}

async function muatTabel() {
  const res = await fetch('api/karyawan.php');
  const j   = await res.json();
  daftar    = j.data ?? [];
  renderTabel(daftar);
}

// Pencarian client-side (tidak membebani server)
document.getElementById('cari').addEventListener('input', e => {
  const q = e.target.value.toLowerCase();
  renderTabel(daftar.filter(k =>
    k.nama.toLowerCase().includes(q) || k.username.toLowerCase().includes(q)
  ));
});

function bukaTambah() { resetForm('Tambah Karyawan'); }

function bukaEdit(id) {
  const k = daftar.find(x => x.id === id);
  if (!k) return;
  resetForm('Edit Karyawan');
  document.getElementById('f-id').value       = k.id;
  document.getElementById('f-nama').value     = k.nama;
  document.getElementById('f-username').value = k.username;
  document.getElementById('f-role').value     = k.role;
  modal.show();
}

function resetForm(judul) {
  modalAlert.classList.add('d-none');
  document.getElementById('modal-judul').textContent = judul;
  document.getElementById('form-karyawan').reset();
  document.getElementById('f-id').value = '';
}

document.getElementById('form-karyawan').addEventListener('submit', async e => {
  e.preventDefault();
  modalAlert.classList.add('d-none');
  const id  = document.getElementById('f-id').value;
  const payload = {
    id      : id || undefined,
    nama    : document.getElementById('f-nama').value,
    username: document.getElementById('f-username').value,
    role    : document.getElementById('f-role').value,
    password: document.getElementById('f-password').value || undefined,
  };
  const res  = await fetch('api/karyawan.php', {
    method : id ? 'PUT' : 'POST',
    headers: { 'Content-Type': 'application/json' },
    body   : JSON.stringify(payload),
  });
  const j = await res.json();
  if (j.success) {
    modal.hide();
    muatTabel();
  } else {
    modalAlert.textContent = j.message || 'Gagal menyimpan.';
    modalAlert.classList.remove('d-none');
  }
});

async function hapus(id) {
  if (!confirm('Hapus karyawan ini?')) return;
  const res = await fetch('api/karyawan.php?id=' + id, { method: 'DELETE' });
  const j   = await res.json();
  if (!j.success) alert(j.message || 'Gagal menghapus.');
  muatTabel();
}

muatTabel();
JS;
require __DIR__ . '/includes/footer.php';
?>
