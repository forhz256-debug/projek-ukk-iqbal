<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/fungsi.php';
require_role(['Petugas', 'Peminjam']);

$pageTitle = 'Data Peminjaman';
$active = 'peminjaman';
$role = current_role();
$statusFilter = $_GET['status'] ?? '';


$sql = "SELECT p.*, u.nama_lengkap AS nama_peminjam,
               GROUP_CONCAT(CONCAT(a.nama_alat,' (',d.jumlah_pinjam,')') SEPARATOR ', ') AS daftar_alat
        FROM tb_peminjaman p
        JOIN tb_user u ON u.id_user = p.id_user
        LEFT JOIN tb_detail_peminjaman d ON d.id_peminjaman = p.id_peminjaman
        LEFT JOIN tb_alat a ON a.id_alat = d.id_alat";
$conds = [];
$params = [];

if ($role === 'Peminjam') {
    $conds[] = 'p.id_user = :uid';
    $params[':uid'] = current_user_id();
}
if ($statusFilter !== '') {
    $conds[] = 'p.status_peminjaman = :status';
    $params[':status'] = $statusFilter;
}
if ($conds) $sql .= ' WHERE ' . implode(' AND ', $conds);
$sql .= ' GROUP BY p.id_peminjaman ORDER BY p.tanggal_pinjam DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

function badgeClass(string $s): string {
    $badges = [
        'Diajukan' => 'badge-diajukan',
        'Dipinjam' => 'badge-dipinjam',
        'Selesai'  => 'badge-selesai',
        'Ditolak'  => 'badge-ditolak',
    ];

    return $badges[$s] ?? 'badge-diajukan';
}

require __DIR__ . '/../config/partial_header.php';
?>
<div class="toolbar">
  <form method="get" class="search-box">
    <select name="status" onchange="this.form.submit()">
      <option value="">Semua Status</option>
      <?php foreach (['Diajukan','Dipinjam','Selesai','Ditolak'] as $s): ?>
        <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= $s ?></option>
      <?php endforeach; ?>
    </select>
  </form>
  <?php if ($role === 'Peminjam'): ?>
    <a href="ajukan.php" class="btn btn-primary">+ Ajukan Peminjaman</a>
  <?php endif; ?>
</div>

<div class="card">
  <table>
    <thead>
      <tr>
        <th>#</th><th>Peminjam</th><th>Alat</th><th>Tgl Pinjam</th><th>Rencana Kembali</th><th>Status</th><th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?><tr><td colspan="7">Tidak ada data.</td></tr><?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td>#<?= (int)$r['id_peminjaman'] ?></td>
          <td><?= e($r['nama_peminjam']) ?></td>
          <td><?= e($r['daftar_alat'] ?: '-') ?></td>
          <td><?= e($r['tanggal_pinjam']) ?></td>
          <td><?= e($r['tanggal_rencana_kembali']) ?></td>
          <td><span class="badge <?= badgeClass($r['status_peminjaman']) ?>"><?= e($r['status_peminjaman']) ?></span></td>
          <td>
            <a href="detail.php?id=<?= (int)$r['id_peminjaman'] ?>" class="btn btn-muted btn-sm">Detail</a>
            <?php if ($role === 'Petugas' && $r['status_peminjaman'] === 'Diajukan'): ?>
              <a href="setujui.php?id=<?= (int)$r['id_peminjaman'] ?>" class="btn btn-success btn-sm">Proses</a>
            <?php endif; ?>
            <?php if (in_array($role, ['Admin','Petugas'], true) && $r['status_peminjaman'] === 'Diajukan'): ?>
              <form class="inline" method="post" action="hapus.php" onsubmit="return confirm('Batalkan pengajuan ini?');">
                <input type="hidden" name="id_peminjaman" value="<?= (int)$r['id_peminjaman'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Batalkan</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../config/partial_footer.php'; ?>
