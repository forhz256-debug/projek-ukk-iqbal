<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/fungsi.php';
require_role(['Admin', 'Petugas', 'Peminjam']); 

$pageTitle = 'Data Alat';
$active = 'alat';
$isAdmin = current_role() === 'Admin';

$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = '';
$params = [];
if ($q !== '') {
    $where = 'WHERE a.nama_alat LIKE :q OR k.nama_kategori LIKE :q';
    $params[':q'] = "%$q%";
}


$countStmt = $pdo->prepare("SELECT COUNT(*) FROM tb_alat a JOIN tb_kategori k ON k.id_kategori = a.id_kategori $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$sql = "SELECT a.*, k.nama_kategori
        FROM tb_alat a JOIN tb_kategori k ON k.id_kategori = a.id_kategori
        $where ORDER BY a.nama_alat LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k2 => $v) $stmt->bindValue($k2, $v);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$alatList = $stmt->fetchAll();

require __DIR__ . '/../config/partial_header.php';
?>
<div class="toolbar">
  <form method="get" class="search-box">
    <input type="text" name="q" placeholder="Cari alat / kategori..." value="<?= e($q) ?>">
  </form>
  <?php if ($isAdmin): ?>
    <a href="tambah.php" class="btn btn-primary">+ Tambah Alat</a>
  <?php endif; ?>
</div>

<div class="card">
  <table>
    <thead>
      <tr><th>Nama Alat</th><th>Kategori</th><th>Stok</th><th>Kondisi</th><?php if ($isAdmin): ?><th>Aksi</th><?php endif; ?></tr>
    </thead>
    <tbody>
      <?php if (!$alatList): ?>
        <tr><td colspan="5">Tidak ada data.</td></tr>
      <?php endif; ?>
      <?php foreach ($alatList as $a): ?>
        <tr>
          <td><?= e($a['nama_alat']) ?></td>
          <td><?= e($a['nama_kategori']) ?></td>
          <td><?= (int)$a['jumlah_stok'] ?></td>
          <td><?= e($a['kondisi']) ?></td>
          <?php if ($isAdmin): ?>
          <td>
            <a href="edit.php?id=<?= (int)$a['id_alat'] ?>" class="btn btn-muted btn-sm">Edit</a>
            <form class="inline" method="post" action="hapus.php" onsubmit="return confirm('Hapus alat ini?');">
              <input type="hidden" name="id_alat" value="<?= (int)$a['id_alat'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
            </form>
          </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php $pages = (int)ceil($total / $perPage); if ($pages > 1): ?>
    <div style="margin-top:14px">
      <?php for ($p = 1; $p <= $pages; $p++): ?>
        <a class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-muted' ?>" href="?page=<?= $p ?>&q=<?= urlencode($q) ?>"><?= $p ?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../config/partial_footer.php'; ?>
