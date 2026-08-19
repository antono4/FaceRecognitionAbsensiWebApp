<?php
/**
 * Header layout AdminLTE 4.
 * Variabel opsional dari halaman:
 *   $page_title   - judul halaman
 *   $minimal      - true untuk layout tanpa sidebar (halaman absen kiosk)
 *   $active_menu  - penanda menu aktif di sidebar
 */
$page_title  = $page_title  ?? 'Absensi Online';
$minimal     = $minimal     ?? false;
$active_menu = $active_menu ?? '';
$user        = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($page_title) ?> &mdash; Absensi Online</title>

  <!-- Bootstrap 5 + AdminLTE 4 + Bootstrap Icons (CDN) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.4.1/dist/css/adminlte.min.css">
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">

<div class="app-wrapper">

  <!-- ======================= NAVBAR (app-header) ======================= -->
  <nav class="app-header navbar navbar-expand bg-body">
    <div class="container-fluid">

      <ul class="navbar-nav">
        <?php if (!$minimal): ?>
        <li class="nav-item">
          <a class="nav-link" href="#" role="button" data-lte-toggle="sidebar">
            <i class="bi bi-list"></i>
          </a>
        </li>
        <?php endif; ?>
        <li class="nav-item d-none d-md-inline">
          <a href="index.php" class="nav-link">Dashboard</a>
        </li>
      </ul>

      <ul class="navbar-nav ms-auto">
        <?php if ($minimal): ?>
        <li class="nav-item">
          <a class="nav-link" href="#" id="btn-fullscreen" role="button" title="Layar penuh">
            <i class="bi bi-arrows-fullscreen"></i>
          </a>
        </li>
        <?php endif; ?>

        <?php if ($user): ?>
        <li class="nav-item dropdown user-menu">
          <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle"></i>
            <span class="d-none d-md-inline"><?= htmlspecialchars($user['nama']) ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><span class="dropdown-item-text">Role: <?= htmlspecialchars($user['role']) ?></span></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
          </ul>
        </li>
        <?php endif; ?>
      </ul>

    </div>
  </nav>

  <?php if (!$minimal): include __DIR__ . '/sidebar.php'; endif; ?>

  <!-- ======================== MAIN (app-main) ========================== -->
  <main class="app-main">
    <div class="app-content-header">
      <div class="container-fluid">
        <div class="row">
          <div class="col-sm-12">
            <h3 class="mb-0"><?= htmlspecialchars($page_title) ?></h3>
          </div>
        </div>
      </div>
    </div>
    <div class="app-content">
      <div class="container-fluid">
