<?php
/**
 * Halaman Login — mengikuti template "login-page" AdminLTE 4.
 * Form dikirim via Fetch API ke api/auth.php.
 */
session_start();
if (!empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login &mdash; Absensi Online</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.4.1/dist/css/adminlte.min.css">
</head>
<body class="login-page bg-body-secondary">

<div class="login-box">
  <div class="login-logo">
    <a href="login.php"><b>Absensi</b> Online</a>
  </div>
  <div class="card">
    <div class="card-body login-card-body">
      <p class="login-box-msg">Masuk ke panel admin</p>

      <div id="alert" class="alert alert-danger d-none" role="alert"></div>

      <form id="form-login" autocomplete="off">
        <div class="input-group mb-3">
          <input type="text" class="form-control" id="username" placeholder="Username" required>
          <div class="input-group-text"><i class="bi bi-person"></i></div>
        </div>
        <div class="input-group mb-3">
          <input type="password" class="form-control" id="password" placeholder="Password" required>
          <div class="input-group-text"><i class="bi bi-lock-fill"></i></div>
        </div>
        <div class="row">
          <div class="col-8">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="showpass">
              <label class="form-check-label" for="showpass">Tampilkan password</label>
            </div>
          </div>
          <div class="col-4">
            <button type="submit" id="btn-login" class="btn btn-primary btn-block">Masuk</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@4.4.1/dist/js/adminlte.min.js"></script>
<script>
const form  = document.getElementById('form-login');
const alert = document.getElementById('alert');
const btn   = document.getElementById('btn-login');

document.getElementById('showpass').addEventListener('change', e => {
  document.getElementById('password').type = e.target.checked ? 'text' : 'password';
});

form.addEventListener('submit', async e => {
  e.preventDefault();
  alert.classList.add('d-none');
  btn.disabled = true;
  try {
    const res = await fetch('api/auth.php', {
      method : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body   : JSON.stringify({
        action  : 'login',
        username: document.getElementById('username').value,
        password: document.getElementById('password').value,
      }),
    });
    const json = await res.json();
    if (json.success) {
      window.location.href = 'index.php';
    } else {
      alert.textContent = json.message || 'Login gagal.';
      alert.classList.remove('d-none');
    }
  } catch (err) {
    alert.textContent = 'Tidak dapat terhubung ke server.';
    alert.classList.remove('d-none');
  } finally {
    btn.disabled = false;
  }
});
</script>

</body>
</html>
