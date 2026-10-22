<?php
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/fungsi.php';

if (!empty($_SESSION['id_user'])) {
    redirect('dashboard.php');
}

$error = '';
$nama = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama_lengkap'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirm  = $_POST['konfirmasi_password'] ?? '';

    if ($nama === '' || $username === '' || $password === '' || $konfirm === '') {
        $error = 'Semua field wajib diisi.';
    } elseif (mb_strlen($nama) < 3 || mb_strlen($nama) > 100) {
        $error = 'Nama lengkap harus 3-100 karakter.';
    } elseif (!preg_match('/^[A-Za-z0-9_.]{4,30}$/', $username)) {
        $error = 'Username 4-30 karakter, hanya huruf, angka, titik, atau underscore.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirm) {
        $error = 'Konfirmasi password tidak sama.';
    } else {
        $chk = $pdo->prepare('SELECT COUNT(*) FROM tb_user WHERE username = :u');
        $chk->execute([':u' => $username]);
        if ($chk->fetchColumn() > 0) {
            $error = 'Username sudah dipakai, silakan pilih yang lain.';
        } else {
            try {
                // Role SELALU 'Peminjam' -- registrasi publik tidak boleh membuat Admin/Petugas
                $stmt = $pdo->prepare(
                    "INSERT INTO tb_user (username, password, nama_lengkap, level_user) VALUES (:u, :p, :n, 'Peminjam')"
                );
                $stmt->execute([
                    ':u' => $username,
                    ':p' => password_hash($password, PASSWORD_DEFAULT),
                    ':n' => $nama,
                ]);
                $idBaru = (int)$pdo->lastInsertId();

                // log_aktivitas() butuh session, sedangkan user baru belum login -> insert langsung
                $log = $pdo->prepare('INSERT INTO tb_log_aktivitas (id_user, aktivitas, waktu) VALUES (:id, :a, NOW())');
                $log->execute([':id' => $idBaru, ':a' => "Registrasi akun baru: $username (Peminjam)"]);

                set_flash('success', 'Registrasi berhasil. Silakan login dengan akun anda.');
                redirect('login.php');
            } catch (PDOException $ex) {
                // 23000 = duplicate key (username kembar karena race condition)
                $error = ($ex->getCode() === '23000')
                    ? 'Username sudah dipakai, silakan pilih yang lain.'
                    : 'Registrasi gagal, coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Daftar - Aplikasi Peminjaman Alat</title>
<link rel="stylesheet" href="assets/style.css?v=<?= @filemtime(__DIR__ . '/assets/style.css') ?: time() ?>">
</head>
<body>
<div class="login-wrap">
  <div class="login-box">
    <h1>Daftar Akun</h1>
    <p>Buat akun Peminjam untuk meminjam alat</p>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" autocomplete="off">
      <label for="nama_lengkap">Nama Lengkap</label>
      <input type="text" id="nama_lengkap" name="nama_lengkap" required autofocus maxlength="100" value="<?= e($nama) ?>">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" required maxlength="30" value="<?= e($username) ?>">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required minlength="6">
      <label for="konfirmasi_password">Ulangi Password</label>
      <input type="password" id="konfirmasi_password" name="konfirmasi_password" required minlength="6">
      <div class="form-actions">
        <button type="submit" class="btn btn-primary" style="width:100%">Daftar</button>
      </div>
    </form>
    <p class="alt-link">Sudah punya akun? <a href="login.php">Login</a></p>
  </div>
</div>
</body>
</html>
