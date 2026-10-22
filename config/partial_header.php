<?php

$role = current_role();
$menus = [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => base_url('dashboard.php'), 'roles' => ['Admin', 'Petugas', 'Peminjam']],
    ['key' => 'alat',      'label' => 'Data Alat',  'href' => base_url('alat/index.php'), 'roles' => ['Admin', 'Petugas', 'Peminjam']],
    ['key' => 'kategori',  'label' => 'Kategori',   'href' => base_url('kategori/index.php'), 'roles' => ['Admin']],
    ['key' => 'user',      'label' => 'Data User',  'href' => base_url('user/index.php'), 'roles' => ['Admin']],
    ['key' => 'peminjaman','label' => 'Peminjaman', 'href' => base_url('peminjaman/index.php'), 'roles' => ['Petugas', 'Peminjam']],
    ['key' => 'pengembalian','label' => 'Pengembalian', 'href' => base_url('pengembalian/index.php'), 'roles' => ['Petugas', 'Peminjam']],
    ['key' => 'laporan',   'label' => 'Cetak Laporan', 'href' => base_url('laporan/index.php'), 'roles' => ['Admin']],
    ['key' => 'log',       'label' => 'Log Aktifitas', 'href' => base_url('log_aktivitas/index.php'), 'roles' => ['Admin']],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Peminjaman Alat') ?></title>
<link rel="stylesheet" href="<?= base_url('assets/style.css') ?>?v=<?= @filemtime(__DIR__ . '/../assets/style.css') ?: time() ?>">
</head>
<body>
<header class="topnav" id="topnav">
  <div class="topnav-inner">
    <a href="<?= base_url('dashboard.php') ?>" class="brand">Peminjaman <span>Alat</span></a>
    <button type="button" class="nav-toggle" aria-label="Menu" onclick="document.getElementById('topnav').classList.toggle('open')">&#9776;</button>
    <nav class="nav-menu">
      <?php foreach ($menus as $m): ?>
        <?php if (in_array($role, $m['roles'], true)): ?>
          <a href="<?= $m['href'] ?>" class="<?= (($active ?? '') === $m['key']) ? 'active' : '' ?>"><?= e($m['label']) ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>
    <div class="nav-user">
      <div class="user-chip">
        <strong><?= e($_SESSION['nama_lengkap'] ?? '') ?></strong>
        <small><?= e($role) ?></small>
      </div>
      <a href="<?= base_url('logout.php') ?>" class="logout">Logout</a>
    </div>
  </div>
</header>
<main class="main">
    <div class="topbar">
      <h1><?= e($pageTitle ?? '') ?></h1>
    </div>
    <?php show_flash(); ?>
