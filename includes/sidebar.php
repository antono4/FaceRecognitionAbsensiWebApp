<?php
/** Sidebar AdminLTE 4. Menu aktif ditandai lewat $active_menu. */
function menu_active(string $key): string { global $active_menu; return $active_menu === $key ? ' active' : ''; }
?>
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">

  <div class="sidebar-brand">
    <a href="index.php" class="brand-link">
      <img src="assets/img/logo.svg" alt="Logo" class="brand-image opacity-75 shadow">
      <span class="brand-text fw-light">Absensi Online</span>
    </a>
  </div>

  <div class="sidebar-wrapper">
    <nav class="mt-2">
      <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="navigation" aria-label="Navigasi utama">

        <li class="nav-item">
          <a href="index.php" class="nav-link<?= menu_active('dashboard') ?>">
            <i class="nav-icon bi bi-speedometer"></i>
            <p>Dashboard</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="karyawan.php" class="nav-link<?= menu_active('karyawan') ?>">
            <i class="nav-icon bi bi-people-fill"></i>
            <p>Data Karyawan</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="registrasi_wajah.php" class="nav-link<?= menu_active('wajah') ?>">
            <i class="nav-icon bi bi-camera-fill"></i>
            <p>Registrasi Wajah</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="rekap.php" class="nav-link<?= menu_active('rekap') ?>">
            <i class="nav-icon bi bi-clipboard-data"></i>
            <p>Rekap Absensi</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="absen.php" class="nav-link<?= menu_active('absen') ?>" target="_blank">
            <i class="nav-icon bi bi-person-badge-fill"></i>
            <p>Halaman Absen</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="logout.php" class="nav-link">
            <i class="nav-icon bi bi-box-arrow-right"></i>
            <p>Logout</p>
          </a>
        </li>

      </ul>
    </nav>
  </div>
</aside>
