<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/fungsi.php';
require_role(['Admin']);

$pageTitle = 'Tambah User';
$active = 'user';
$error = '';
$roles = ['Admin', 'Petugas', 'Peminjam'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $nama = trim($_POST['nama_lengkap'] ?? '');
    $password = $_POST['password'] ?? '';
    $level = $_POST['level_user'] ?? '';

    if ($username === '' || $nama === '' || $password === '' || !in_array($level, $roles, true)) {
        $error = 'Lengkapi semua field dengan benar.';
    } else {
        $chk = $pdo->prepare('SELECT COUNT(*) FROM tb_user WHERE username = :u');
        $chk->execute([':u' => $username]);
        if ($chk->fetchColumn() > 0) {
            $error = 'Username sudah dipakai.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO tb_user (username, password, nama_lengkap, level_user) VALUES (:u,:p,:n,:l)');
            $stmt->execute([
                ':u' => $username,
                ':p' => password_hash($password, PASSWORD_DEFAULT),
                ':n' => $nama,
                ':l' => $level,
            ]);
            log_aktivitas($pdo, "Menambah user: $username ($level)");
            set_flash('success', 'User berhasil ditambahkan.');
            redirect('user/index.php');
        }
    }
}
require __DIR__ . '/../config/partial_header.php';
?>
<div class="card" style="max-width:480px">
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <label>Username</label>
    <input type="text" name="username" required value="<?= e($_POST['username'] ?? '') ?>">
    <label>Nama Lengkap</label>
    <input type="text" name="nama_lengkap" required value="<?= e($_POST['nama_lengkap'] ?? '') ?>">
    <label>Password</label>
    <input type="password" name="password" required>
    <label>Role</label>
    <select name="level_user" required>
      <option value="">-- Pilih Role --</option>
      <?php foreach ($roles as $r): ?><option value="<?= $r ?>"><?= $r ?></option><?php endforeach; ?>
    </select>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Simpan</button>
      <a href="index.php" class="btn btn-muted">Batal</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../config/partial_footer.php'; ?>
