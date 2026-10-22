<?php
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/fungsi.php';

if (!empty($_SESSION['id_user'])) {
    log_aktivitas($pdo, 'Logout dari sistem');
}
$_SESSION = [];
session_destroy();
redirect('login.php');
