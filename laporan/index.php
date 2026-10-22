<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/fungsi.php';
require_role(['Admin']);

$pageTitle = 'Cetak Laporan Peminjaman';
$active = 'laporan';

$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
$status = $_GET['status'] ?? '';

$sql = "SELECT p.id_peminjaman, u.nama_lengkap AS peminjam, p.tanggal_pinjam, p.tanggal_rencana_kembali,
               p.status_peminjaman,
               GROUP_CONCAT(CONCAT(a.nama_alat,' (',d.jumlah_pinjam,')') SEPARATOR ', ') AS daftar_alat,
               COALESCE(r.denda, 0) AS denda, COALESCE(r.status_denda, 'Tidak Ada') AS status_denda
        FROM tb_peminjaman p
        JOIN tb_user u ON u.id_user = p.id_user
        LEFT JOIN tb_detail_peminjaman d ON d.id_peminjaman = p.id_peminjaman
        LEFT JOIN tb_alat a ON a.id_alat = d.id_alat
        LEFT JOIN tb_pengembalian r ON r.id_peminjaman = p.id_peminjaman AND r.status = 'Selesai'
        WHERE p.tanggal_pinjam BETWEEN :dari AND :sampai";
$params = [':dari' => $dari, ':sampai' => $sampai];
if ($status !== '') { $sql .= ' AND p.status_peminjaman = :status'; $params[':status'] = $status; }
$sql .= ' GROUP BY p.id_peminjaman ORDER BY p.tanggal_pinjam';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
$totalDenda = array_sum(array_column($rows, 'denda'));
$dendaLunas = 0;
foreach ($rows as $r) { if ($r['status_denda'] === 'Lunas') $dendaLunas += (int)$r['denda']; }
$dendaBelum = $totalDenda - $dendaLunas;

require __DIR__ . '/../config/partial_header.php';
?>
<form method="get" class="toolbar no-print">
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <div><label>Dari</label><input type="date" name="dari" value="<?= e($dari) ?>"></div>
    <div><label>Sampai</label><input type="date" name="sampai" value="<?= e($sampai) ?>"></div>
    <div><label>Status</label>
      <select name="status">
        <option value="">Semua</option>
        <?php foreach (['Diajukan','Dipinjam','Selesai','Ditolak'] as $s): ?>
          <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="align-self:end"><button class="btn btn-primary" type="submit">Tampilkan</button></div>
    <div style="align-self:end"><button class="btn btn-muted" type="button" onclick="window.print()">Cetak / PDF</button></div>
  </div>
</form>

<div class="card">
  <p>Periode: <strong><?= e($dari) ?></strong> s/d <strong><?= e($sampai) ?></strong> &middot; Total Data: <?= count($rows) ?> &middot; Total Denda: <?= rupiah((int)$totalDenda) ?> (Lunas: <?= rupiah($dendaLunas) ?>, Belum Dibayar: <?= rupiah($dendaBelum) ?>)</p>
  <table>
    <thead><tr><th>#</th><th>Peminjam</th><th>Alat</th><th>Tgl Pinjam</th><th>Rencana Kembali</th><th>Status</th><th>Denda</th><th>Status Denda</th></tr></thead>
    <tbody>
      <?php if (!$rows): ?><tr><td colspan="8">Tidak ada data pada periode ini.</td></tr><?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td>#<?= (int)$r['id_peminjaman'] ?></td>
          <td><?= e($r['peminjam']) ?></td>
          <td><?= e($r['daftar_alat'] ?: '-') ?></td>
          <td><?= e($r['tanggal_pinjam']) ?></td>
          <td><?= e($r['tanggal_rencana_kembali']) ?></td>
          <td><?= e($r['status_peminjaman']) ?></td>
          <td><?= rupiah((int)$r['denda']) ?></td>
          <td><?= (int)$r['denda'] > 0 ? e($r['status_denda']) : '-' ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../config/partial_footer.php'; ?>
