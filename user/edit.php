<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/fungsi.php';
require_role(['Admin']);

$pageTitle = 'Edit User';
$active = 'user';
$roles = ['Admin', 'Petugas', 'Peminjam'];
$id = (int)($_GET['id'] ?? $_POST['id_user'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM tb_user WHERE id_user = :id');
$stmt->execute([':id' => $id]);
$user = $stmt->fetch();
if (!$user) { set_flash('error', 'User tidak ditemukan.'); redirect('user/index.php'); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $nama = trim($_POST['nama_lengkap'] ?? '');
    $password = $_POST['password'] ?? '';
    $level = $_POST['level_user'] ?? '';

    if ($username === '' || $nama === '' || !in_array($level, $roles, true)) {
        $error = 'Lengkapi semua field dengan benar.';
    } else {
        $chk = $pdo->prepare('SELECT COUNT(*) FROM tb_user WHERE username = :u AND id_user <> :id');
        $chk->execute([':u' => $username, ':id' => $id]);
        if ($chk->fetchColumn() > 0) {
            $error = 'Username sudah dipakai user lain.';
        } else {
            if ($password !== '') {
                $upd = $pdo->prepare('UPDATE tb_user SET username=:u, nama_lengkap=:n, level_user=:l, password=:p WHERE id_user=:id');
                $upd->execute([':u' => $username, ':n' => $nama, ':l' => $level, ':p' => password_hash($password, PASSWORD_DEFAULT), ':id' => $id]);
            } else {
                $upd = $pdo->prepare('UPDATE tb_user SET username=:u, nama_lengkap=:n, level_user=:l WHERE id_user=:id');
                $upd->execute([':u' => $username, ':n' => $nama, ':l' => $level, ':id' => $id]);
            }
            log_aktivitas($pdo, "Mengubah user: $username");
            set_flash('success', 'User berhasil diperbarui.');
            redirect('user/index.php');
        }
    }
    $user = array_merge($user, $_POST);
}
require __DIR__ . '/../config/partial_header.php';
?>
<div class="card" style="max-width:480px">
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="id_user" value="<?= (int)$id ?>">
    <label>Username</label>
    <input type="text" name="username" required value="<?= e($user['username']) ?>">
    <label>Nama Lengkap</label>
    <input type="text" name="nama_lengkap" required value="<?= e($user['nama_lengkap']) ?>">
    <label>Password (kosongkan jika tidak diubah)</label>
    <input type="password" name="password">
    <label>Role</label>
    <select name="level_user" required>
      <?php foreach ($roles as $r): ?><option value="<?= $r ?>" <?= $user['level_user'] === $r ? 'selected' : '' ?>><?= $r ?></option><?php endforeach; ?>
    </select>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
      <a href="index.php" class="btn btn-muted">Batal</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../config/partial_footer.php'; ?>
