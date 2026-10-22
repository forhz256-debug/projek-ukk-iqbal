<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/fungsi.php';
require_role(['Admin']);

$pageTitle = 'Log Aktifitas';
$active = 'log';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$total = (int)$pdo->query('SELECT COUNT(*) FROM tb_log_aktivitas')->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT l.*, u.nama_lengkap, u.level_user FROM tb_log_aktivitas l
     JOIN tb_user u ON u.id_user = l.id_user
     ORDER BY l.waktu DESC LIMIT :limit OFFSET :offset"
);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

require __DIR__ . '/../config/partial_header.php';
?>
<div class="card">
  <table>
    <thead><tr><th>Waktu</th><th>User</th><th>Role</th><th>Aktifitas</th></tr></thead>
    <tbody>
      <?php if (!$logs): ?><tr><td colspan="4">Belum ada log.</td></tr><?php endif; ?>
      <?php foreach ($logs as $l): ?>
        <tr>
          <td><?= e($l['waktu']) ?></td>
          <td><?= e($l['nama_lengkap']) ?></td>
          <td><?= e($l['level_user']) ?></td>
          <td><?= e($l['aktivitas']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php $pages = (int)ceil($total / $perPage); if ($pages > 1): ?>
    <div style="margin-top:14px">
      <?php for ($p = 1; $p <= $pages; $p++): ?>
        <a class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-muted' ?>" href="?page=<?= $p ?>"><?= $p ?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../config/partial_footer.php'; ?>
