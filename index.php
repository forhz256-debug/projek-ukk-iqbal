<?php
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/auth.php';
header('Location: ' . (!empty($_SESSION['id_user']) ? 'dashboard.php' : 'login.php'));
exit;
