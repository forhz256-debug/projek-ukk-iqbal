<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/fungsi.php';
require_role(['Admin']);

$pageTitle = 'Data Kategori';
$active = 'kategori';

$list = $pdo->query('SELECT * FROM tb_kategori ORDER BY nama_kategori')->fetchAll();
require __DIR__ . '/../config/partial_header.php';
?>
<div class="toolbar">
  <div></div>
  <a href="tambah.php" class="btn btn-primary">+ Tambah Kategori</a>
</div>
<div class="card">
  <table>
    <thead><tr><th>Nama Kategori</th><th>Lokasi</th><th>Penanggung Jawab</th><th>Keterangan</th><th>Aksi</th></tr></thead>
    <tbody>
      <?php if (!$list): ?><tr><td colspan="5">Tidak ada data.</td></tr><?php endif; ?>
      <?php foreach ($list as $k): ?>
        <tr>
          <td><?= e($k['nama_kategori']) ?></td>
          <td><?= e($k['lokasi_penyimpanan']) ?></td>
          <td><?= e($k['penanggung_jawab']) ?></td>
          <td><?= e($k['keterangan']) ?></td>
          <td>
            <a href="edit.php?id=<?= (int)$k['id_kategori'] ?>" class="btn btn-muted btn-sm">Edit</a>
            <form class="inline" method="post" action="hapus.php" onsubmit="return confirm('Hapus kategori ini?');">
              <input type="hidden" name="id_kategori" value="<?= (int)$k['id_kategori'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../config/partial_footer.php'; ?>
