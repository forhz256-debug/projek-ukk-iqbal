<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/fungsi.php';
require_role(['Admin']);

$pageTitle = 'Data User';
$active = 'user';

$list = $pdo->query('SELECT id_user, username, nama_lengkap, level_user FROM tb_user ORDER BY nama_lengkap')->fetchAll();
require __DIR__ . '/../config/partial_header.php';
?>
<div class="toolbar">
  <div></div>
  <a href="tambah.php" class="btn btn-primary">+ Tambah User</a>
</div>
<div class="card">
  <table>
    <thead><tr><th>Username</th><th>Nama Lengkap</th><th>Role</th><th>Aksi</th></tr></thead>
    <tbody>
      <?php foreach ($list as $u): ?>
        <tr>
          <td><?= e($u['username']) ?></td>
          <td><?= e($u['nama_lengkap']) ?></td>
          <td><span class="badge badge-dipinjam"><?= e($u['level_user']) ?></span></td>
          <td>
            <a href="edit.php?id=<?= (int)$u['id_user'] ?>" class="btn btn-muted btn-sm">Edit</a>
            <?php if ((int)$u['id_user'] !== current_user_id()): ?>
            <form class="inline" method="post" action="hapus.php" onsubmit="return confirm('Hapus user ini?');">
              <input type="hidden" name="id_user" value="<?= (int)$u['id_user'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../config/partial_footer.php'; ?>
