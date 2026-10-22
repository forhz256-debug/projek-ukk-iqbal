<?php
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/fungsi.php';

if (!empty($_SESSION['id_user'])) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        
        $stmt = $pdo->prepare('SELECT * FROM tb_user WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        $valid = false;
        if ($user) {
            
            if (password_verify($password, $user['password'])) {
                $valid = true;
            } elseif (hash_equals($user['password'], $password)) {
                $valid = true;
                
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $pdo->prepare('UPDATE tb_user SET password = :p WHERE id_user = :id');
                $upd->execute([':p' => $newHash, ':id' => $user['id_user']]);
            }
        }

        if ($valid) {
            session_regenerate_id(true); // cegah session fixation
            $_SESSION['id_user']      = $user['id_user'];
            $_SESSION['username']     = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['level_user']   = $user['level_user'];
            log_aktivitas($pdo, 'Login ke sistem');
            redirect('dashboard.php');
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - Aplikasi Peminjaman Alat</title>
<link rel="stylesheet" href="assets/style.css?v=<?= @filemtime(__DIR__ . '/assets/style.css') ?: time() ?>">
</head>
<body>
<div class="login-wrap">
  <div class="login-box">
    <h1>Peminjaman Alat</h1>
    <p>Silakan masuk untuk melanjutkan</p>
    <?php show_flash(); ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" autocomplete="off">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" required autofocus>
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary" style="width:100%">Login</button>
      </div>
    </form>
    <p class="alt-link">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
  </div>
</div>
</body>
</html>
