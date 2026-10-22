<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/fungsi.php';
require_role(['Petugas', 'Peminjam']);

$pageTitle = 'Detail Peminjaman';
$active = 'peminjaman';
$id = (int)($_GET['id'] ?? 0);
$role = current_role();

$stmt = $pdo->prepare('SELECT p.*, u.nama_lengkap, pt.nama_lengkap AS nama_petugas
    FROM tb_peminjaman p
    JOIN tb_user u ON u.id_user = p.id_user
    LEFT JOIN tb_user pt ON pt.id_user = p.id_petugas
    WHERE p.id_peminjaman = :id');
$stmt->execute([':id' => $id]);
$p = $stmt->fetch();

if (!$p || ($role === 'Peminjam' && (int)$p['id_user'] !== current_user_id())) {
    set_flash('error', 'Data tidak ditemukan.');
    redirect('peminjaman/index.php');
}

$items = $pdo->prepare('SELECT d.*, a.nama_alat FROM tb_detail_peminjaman d JOIN tb_alat a ON a.id_alat=d.id_alat WHERE d.id_peminjaman=:id');
$items->execute([':id' => $id]);
$items = $items->fetchAll();

require __DIR__ . '/../config/partial_header.php';
?>
<div class="card" style="max-width:640px">
  <p><strong>Peminjam:</strong> <?= e($p['nama_lengkap']) ?></p>
  <p><strong>Tanggal Pinjam:</strong> <?= e($p['tanggal_pinjam']) ?> &nbsp; <strong>Rencana Kembali:</strong> <?= e($p['tanggal_rencana_kembali']) ?></p>
  <p><strong>Status:</strong> <?= e($p['status_peminjaman']) ?></p>
  <?php if ($p['nama_petugas']): ?><p><strong>Diproses oleh:</strong> <?= e($p['nama_petugas']) ?></p><?php endif; ?>
  <?php if ($p['catatan_petugas']): ?><p><strong>Catatan:</strong> <?= e($p['catatan_petugas']) ?></p><?php endif; ?>

  <table style="margin-top:14px">
    <thead><tr><th>Alat</th><th>Jumlah</th><th>Keperluan</th></tr></thead>
    <tbody>
      <?php foreach ($items as $it): ?>
        <tr><td><?= e($it['nama_alat']) ?></td><td><?= (int)$it['jumlah_pinjam'] ?></td><td><?= e($it['keterangan_keperluan']) ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div class="form-actions"><a href="index.php" class="btn btn-muted">Kembali</a></div>
</div>
<?php require __DIR__ . '/../config/partial_footer.php'; ?>
